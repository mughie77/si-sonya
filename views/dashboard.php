<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['role'];
$nama = $_SESSION['nama_lengkap'];

// Ambil statistik lengkap untuk dashboard
$stats = [];
if ($role == 'admin' || $role == 'guru') {
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_bullying'] = $pdo->query("SELECT COUNT(*) FROM bullying_reports")->fetchColumn();
    $stats['total_facilities'] = $pdo->query("SELECT COUNT(*) FROM facility_reports")->fetchColumn();
    $stats['pending_reports'] = $pdo->query("SELECT COUNT(*) FROM bullying_reports WHERE status = 'pending'")->fetchColumn();
    $stats['active_panics'] = $pdo->query("SELECT COUNT(*) FROM panic_events WHERE status = 'active'")->fetchColumn();

    // Average Mood for Today
    $avg_mood_today = $pdo->query("SELECT AVG(mood_score) FROM mood_tracking WHERE tanggal = CURRENT_DATE")->fetchColumn();
    $stats['avg_mood_today'] = $avg_mood_today ? round($avg_mood_today, 1) : null;

    // Data untuk Chart (Mood rata-rata 7 hari terakhir)
    $chart_data = $pdo->query("SELECT tanggal, AVG(mood_score) as avg_mood FROM mood_tracking GROUP BY tanggal ORDER BY tanggal DESC LIMIT 7")->fetchAll();
    $chart_labels = [];
    $chart_values = [];
    foreach (array_reverse($chart_data) as $c) {
        $chart_labels[] = date('d M', strtotime($c['tanggal']));
        $chart_values[] = round($c['avg_mood'], 1);
    }
} else {
    $stats['my_bullying'] = $pdo->prepare("SELECT COUNT(*) FROM bullying_reports WHERE pelapor_id = ?");
    $stats['my_bullying']->execute([$_SESSION['user_id']]);
    $stats['my_bullying'] = $stats['my_bullying']->fetchColumn();

    $stats['my_mood'] = $pdo->prepare("SELECT mood_score FROM mood_tracking WHERE user_id = ? AND tanggal = CURRENT_DATE");
    $stats['my_mood']->execute([$_SESSION['user_id']]);
    $stats['my_mood'] = $stats['my_mood']->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .panic-active { animation: pulse-red 1s infinite; }
        @keyframes pulse-red {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-grow flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm border-b p-4 flex justify-between items-center px-8">
            <h2 class="text-xl font-bold text-gray-800">Selamat Datang, <?php echo e($nama); ?>!</h2>
            <div class="flex items-center space-x-4">
                <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold uppercase tracking-wide"><?php echo e($role); ?></span>
            </div>
        </header>

        <div class="p-8 space-y-8 overflow-y-auto">
            <!-- Panic Button Section (SISWA ONLY) -->
            <?php if ($role == 'siswa'): ?>
            <div class="bg-white p-8 rounded-3xl shadow-sm border-4 border-red-100 flex flex-col items-center justify-center space-y-4 text-center">
                <div class="bg-red-100 p-4 rounded-full">
                    <span class="text-5xl">🆘</span>
                </div>
                <div>
                    <h3 class="text-2xl font-extrabold text-red-600">PANIC BUTTON</h3>
                    <p class="text-gray-500 text-sm">Tekan tombol di bawah dalam keadaan darurat untuk membagikan lokasi GPS Anda ke pihak sekolah secara instan.</p>
                </div>
                <button id="panicButton" class="bg-red-600 hover:bg-red-700 text-white w-48 h-48 rounded-full shadow-2xl transition transform active:scale-95 flex flex-col items-center justify-center border-8 border-red-200">
                    <span class="text-3xl font-black tracking-tighter">EMERGENCY</span>
                    <span class="text-[10px] font-bold opacity-70 mt-1 uppercase">Tekan Di Sini</span>
                </button>
                <div id="panicStatus" class="hidden text-sm font-bold p-3 rounded-xl"></div>
            </div>

            <script>
                document.getElementById('panicButton').addEventListener('click', function() {
                    if (confirm('Konfirmasi kirim sinyal darurat (Emergency Signal)? Lokasi Anda akan dibagikan ke sekolah.')) {
                        const statusDiv = document.getElementById('panicStatus');
                        statusDiv.className = 'block text-sm font-bold p-3 rounded-xl bg-indigo-50 text-indigo-700 italic';
                        statusDiv.innerText = '🛰️ Sedang mengambil lokasi GPS...';
                        this.disabled = true;
                        this.classList.add('opacity-50');

                        if ("geolocation" in navigator) {
                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    const lat = position.coords.latitude;
                                    const lng = position.coords.longitude;

                                    fetch('../controllers/panic_handler.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({ lat: lat, lng: lng })
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            statusDiv.className = 'block text-sm font-bold p-3 rounded-xl bg-green-100 text-green-700';
                                            statusDiv.innerText = '✅ ' + data.message;
                                            this.classList.add('panic-active');
                                            this.classList.remove('opacity-50');
                                            this.innerHTML = '<span class="text-3xl font-black">ACTIVE</span>';
                                        } else {
                                            alert('Gagal mengirim sinyal: ' + data.message);
                                            resetButton();
                                        }
                                    });
                                },
                                (error) => {
                                    alert('Gagal mengakses GPS: ' + error.message + '. Pastikan fitur lokasi aktif.');
                                    resetButton();
                                }
                            );
                        } else {
                            alert('Browser Anda tidak mendukung GPS.');
                            resetButton();
                        }
                    }
                });

                function resetButton() {
                    const btn = document.getElementById('panicButton');
                    btn.disabled = false;
                    btn.classList.remove('opacity-50');
                    document.getElementById('panicStatus').classList.add('hidden');
                }
            </script>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                <?php if ($role == 'admin' || $role == 'guru'): ?>
                    <div class="bg-indigo-600 p-6 rounded-3xl shadow-lg text-white">
                        <p class="text-[10px] opacity-80 uppercase font-bold tracking-widest">Total Pengguna</p>
                        <h3 class="text-3xl font-extrabold mt-1"><?php echo $stats['total_users']; ?></h3>
                    </div>
                    <div class="bg-red-500 p-6 rounded-3xl shadow-lg text-white">
                        <p class="text-[10px] opacity-80 uppercase font-bold tracking-widest">Laporan Bullying</p>
                        <h3 class="text-3xl font-extrabold mt-1"><?php echo $stats['total_bullying']; ?></h3>
                    </div>
                    <div class="bg-amber-500 p-6 rounded-3xl shadow-lg text-white">
                        <p class="text-[10px] opacity-80 uppercase font-bold tracking-widest">Fasilitas Rusak</p>
                        <h3 class="text-3xl font-extrabold mt-1"><?php echo $stats['total_facilities']; ?></h3>
                    </div>
                    <div class="bg-red-700 p-6 rounded-3xl shadow-lg text-white border-4 border-white/20 animate-pulse relative overflow-hidden">
                        <div class="absolute -right-2 -top-2 text-4xl opacity-20">🆘</div>
                        <p class="text-[10px] opacity-80 uppercase font-bold tracking-widest">Panic Active</p>
                        <h3 class="text-3xl font-extrabold mt-1"><?php echo $stats['active_panics']; ?></h3>
                    </div>
                    <div class="bg-green-600 p-6 rounded-3xl shadow-lg text-white border-4 border-white/20 relative overflow-hidden">
                        <div class="absolute -right-2 -top-2 text-4xl opacity-20">💖</div>
                        <p class="text-[10px] opacity-80 uppercase font-bold tracking-widest">Rata Mood Hari Ini</p>
                        <h3 class="text-3xl font-extrabold mt-1"><?php echo $stats['avg_mood_today'] ?: '0.0'; ?></h3>
                        <p class="text-[9px] mt-1 font-bold uppercase tracking-tighter italic">Skala: 1.0 - 5.0</p>
                    </div>
                <?php else: ?>
                    <div class="bg-indigo-500 p-6 rounded-3xl shadow-lg text-white">
                        <p class="text-[10px] opacity-80 uppercase font-bold tracking-widest">Laporan Saya</p>
                        <h3 class="text-3xl font-extrabold mt-1"><?php echo $stats['my_bullying']; ?></h3>
                    </div>
                    <div class="bg-green-500 p-6 rounded-3xl shadow-lg text-white">
                        <p class="text-[10px] opacity-80 uppercase font-bold tracking-widest">Mood Hari Ini</p>
                        <h3 class="text-3xl font-extrabold mt-1">
                            <?php
                                $m = $stats['my_mood'];
                                if (!$m) echo "Belum Isi";
                                else if ($m == 5) echo "🤩";
                                else if ($m == 4) echo "😊";
                                else if ($m == 3) echo "😐";
                                else if ($m == 2) echo "😟";
                                else echo "😢";
                            ?>
                        </h3>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (($role == 'admin' || $role == 'guru') && !empty($chart_labels)): ?>
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h4 class="text-lg font-bold text-gray-800 mb-6">Tren Kebahagiaan Siswa (7 Hari Terakhir)</h4>
                <canvas id="moodChart" height="100"></canvas>
            </div>
            <script>
                const ctx = document.getElementById('moodChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [{
                            label: 'Rata-rata Skor Mood',
                            data: <?php echo json_encode($chart_values); ?>,
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 3,
                            pointBackgroundColor: '#4f46e5'
                        }]
                    },
                    options: {
                        scales: { y: { min: 1, max: 5 } },
                        plugins: { legend: { display: false } }
                    }
                });
            </script>
            <?php endif; ?>

            <div class="bg-white p-10 rounded-3xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-center gap-10">
                <div class="flex-grow space-y-4">
                    <h1 class="text-4xl font-extrabold text-indigo-900 leading-tight">Sekolah Aman, Siswa Nyaman, Masa Depan Gemilang! 🌈</h1>
                    <p class="text-gray-600 text-lg">Terima kasih telah berkontribusi dalam menjaga ekosistem sekolah melalui SI-SONYA.</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
