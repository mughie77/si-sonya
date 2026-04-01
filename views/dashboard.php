<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$nama = $_SESSION['nama_lengkap'];

function getMoodLabel($score) {
    if (!$score) return 'Belum Ada';
    $s = round($score);
    switch ($s) {
        case 5: return 'Sangat Senang';
        case 4: return 'Senang';
        case 3: return 'Biasa';
        case 2: return 'Sedih';
        case 1: return 'Sangat Sedih';
        default: return 'Tidak Diketahui';
    }
}

$stats = [];
if ($role == 'admin' || $role == 'guru') {
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_bullying'] = $pdo->query("SELECT COUNT(*) FROM bullying_reports")->fetchColumn();
    $stats['total_facilities'] = $pdo->query("SELECT COUNT(*) FROM facility_reports")->fetchColumn();
    $stats['pending_reports'] = $pdo->query("SELECT COUNT(*) FROM bullying_reports WHERE status = 'pending'")->fetchColumn();
    $stats['active_panics'] = $pdo->query("SELECT COUNT(*) FROM panic_events WHERE status = 'active'")->fetchColumn();

    $avg_mood_today = $pdo->query("SELECT AVG(mood_score) FROM mood_tracking WHERE tanggal = CURRENT_DATE")->fetchColumn();
    $stats['avg_mood_today'] = $avg_mood_today ? round($avg_mood_today, 1) : null;
    $stats['avg_mood_label'] = getMoodLabel($stats['avg_mood_today']);

    $chart_data = $pdo->query("SELECT tanggal, AVG(mood_score) as avg_mood FROM mood_tracking GROUP BY tanggal ORDER BY tanggal DESC LIMIT 7")->fetchAll();
    $chart_labels = [];
    $chart_values = [];
    foreach (array_reverse($chart_data) as $c) {
        $chart_labels[] = date('d M', strtotime($c['tanggal']));
        $chart_values[] = round($c['avg_mood'], 1);
    }
}

if ($role == 'siswa' || $role == 'guru') {
    $stats['my_bullying'] = $pdo->prepare("SELECT COUNT(*) FROM bullying_reports WHERE pelapor_id = ?");
    $stats['my_bullying']->execute([$user_id]);
    $stats['my_bullying'] = $stats['my_bullying']->fetchColumn();

    $stats['my_mood'] = $pdo->prepare("SELECT mood_score FROM mood_tracking WHERE user_id = ? AND tanggal = CURRENT_DATE");
    $stats['my_mood']->execute([$user_id]);
    $stats['my_mood'] = $stats['my_mood']->fetchColumn();

    $weekly_moods = $pdo->prepare("SELECT tanggal, mood_score FROM mood_tracking WHERE user_id = ? AND tanggal >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY) ORDER BY tanggal ASC");
    $weekly_moods->execute([$user_id]);
    $weekly_data = [];
    while ($row = $weekly_moods->fetch()) {
        $day = strtoupper(date('D', strtotime($row['tanggal'])));
        $weekly_data[$day] = $row['mood_score'];
    }
}

$days_of_week = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

// Fetch Global Emergency Contacts
$stmt_contact = $pdo->query("SELECT key_name, key_value FROM settings WHERE key_name IN ('emergency_contact_1', 'emergency_contact_2')");
$global_contacts = $stmt_contact->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .bg-zen { background-color: #E2F2FF; }
        .card-zen { background: white; border-radius: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .panic-active { animation: pulse-red 1s infinite; }
        @keyframes pulse-red {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="<?php echo ($role == 'admin') ? 'bg-gray-50' : 'bg-zen'; ?> min-h-screen <?php echo ($role == 'admin') ? 'flex' : ''; ?>">
    <?php if ($role == 'admin') include 'includes/sidebar.php'; ?>

    <div class="flex-grow">
        <?php include 'includes/header.php'; ?>

        <main class="max-w-4xl mx-auto p-6 md:p-10 space-y-8 pb-32">

            <?php if ($role == 'admin'): ?>
                <div class="mb-6 flex justify-between items-end">
                    <div>
                        <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Panel Admin</h2>
                        <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Ringkasan Data Sekolah</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    <div class="bg-indigo-600 p-5 rounded-3xl shadow-lg text-white">
                        <p class="text-[9px] opacity-80 uppercase font-bold tracking-widest">Pengguna</p>
                        <h3 class="text-2xl font-black mt-1"><?php echo $stats['total_users']; ?></h3>
                    </div>
                    <div class="bg-red-500 p-5 rounded-3xl shadow-lg text-white">
                        <p class="text-[9px] opacity-80 uppercase font-bold tracking-widest">Bullying</p>
                        <h3 class="text-2xl font-black mt-1"><?php echo $stats['total_bullying']; ?></h3>
                    </div>
                    <div class="bg-amber-500 p-5 rounded-3xl shadow-lg text-white">
                        <p class="text-[9px] opacity-80 uppercase font-bold tracking-widest">Fasilitas</p>
                        <h3 class="text-2xl font-black mt-1"><?php echo $stats['total_facilities']; ?></h3>
                    </div>
                    <div class="bg-red-700 p-5 rounded-3xl shadow-lg text-white border-2 border-white/20 animate-pulse">
                        <p class="text-[9px] opacity-80 uppercase font-bold tracking-widest">Panik Aktif</p>
                        <h3 class="text-2xl font-black mt-1"><?php echo $stats['active_panics']; ?></h3>
                    </div>
                    <div class="bg-green-600 p-5 rounded-3xl shadow-lg text-white border-2 border-white/20 relative overflow-hidden">
                        <div class="absolute -right-2 -top-2 text-3xl opacity-20">💖</div>
                        <p class="text-[9px] opacity-80 uppercase font-bold tracking-widest">Mood Hari Ini</p>
                        <h3 class="text-2xl font-black mt-1"><?php echo $stats['avg_mood_today'] ?: '0.0'; ?></h3>
                        <p class="text-[8px] mt-1 font-bold uppercase tracking-tighter italic"><?php echo $stats['avg_mood_label']; ?></p>
                    </div>
                </div>

                <div class="bg-white p-8 rounded-[40px] shadow-sm border border-gray-100">
                    <h4 class="font-black text-gray-800 uppercase tracking-widest text-sm mb-6">Tren Kebahagiaan</h4>
                    <canvas id="moodChart" height="150"></canvas>
                </div>
            <?php else: ?>
                <!-- SISWA & GURU DASHBOARD -->
                <div class="card-zen p-10 flex flex-col items-center text-center">
                    <div class="relative mb-6">
                        <div class="w-24 h-24 rounded-full border-4 border-white shadow-xl overflow-hidden">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nama); ?>&background=random&size=128" alt="Avatar">
                        </div>
                    </div>
                    <h1 class="text-2xl font-extrabold text-gray-800"><?php echo e($nama); ?></h1>
                    <p class="text-gray-400 font-medium text-sm mt-1 uppercase tracking-widest"><?php echo e($_SESSION['role']); ?> • <?php echo e($_SESSION['kelas'] ?? 'STAFF'); ?></p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-[#D8F3C5] p-5 rounded-[30px] shadow-sm text-center">
                        <p class="text-gray-600 font-bold text-[9px] uppercase tracking-widest mb-1">Laporan</p>
                        <h4 class="text-2xl font-black text-[#4B7C2F]"><?php echo $stats['my_bullying']; ?></h4>
                    </div>
                    <div class="bg-[#FDE2FF] p-5 rounded-[30px] shadow-sm text-center">
                        <p class="text-gray-600 font-bold text-[9px] uppercase tracking-widest mb-1">Mood</p>
                        <h4 class="text-2xl font-black text-[#A147AF]">
                            <?php
                                $m = $stats['my_mood'];
                                if (!$m) echo "—";
                                else if ($m == 5) echo "🤩";
                                else if ($m == 4) echo "😊";
                                else if ($m == 3) echo "😐";
                                else if ($m == 2) echo "😟";
                                else echo "😢";
                            ?>
                        </h4>
                    </div>
                    <div class="bg-[#E2F2FF] p-5 rounded-[30px] shadow-sm text-center">
                        <p class="text-gray-600 font-bold text-[9px] uppercase tracking-widest mb-1">Poin</p>
                        <h4 class="text-2xl font-black text-[#4285F4]">100</h4>
                    </div>
                </div>

                <div class="card-zen p-8">
                    <div class="flex justify-between items-center mb-6 px-2">
                        <h4 class="font-black text-gray-800 uppercase tracking-widest text-xs">Pantau Mood Mingguan</h4>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest"><?php echo date('M Y'); ?></span>
                    </div>
                    <div class="flex justify-between items-center px-2">
                        <?php foreach ($days_of_week as $day): ?>
                            <div class="flex flex-col items-center">
                                <div class="text-2xl mb-2 grayscale opacity-30 hover:grayscale-0 hover:opacity-100 transition duration-300 cursor-help">
                                    <?php
                                        $s = $weekly_data[$day] ?? 0;
                                        if ($s == 5) echo "🤩";
                                        else if ($s == 4) echo "😊";
                                        else if ($s == 3) echo "😐";
                                        else if ($s == 2) echo "😟";
                                        else if ($s == 1) echo "😢";
                                        else echo "⚪";
                                    ?>
                                </div>
                                <span class="text-[8px] font-black text-gray-400"><?php echo $day; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if ($role == 'siswa'): ?>
            <div class="space-y-4">
                <button id="panicButton" class="w-full bg-red-600 hover:bg-red-700 text-white py-6 rounded-[35px] shadow-2xl shadow-red-500/20 transition-all transform active:scale-95 flex items-center justify-center gap-4 border-b-8 border-red-800">
                    <span class="text-4xl">🆘</span>
                    <div class="text-left">
                        <h4 class="font-black uppercase tracking-tighter text-xl leading-none">TOMBOL PANIK</h4>
                        <p class="text-[9px] font-bold opacity-70 uppercase tracking-widest mt-1">Tekan Dalam Keadaan Darurat</p>
                    </div>
                </button>

                <!-- Global Emergency Contacts Display for Students -->
                <div class="grid grid-cols-2 gap-4">
                    <a href="tel:<?php echo e($global_contacts['emergency_contact_1'] ?? ''); ?>" class="bg-white p-4 rounded-[25px] border border-red-100 flex items-center justify-center gap-3 group hover:bg-red-50 transition">
                        <span class="text-xl">📞</span>
                        <div class="text-left">
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Kontak 1</p>
                            <p class="text-[10px] font-black text-red-600"><?php echo e($global_contacts['emergency_contact_1'] ?? '-'); ?></p>
                        </div>
                    </a>
                    <a href="tel:<?php echo e($global_contacts['emergency_contact_2'] ?? ''); ?>" class="bg-white p-4 rounded-[25px] border border-red-100 flex items-center justify-center gap-3 group hover:bg-red-50 transition">
                        <span class="text-xl">📞</span>
                        <div class="text-left">
                            <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Kontak 2</p>
                            <p class="text-[10px] font-black text-red-600"><?php echo e($global_contacts['emergency_contact_2'] ?? '-'); ?></p>
                        </div>
                    </a>
                    </div>
            </div>
                <?php endif; ?>

                <div class="text-center py-6">
                    <h2 class="text-3xl font-black text-indigo-900 leading-tight">Explore the Power of Your Mind 🧠🚀</h2>
                    <p class="text-gray-500 text-sm mt-3 px-8 leading-relaxed italic">"Sekolah Aman, Siswa Nyaman, Masa Depan Gemilang!"</p>
                </div>
            <?php endif; ?>

        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>

    <script>
        <?php if ($role == 'admin' || $role == 'guru'): ?>
        const ctx = document.getElementById('moodChart').getContext('2d');
        const moodLabels = {
            1: 'Sangat Sedih 😢',
            2: 'Sedih 😟',
            3: 'Biasa 😐',
            4: 'Senang 😊',
            5: 'Sangat Senang 🤩'
        };

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Skor Mood',
                    data: <?php echo json_encode($chart_values); ?>,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 4,
                    pointBackgroundColor: '#4f46e5',
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        min: 1,
                        max: 5,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) { return moodLabels[value] || value; }
                        }
                    }
                },
                plugins: { legend: { display: false } }
            }
        });
        <?php endif; ?>

        const pBtn = document.getElementById('panicButton');
        if (pBtn) {
            pBtn.addEventListener('click', function() {
                if (confirm('Bagi lokasi darurat ke sekolah sekarang?')) {
                    if ("geolocation" in navigator) {
                        navigator.geolocation.getCurrentPosition((pos) => {
                            fetch('../controllers/panic_handler.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    alert('🚨 SINYAL TERKIRIM: Bantuan akan segera datang.');
                                    pBtn.classList.add('panic-active');
                                }
                            });
                        }, (err) => alert('Gagal GPS: ' + err.message));
                    }
                }
            });
        }
    </script>
</body>
</html>
