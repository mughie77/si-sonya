<?php
// views/includes/footer_nav.php
$cur = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';

function get_active_class($page, $current) {
    return ($page === $current) ? 'text-indigo-600' : 'text-gray-400';
}
?>
<div class="h-24"></div> <!-- Spacer for fixed footer -->
<nav class="fixed bottom-6 left-6 right-6 bg-white/90 backdrop-blur-xl rounded-[35px] shadow-2xl border border-white/50 px-8 py-4 flex justify-between items-center z-[90]">

    <!-- BERANDA -->
    <a href="dashboard.php" class="flex flex-col items-center gap-1 group">
        <div class="p-2 rounded-2xl group-active:scale-90 transition <?php echo ($cur == 'dashboard.php') ? 'bg-indigo-50' : ''; ?>">
            <svg class="w-6 h-6 <?php echo get_active_class('dashboard.php', $cur); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
        </div>
    </a>

    <!-- MOOD -->
    <?php if ($role != 'admin'): ?>
        <a href="mood_tracker.php" class="flex flex-col items-center gap-1 group">
            <div class="p-2 rounded-2xl group-active:scale-90 transition <?php echo ($cur == 'mood_tracker.php') ? 'bg-indigo-50' : ''; ?>">
                <svg class="w-6 h-6 <?php echo get_active_class('mood_tracker.php', $cur); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </a>
    <?php else: ?>
        <a href="mood_report.php" class="flex flex-col items-center gap-1 group">
            <div class="p-2 rounded-2xl group-active:scale-90 transition <?php echo ($cur == 'mood_report.php') ? 'bg-indigo-50' : ''; ?>">
                <svg class="w-6 h-6 <?php echo get_active_class('mood_report.php', $cur); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </a>
    <?php endif; ?>

    <!-- CENTER / PRIMARY ACTION (LAPOR) -->
    <a href="lapor_bullying.php" class="flex flex-col items-center -mt-12 group">
        <div class="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center text-white shadow-xl shadow-indigo-200 border-4 border-white group-active:scale-90 transition">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path>
            </svg>
        </div>
    </a>

    <!-- LIVE VIEW (SECURITY) -->
    <?php if ($role == 'admin' || $role == 'guru'): ?>
        <a href="live_monitoring.php" class="flex flex-col items-center gap-1 group">
            <div class="p-2 rounded-2xl group-active:scale-90 transition <?php echo ($cur == 'live_monitoring.php') ? 'bg-red-50' : ''; ?>">
                <svg class="w-6 h-6 <?php echo ($cur == 'live_monitoring.php') ? 'text-red-500' : 'text-gray-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
            </div>
        </a>
    <?php else: ?>
        <!-- For Siswa, this could be Lapor Fasilitas -->
        <a href="lapor_fasilitas.php" class="flex flex-col items-center gap-1 group">
            <div class="p-2 rounded-2xl group-active:scale-90 transition <?php echo ($cur == 'lapor_fasilitas.php') ? 'bg-indigo-50' : ''; ?>">
                <svg class="w-6 h-6 <?php echo get_active_class('lapor_fasilitas.php', $cur); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
        </a>
    <?php endif; ?>

    <!-- PROFILE / MENU -->
    <button onclick="toggleExtraMenu()" class="flex flex-col items-center gap-1 group">
        <div class="p-2 rounded-2xl group-active:scale-90 transition">
            <div class="w-6 h-6 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-[10px] font-black text-gray-500 overflow-hidden">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nama_user); ?>&size=32" alt="P">
            </div>
        </div>
    </button>

</nav>
