<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

$role = $_SESSION['role'];
$nama = $_SESSION['nama_lengkap'];

// Ambil statistik sederhana untuk dashboard
$stats = [];
if ($role == 'admin') {
    $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_bullying'] = $pdo->query("SELECT COUNT(*) FROM bullying_reports")->fetchColumn();
    $stats['total_facilities'] = $pdo->query("SELECT COUNT(*) FROM facility_reports")->fetchColumn();
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

    <!-- Sidebar -->
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
                <a href="kelola_laporan.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">📊 Kelola Laporan</a>
            <?php endif; ?>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../logout.php" class="block py-3 px-4 rounded-xl bg-red-600 hover:bg-red-700 transition text-center font-bold">Keluar</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow flex flex-col">
        <!-- Top Nav -->
        <header class="bg-white shadow-sm border-b p-4 flex justify-between items-center px-8">
            <h2 class="text-xl font-bold text-gray-800">Selamat Datang, <?php echo $nama; ?>!</h2>
            <div class="flex items-center space-x-4">
                <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold uppercase tracking-wide">
                    <?php echo $role; ?>
                </span>
                <!-- Mobile Menu Button -->
                <button class="md:hidden p-2 text-indigo-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </header>

        <!-- Content Body -->
        <div class="p-8 space-y-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php if ($role == 'admin'): ?>
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 p-6 rounded-3xl shadow-lg text-white">
                        <p class="text-sm opacity-80 uppercase font-bold tracking-wider">Total Pengguna</p>
                        <h3 class="text-4xl font-extrabold mt-2"><?php echo $stats['total_users']; ?></h3>
                    </div>
                    <div class="bg-gradient-to-br from-pink-500 to-rose-600 p-6 rounded-3xl shadow-lg text-white">
                        <p class="text-sm opacity-80 uppercase font-bold tracking-wider">Laporan Bullying</p>
                        <h3 class="text-4xl font-extrabold mt-2"><?php echo $stats['total_bullying']; ?></h3>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500 to-orange-600 p-6 rounded-3xl shadow-lg text-white">
                        <p class="text-sm opacity-80 uppercase font-bold tracking-wider">Laporan Fasilitas</p>
                        <h3 class="text-4xl font-extrabold mt-2"><?php echo $stats['total_facilities']; ?></h3>
                    </div>
                <?php else: ?>
                    <div class="bg-gradient-to-br from-indigo-500 to-blue-600 p-6 rounded-3xl shadow-lg text-white">
                        <p class="text-sm opacity-80 uppercase font-bold tracking-wider">Laporan Saya</p>
                        <h3 class="text-4xl font-extrabold mt-2"><?php echo $stats['my_bullying']; ?></h3>
                    </div>
                    <div class="bg-gradient-to-br from-green-500 to-emerald-600 p-6 rounded-3xl shadow-lg text-white">
                        <p class="text-sm opacity-80 uppercase font-bold tracking-wider">Mood Hari Ini</p>
                        <h3 class="text-4xl font-extrabold mt-2">
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

            <!-- Dashboard Welcome Section -->
            <div class="bg-white p-10 rounded-3xl shadow-sm border border-gray-100 flex flex-col md:flex-row items-center gap-10">
                <div class="flex-grow space-y-4">
                    <h1 class="text-4xl font-extrabold text-indigo-900 leading-tight">Bersama Kita Ciptakan Sekolah yang Aman & Menyenangkan! 🚀</h1>
                    <p class="text-gray-600 text-lg">Gunakan platform SI-SONYA untuk melapor segala bentuk tindakan bullying, pantau kesehatan mentalmu, dan bantu perbaiki fasilitas sekolahmu.</p>
                    <div class="flex flex-wrap gap-4 pt-4">
                        <?php if ($role != 'admin'): ?>
                            <a href="lapor_bullying.php" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 transition transform hover:-translate-y-1">Lapor Bullying Sekarang</a>
                            <a href="mood_tracker.php" class="px-8 py-4 bg-white border-2 border-indigo-600 text-indigo-600 hover:bg-indigo-50 rounded-2xl font-bold transition">Isi Mood Tracker</a>
                        <?php else: ?>
                            <a href="kelola_laporan.php" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold shadow-lg shadow-indigo-200 transition transform hover:-translate-y-1">Tinjau Laporan Masuk</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hidden md:block">
                    <img src="https://img.freepik.com/free-vector/bullying-isometric-concept-with-boy-suffering-from-school-bullies-3d-vector-illustration_1284-75463.jpg?t=st=1711860000~exp=1711863600~hmac=..." alt="Anti Bullying" class="w-80 rounded-2xl shadow-2xl">
                </div>
            </div>

            <!-- Features Quick Access (Jika bukan admin) -->
            <?php if ($role != 'admin'): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                 <a href="lapor_bullying.php" class="group bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:border-indigo-500 transition-all">
                    <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center text-red-600 text-2xl group-hover:bg-red-600 group-hover:text-white transition-colors mb-4">🛡️</div>
                    <h4 class="font-bold text-gray-800">Lapor Bullying</h4>
                    <p class="text-gray-500 text-sm mt-1">Jangan takut, kerahasiaanmu terjamin.</p>
                 </a>
                 <a href="mood_tracker.php" class="group bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:border-indigo-500 transition-all">
                    <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center text-green-600 text-2xl group-hover:bg-green-600 group-hover:text-white transition-colors mb-4">😊</div>
                    <h4 class="font-bold text-gray-800">Mood Tracker</h4>
                    <p class="text-gray-500 text-sm mt-1">Bagaimana perasaanmu hari ini?</p>
                 </a>
                 <a href="lapor_fasilitas.php" class="group bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:border-indigo-500 transition-all">
                    <div class="w-12 h-12 bg-amber-100 rounded-2xl flex items-center justify-center text-amber-600 text-2xl group-hover:bg-amber-600 group-hover:text-white transition-colors mb-4">🏗️</div>
                    <h4 class="font-bold text-gray-800">Lapor Fasilitas</h4>
                    <p class="text-gray-500 text-sm mt-1">Ada meja rusak? Laporkan di sini.</p>
                 </a>
                 <a href="feedback.php" class="group bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:border-indigo-500 transition-all">
                    <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center text-purple-600 text-2xl group-hover:bg-purple-600 group-hover:text-white transition-colors mb-4">💬</div>
                    <h4 class="font-bold text-gray-800">Kirim Feedback</h4>
                    <p class="text-gray-500 text-sm mt-1">Saran untuk sekolah yang lebih baik.</p>
                 </a>
            </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
