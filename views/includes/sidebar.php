<?php
// views/includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';

function is_active($page, $current) {
    return $page === $current ? 'bg-indigo-800' : 'hover:bg-indigo-700';
}
?>
<aside class="w-64 bg-indigo-900 text-white flex-shrink-0 hidden md:flex flex-col shadow-xl">
    <div class="p-6 text-2xl font-bold border-b border-indigo-800 tracking-wider">SI-SONYA</div>
    <nav class="flex-grow p-4 space-y-1.5 overflow-y-auto">

        <!-- DASHBOARD (ALL) -->
        <a href="dashboard.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('dashboard.php', $current_page); ?>">🏠 Dashboard</a>

        <!-- LIVE VIEW (ADMIN & GURU) -->
        <?php if ($role === 'admin' || $role === 'guru'): ?>
            <a href="live_monitoring.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('live_monitoring.php', $current_page); ?>">
                📡 Live View <span class="bg-red-500 text-[9px] px-1.5 py-0.5 rounded-full animate-pulse ml-1">LIVE</span>
            </a>
        <?php endif; ?>

        <!-- SISWA & GURU MENU -->
        <?php if ($role === 'siswa' || $role === 'guru'): ?>
            <a href="lapor_bullying.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('lapor_bullying.php', $current_page); ?>">🛡️ Lapor Bullying</a>
            <a href="mood_tracker.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('mood_tracker.php', $current_page); ?>">😊 Mood Tracker</a>
            <a href="lapor_fasilitas.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('lapor_fasilitas.php', $current_page); ?>">🏗️ Lapor Fasilitas</a>
            <a href="feedback.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('feedback.php', $current_page); ?>">💬 Kirim Saran</a>
        <?php endif; ?>

        <!-- ADMIN ONLY MENU -->
        <?php if ($role === 'admin'): ?>
            <div class="pt-4 pb-1 px-4 text-[10px] font-bold text-indigo-300 uppercase tracking-widest">Manajemen Data</div>
            <a href="kelola_siswa.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('kelola_siswa.php', $current_page); ?>">🎓 Data Siswa</a>
            <a href="kelola_guru.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('kelola_guru.php', $current_page); ?>">👨‍🏫 Data Guru</a>
            <a href="kelola_user.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('kelola_user.php', $current_page); ?>">👥 Kelola User</a>
            <a href="import_data.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('import_data.php', $current_page); ?>">📥 Import Data</a>

            <div class="pt-4 pb-1 px-4 text-[10px] font-bold text-indigo-300 uppercase tracking-widest">Laporan & Tool</div>
            <a href="mood_report.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('mood_report.php', $current_page); ?>">📑 Laporan Mood</a>
            <a href="kelola_laporan.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('kelola_laporan.php', $current_page); ?>">📊 Kelola Laporan</a>
            <a href="hash_generator.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('hash_generator.php', $current_page); ?>">🔑 Hash Generator</a>
        <?php endif; ?>

        <!-- ALL USERS -->
        <div class="pt-4 pb-1 px-4 text-[10px] font-bold text-indigo-300 uppercase tracking-widest">Akun</div>
        <a href="change_password.php" class="block py-2.5 px-4 rounded-xl transition <?php echo is_active('change_password.php', $current_page); ?> italic">🔒 Ganti Password</a>

    </nav>
    <div class="p-4 border-t border-indigo-800">
        <a href="../logout.php" class="block py-3 px-4 rounded-xl bg-red-600 hover:bg-red-700 transition text-center font-bold">Keluar</a>
    </div>
</aside>
