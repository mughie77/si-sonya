<?php
// views/includes/header.php
$nama_user = $_SESSION['nama_lengkap'] ?? 'User';
$role = $_SESSION['role'] ?? '';
$inisial = substr($nama_user, 0, 1);
?>
<header class="fixed top-0 left-0 right-0 bg-white/70 backdrop-blur-md z-50 px-6 py-4 flex justify-between items-center border-b border-gray-100 <?php echo ($role == 'admin') ? 'lg:hidden' : ''; ?>">
    <div class="flex items-center gap-4">
        <?php if ($role == 'admin'): ?>
            <button onclick="toggleSidebar()" class="w-10 h-10 flex items-center justify-center rounded-2xl bg-gray-50 text-gray-400 hover:bg-gray-100 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        <?php else: ?>
            <button onclick="toggleExtraMenu()" class="w-10 h-10 flex items-center justify-center rounded-2xl bg-gray-50 text-gray-400 hover:bg-gray-100 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        <?php endif; ?>
    </div>

    <div class="text-center">
        <h1 class="text-xl font-black text-indigo-900 tracking-tighter uppercase">SI-SONYA</h1>
    </div>

    <div class="flex items-center gap-3">
        <div class="w-10 h-10 flex items-center justify-center rounded-2xl bg-gray-50 text-indigo-600 font-black text-xs uppercase shadow-inner border border-white">
            <?php echo $inisial; ?>
        </div>
    </div>
</header>

<!-- Spacer only on screens where header is visible -->
<div class="h-20 <?php echo ($role == 'admin') ? 'lg:hidden' : ''; ?>"></div>

<?php if ($role != 'admin'): ?>
<!-- Pop-up Menu "Lainnya" (For Guru/Siswa) -->
<div id="extraMenu" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[150] hidden flex items-end justify-center sm:items-center p-4">
    <div class="bg-white w-full max-w-md rounded-[40px] p-8 shadow-2xl transform transition-all translate-y-full" id="menuContent">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-2xl font-black text-indigo-900 uppercase tracking-tighter">Menu Utama</h3>
            <button onclick="toggleExtraMenu()" class="text-gray-400 hover:text-red-500 transition">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <a href="dashboard.php" class="p-6 bg-gray-50 rounded-[30px] hover:bg-indigo-50 transition text-center group">
                <span class="text-3xl block mb-2">🏠</span>
                <span class="text-xs font-black uppercase tracking-widest text-gray-500 group-hover:text-indigo-600">Beranda</span>
            </a>
            <a href="change_password.php" class="p-6 bg-gray-50 rounded-[30px] hover:bg-indigo-50 transition text-center group">
                <span class="text-3xl block mb-2">🔒</span>
                <span class="text-xs font-black uppercase tracking-widest text-gray-500 group-hover:text-indigo-600">Ganti Sandi</span>
            </a>
            <a href="lapor_fasilitas.php" class="p-6 bg-gray-50 rounded-[30px] hover:bg-indigo-50 transition text-center group">
                <span class="text-3xl block mb-2">🛠️</span>
                <span class="text-xs font-black uppercase tracking-widest text-gray-500 group-hover:text-indigo-600">Fasilitas</span>
            </a>
            <a href="feedback.php" class="p-6 bg-gray-50 rounded-[30px] hover:bg-indigo-50 transition text-center group">
                <span class="text-3xl block mb-2">💬</span>
                <span class="text-xs font-black uppercase tracking-widest text-gray-500 group-hover:text-indigo-600">Saran</span>
            </a>
        </div>

        <a href="../logout.php" class="mt-8 block w-full py-5 bg-red-50 text-red-600 rounded-[25px] font-black text-center uppercase tracking-widest hover:bg-red-100 transition">
            Keluar Akun
        </a>
    </div>
</div>

<script>
    function toggleExtraMenu() {
        const menu = document.getElementById('extraMenu');
        const content = document.getElementById('menuContent');
        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            setTimeout(() => content.classList.remove('translate-y-full'), 10);
        } else {
            content.classList.add('translate-y-full');
            setTimeout(() => menu.classList.add('hidden'), 300);
        }
    }
</script>
<?php endif; ?>
