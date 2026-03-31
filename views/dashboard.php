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
if ($role == 'admin') {
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_bullying'] = $pdo->query("SELECT COUNT(*) FROM bullying_reports")->fetchColumn();
    $stats['total_facilities'] = $pdo->query("SELECT COUNT(*) FROM facility_reports")->fetchColumn();
    $stats['pending_reports'] = $pdo->query("SELECT COUNT(*) FROM bullying_reports WHERE status = 'pending'")->fetchColumn();

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
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <aside class="w-64 bg-indigo-900 text-white flex-shrink-0 hidden md:flex flex-col shadow-xl">
        <div class="p-6 text-2xl font-bold border-b border-indigo-800 tracking-wider">SI-SONYA</div>
        <nav class="flex-grow p-4 space-y-2">
            <a href="dashboard.php" class="block py-3 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-700 transition font-medium">🏠 Dashboard</a>
            <?php if ($role != 'admin'): ?>
                <a href="lapor_bullying.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🛡️ Lapor Bullying</a>
                <a href="mood_tracker.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">😊 Mood Tracker</a>
                <a href="lapor_fasilitas.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🏗️ Lapor Fasilitas</a>
                <a href="feedback.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">💬 Kirim Saran</a>
            <?php else: ?>
                <a href="kelola_user.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">👥 Kelola User</a>
                <a href="import_data.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">📥 Import Data</a>
                <a href="kelola_laporan.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">📊 Kelola Laporan</a>
            <?php endif; ?>
            <a href="change_password.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition italic">🔑 Ganti Password</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../logout.php" class="block py-3 px-4 rounded-xl bg-red-600 hover:bg-red-700 transition text-center font-bold">Keluar</a>
        </div>
    </aside>

    <main class="flex-grow flex flex-col">
        <header class="bg-white shadow-sm border-b p-4 flex justify-between items-center px-8">
            <h2 class="text-xl font-bold text-gray-800">Selamat Datang, <?php echo e($nama); ?>!</h2>
            <div class="flex items-center space-x-4">
                <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold uppercase tracking-wide"><?php echo e($role); ?></span>
            </div>
        </header>

        <div class="p-8 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php if ($role == 'admin'): ?>
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
                    <div class="bg-purple-600 p-6 rounded-3xl shadow-lg text-white border-4 border-white/20">
                        <p class="text-[10px] opacity-80 uppercase font-bold tracking-widest">Belum Diproses</p>
                        <h3 class="text-3xl font-extrabold mt-1"><?php echo $stats['pending_reports']; ?></h3>
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

            <?php if ($role == 'admin' && !empty($chart_labels)): ?>
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
