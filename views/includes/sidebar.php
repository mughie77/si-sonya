<?php
// views/includes/sidebar.php
$cur = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Overlay for Mobile -->
<div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-[110] hidden lg:hidden backdrop-blur-sm"></div>

<!-- Sidebar -->
<aside id="sidebar-admin" class="fixed inset-y-0 left-0 w-72 bg-indigo-950 flex flex-col p-8 text-white z-[120] transform -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 transition-transform duration-300 shadow-2xl overflow-y-auto custom-scrollbar">
    <div class="flex justify-between items-center mb-12">
        <div>
            <h1 class="text-3xl font-black tracking-tighter uppercase">SI-SONYA</h1>
            <p class="text-indigo-400 text-[10px] font-black uppercase tracking-widest mt-1">Admin Dashboard</p>
        </div>
        <button onclick="toggleSidebar()" class="lg:hidden text-white/50 hover:text-white">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <nav class="flex-grow space-y-2">
        <p class="text-[9px] font-black text-indigo-400 uppercase tracking-[0.2em] mb-4 opacity-50">Menu Utama</p>

        <a href="dashboard.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl transition <?php echo ($cur == 'dashboard.php') ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-white/5'; ?>">
            <span class="text-xl">🏠</span>
            <span class="text-xs font-bold uppercase tracking-widest">Beranda</span>
        </a>

        <a href="live_monitoring.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl transition <?php echo ($cur == 'live_monitoring.php') ? 'bg-red-600 shadow-lg shadow-red-900/50' : 'hover:bg-white/5'; ?>">
            <span class="text-xl">📡</span>
            <span class="text-xs font-bold uppercase tracking-widest">Live Monitoring</span>
        </a>

        <a href="mood_report.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl transition <?php echo ($cur == 'mood_report.php') ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-white/5'; ?>">
            <span class="text-xl">📊</span>
            <span class="text-xs font-bold uppercase tracking-widest">Laporan Mood</span>
        </a>

        <p class="text-[9px] font-black text-indigo-400 uppercase tracking-[0.2em] mt-10 mb-4 opacity-50">Manajemen Data</p>

        <a href="kelola_siswa.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl transition <?php echo ($cur == 'kelola_siswa.php') ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-white/5'; ?>">
            <span class="text-xl">🎓</span>
            <span class="text-xs font-bold uppercase tracking-widest">Data Siswa</span>
        </a>

        <a href="kelola_guru.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl transition <?php echo ($cur == 'kelola_guru.php') ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-white/5'; ?>">
            <span class="text-xl">👨‍🏫</span>
            <span class="text-xs font-bold uppercase tracking-widest">Data Guru</span>
        </a>

        <a href="kelola_user.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl transition <?php echo ($cur == 'kelola_user.php') ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-white/5'; ?>">
            <span class="text-xl">👥</span>
            <span class="text-xs font-bold uppercase tracking-widest">Kelola Akun</span>
        </a>

        <a href="kelola_laporan.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl transition <?php echo ($cur == 'kelola_laporan.php') ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-white/5'; ?>">
            <span class="text-xl">📝</span>
            <span class="text-xs font-bold uppercase tracking-widest">Aduan & Saran</span>
        </a>

        <p class="text-[9px] font-black text-indigo-400 uppercase tracking-[0.2em] mt-10 mb-4 opacity-50">Utilitas</p>

        <a href="import_data.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl transition <?php echo ($cur == 'import_data.php') ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-white/5'; ?>">
            <span class="text-xl">📥</span>
            <span class="text-xs font-bold uppercase tracking-widest">Unggah Excel</span>
        </a>

        <a href="hash_generator.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl transition <?php echo ($cur == 'hash_generator.php') ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-white/5'; ?>">
            <span class="text-xl">🔑</span>
            <span class="text-xs font-bold uppercase tracking-widest">Keamanan</span>
        </a>

        <a href="pengaturan_sistem.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl transition <?php echo ($cur == 'pengaturan_sistem.php') ? 'bg-indigo-600 shadow-lg shadow-indigo-900/50' : 'hover:bg-white/5'; ?>">
            <span class="text-xl">⚙️</span>
            <span class="text-xs font-bold uppercase tracking-widest">Pengaturan</span>
        </a>
    </nav>

    <div class="mt-12 pt-8 border-t border-white/10">
        <a href="../logout.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white transition group">
            <span class="text-xl">🚪</span>
            <span class="text-xs font-bold uppercase tracking-widest">Keluar Sistem</span>
        </a>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar-admin');
        const overlay = document.getElementById('sidebar-overlay');
        const isHidden = sidebar.classList.contains('-translate-x-full');

        if (isHidden) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }
</script>
