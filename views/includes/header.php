<?php
// views/includes/header.php
$nama_user = $_SESSION['nama_lengkap'] ?? 'User';
$inisial = substr($nama_user, 0, 1);
?>
<header class="fixed top-0 left-0 right-0 bg-white/70 backdrop-blur-md z-50 px-6 py-4 flex justify-between items-center border-b border-gray-100">
    <button onclick="toggleExtraMenu()" class="w-10 h-10 flex items-center justify-center rounded-2xl bg-gray-50 text-gray-400 hover:bg-gray-100 transition">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
    </button>

    <div class="text-center">
        <h1 class="text-xl font-black text-indigo-900 tracking-tighter">SI-SONYA</h1>
    </div>

    <div class="flex items-center gap-3">
        <button class="w-10 h-10 flex items-center justify-center rounded-2xl bg-gray-50 text-gray-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        </button>
    </div>
</header>
<div class="h-20"></div> <!-- Spacer -->

<!-- Pop-up Menu "Lainnya" -->
<div id="extraMenu" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden flex items-end justify-center sm:items-center p-4">
    <div class="bg-white w-full max-w-md rounded-[40px] p-8 shadow-2xl transform transition-all translate-y-full" id="menuContent">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-2xl font-black text-indigo-900 uppercase tracking-tighter">Menu Utama</h3>
            <button onclick="toggleExtraMenu()" class="text-gray-400 hover:text-red-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <!-- Link umum -->
            <a href="dashboard.php" class="p-4 bg-gray-50 rounded-3xl hover:bg-indigo-50 transition text-center group">
                <span class="text-2xl block mb-2">🏠</span>
                <span class="text-xs font-bold text-gray-600 group-hover:text-indigo-600">Beranda</span>
            </a>
            <a href="change_password.php" class="p-4 bg-gray-50 rounded-3xl hover:bg-indigo-50 transition text-center group">
                <span class="text-2xl block mb-2">🔒</span>
                <span class="text-xs font-bold text-gray-600 group-hover:text-indigo-600">Ganti Sandi</span>
            </a>

            <?php if ($_SESSION['role'] == 'admin'): ?>
                <a href="kelola_siswa.php" class="p-4 bg-gray-50 rounded-3xl hover:bg-indigo-50 transition text-center group">
                    <span class="text-2xl block mb-2">🎓</span>
                    <span class="text-xs font-bold text-gray-600 group-hover:text-indigo-600">Data Siswa</span>
                </a>
                <a href="kelola_guru.php" class="p-4 bg-gray-50 rounded-3xl hover:bg-indigo-50 transition text-center group">
                    <span class="text-2xl block mb-2">👨‍🏫</span>
                    <span class="text-xs font-bold text-gray-600 group-hover:text-indigo-600">Data Guru</span>
                </a>
                <a href="kelola_user.php" class="p-4 bg-gray-50 rounded-3xl hover:bg-indigo-50 transition text-center group">
                    <span class="text-2xl block mb-2">👥</span>
                    <span class="text-xs font-bold text-gray-600 group-hover:text-indigo-600">Kelola Akun</span>
                </a>
                <a href="import_data.php" class="p-4 bg-gray-50 rounded-3xl hover:bg-indigo-50 transition text-center group">
                    <span class="text-2xl block mb-2">📥</span>
                    <span class="text-xs font-bold text-gray-600 group-hover:text-indigo-600">Unggah Data</span>
                </a>
                <a href="kelola_laporan.php" class="p-4 bg-gray-50 rounded-3xl hover:bg-indigo-50 transition text-center group">
                    <span class="text-2xl block mb-2">📊</span>
                    <span class="text-xs font-bold text-gray-600 group-hover:text-indigo-600">Manajemen Laporan</span>
                </a>
                <a href="hash_generator.php" class="p-4 bg-gray-50 rounded-3xl hover:bg-indigo-50 transition text-center group">
                    <span class="text-2xl block mb-2">🔑</span>
                    <span class="text-xs font-bold text-gray-600 group-hover:text-indigo-600">Alat Keamanan</span>
                </a>
            <?php endif; ?>
        </div>

        <a href="../logout.php" class="mt-8 block w-full py-4 bg-red-50 text-red-600 rounded-3xl font-black text-center uppercase tracking-widest hover:bg-red-100 transition">
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
