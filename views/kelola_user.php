<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in() || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Terjadi kesalahan keamanan (CSRF Token invalid).";
    } else {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'add') {
                $role = $_POST['role'];
                $nama = trim($_POST['nama_lengkap']);
                $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
                $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
                $kelas = trim($_POST['kelas'] ?? '');

                if ($role == 'admin') {
                    $username = trim($_POST['username']);
                    $nis_nip = null;
                    $password_plain = $_POST['password'];
                } else {
                    $nis_nip = trim($_POST['nis_nip']);
                    $username = $nis_nip;
                    $password_plain = !empty($_POST['password']) ? $_POST['password'] : $nis_nip;
                }

                $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, nis_nip, kelas, tempat_lahir, tanggal_lahir) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $password_hash, $nama, $role, $nis_nip, $kelas, $tempat_lahir, $tanggal_lahir]);
                    $success = "Pengguna berhasil ditambahkan!";
                } catch (PDOException $e) {
                    $error = "Gagal menambah pengguna: " . $e->getMessage();
                }
            } elseif ($_POST['action'] == 'delete') {
                $id = $_POST['id'];
                if ($id != $_SESSION['user_id']) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "Pengguna berhasil dihapus!";
                } else {
                    $error = "Anda tidak bisa menghapus diri sendiri!";
                }
            }
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role, nama_lengkap")->fetchAll();
$csrf_token = generate_csrf_token();
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen <?php echo ($role == 'admin') ? 'flex' : ''; ?>">
    <?php if ($role == 'admin') include 'includes/sidebar.php'; ?>

    <div class="flex-grow">
        <?php include 'includes/header.php'; ?>

        <main class="max-w-6xl mx-auto p-6 md:p-10 space-y-8 pb-32">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                <div>
                    <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Semua Pengguna</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Master Data User SI-SONYA</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="openModal('siswa')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition shadow-xl shadow-emerald-100">+ Siswa</button>
                    <button onclick="openModal('guru')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition shadow-xl shadow-blue-100">+ Guru</button>
                    <button onclick="openModal('admin')" class="bg-indigo-900 hover:bg-black text-white px-4 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest transition shadow-xl shadow-indigo-100">+ Admin</button>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-2xl text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-2xl text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-x-auto">
                <table class="w-full text-left min-w-[800px]">
                    <thead class="bg-gray-50/50 border-b border-gray-100">
                        <tr>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Identitas</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">ID / NIS / NIP</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Peran</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Kelas / Ket</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-gray-50/50 transition group">
                                <td class="px-8 py-6">
                                    <div class="font-black text-gray-800 uppercase tracking-tight"><?php echo e($u['nama_lengkap']); ?></div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase italic">Username: <?php echo e($u['username']); ?></div>
                                </td>
                                <td class="px-8 py-6 text-blue-600 font-mono text-sm font-bold"><?php echo e($u['nis_nip'] ?? 'ADMIN'); ?></td>
                                <td class="px-8 py-6">
                                    <?php
                                        $role_class = 'bg-gray-100 text-gray-700';
                                        if ($u['role'] == 'admin') $role_class = 'bg-indigo-900 text-white';
                                        if ($u['role'] == 'guru') $role_class = 'bg-blue-50 text-blue-700';
                                        if ($u['role'] == 'siswa') $role_class = 'bg-emerald-50 text-emerald-700';
                                    ?>
                                    <span class="px-3 py-1 <?php echo $role_class; ?> rounded-full text-[10px] font-black uppercase tracking-widest">
                                        <?php echo e($u['role']); ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-gray-600 text-xs font-bold"><?php echo e($u['kelas'] ?? '-'); ?></td>
                                <td class="px-8 py-6 text-center">
                                    <form action="" method="POST" onsubmit="return confirm('Hapus pengguna ini?')" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo e($u['id']); ?>">
                                        <button type="submit" class="w-10 h-10 inline-flex items-center justify-center bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition shadow-sm">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="modal-add" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-lg rounded-[40px] p-10 shadow-2xl overflow-y-auto max-h-[90vh]">
                    <h3 id="modal-title" class="text-2xl font-black text-indigo-900 mb-8 uppercase tracking-tighter">Tambah Pengguna</h3>
                    <form action="" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="role" id="input-role">

                        <div id="field-username" class="hidden">
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Username Admin</label>
                            <input type="text" name="username" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                        </div>

                        <div id="field-nis-nip">
                            <label id="label-nis-nip" class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Nomor Induk</label>
                            <input type="text" name="nis_nip" id="input-nis-nip" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                        </div>

                        <div>
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" required class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                            </div>
                        </div>

                        <div id="field-kelas">
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Kelas / Mapel</label>
                            <input type="text" name="kelas" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                        </div>

                        <div>
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Kata Sandi (Opsional)</label>
                            <input type="password" name="password" placeholder="Bawaan: Nomor Induk" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button type="button" onclick="closeModal()" class="flex-grow py-5 bg-gray-50 text-gray-500 rounded-[25px] font-black uppercase text-xs tracking-widest transition">Batal</button>
                            <button type="submit" class="flex-grow py-5 bg-blue-600 text-white rounded-[25px] font-black uppercase text-xs tracking-widest shadow-xl shadow-blue-100 transition transform active:scale-95">Simpan User</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>

    <script>
        function openModal(role) {
            document.getElementById('modal-add').classList.remove('hidden');
            document.getElementById('input-role').value = role;
            document.getElementById('modal-title').innerText = 'Tambah ' + role.charAt(0).toUpperCase() + role.slice(1);

            if (role === 'admin') {
                document.getElementById('field-username').classList.remove('hidden');
                document.getElementById('field-nis-nip').classList.add('hidden');
                document.getElementById('field-kelas').classList.add('hidden');
                document.getElementById('input-nis-nip').required = false;
            } else {
                document.getElementById('field-username').classList.add('hidden');
                document.getElementById('field-nis-nip').classList.remove('hidden');
                document.getElementById('field-kelas').classList.remove('hidden');
                document.getElementById('input-nis-nip').required = true;
                document.getElementById('label-nis-nip').innerText = (role === 'siswa') ? 'NIS Siswa' : 'NIP / NIK Guru';
            }
        }
        function closeModal() { document.getElementById('modal-add').classList.add('hidden'); }
    </script>
</body>
</html>
