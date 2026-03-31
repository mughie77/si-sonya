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

// Handle CRUD Operations
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
                    $username = $nis_nip; // Use NIS/NIP as username for login
                    // If password not provided or empty, use NIS/NIP as password
                    $password_plain = !empty($_POST['password']) ? $_POST['password'] : $nis_nip;
                }

                $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, nis_nip, kelas, tempat_lahir, tanggal_lahir) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                try {
                    $stmt->execute([$username, $password_hash, $nama, $role, $nis_nip, $kelas, $tempat_lahir, $tanggal_lahir]);
                    $success = "User berhasil ditambahkan!";
                } catch (PDOException $e) {
                    $error = "Gagal menambah user: " . $e->getMessage();
                }
            } elseif ($_POST['action'] == 'delete') {
                $id = $_POST['id'];
                if ($id != $_SESSION['user_id']) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    $success = "User berhasil dihapus!";
                } else {
                    $error = "Anda tidak bisa menghapus diri sendiri!";
                }
            }
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role, nama_lengkap")->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - SI-SONYA Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-indigo-900 text-white flex-shrink-0 hidden md:flex flex-col shadow-xl">
        <div class="p-6 text-2xl font-bold border-b border-indigo-800 tracking-wider">SI-SONYA</div>
        <nav class="flex-grow p-4 space-y-2">
            <a href="dashboard.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🏠 Dashboard</a>
            <a href="live_monitoring.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">📡 Live View <span class="bg-red-500 text-[10px] px-2 py-0.5 rounded-full animate-pulse">NEW</span></a>
            <a href="kelola_user.php" class="block py-3 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-700 transition font-medium">👥 Kelola User</a>
            <a href="kelola_laporan.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">📊 Kelola Laporan</a>
            <a href="import_data.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">📥 Import Data</a>
            <a href="hash_generator.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🔑 Hash Generator</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../logout.php" class="block py-3 px-4 rounded-xl bg-red-600 hover:bg-red-700 transition text-center font-bold">Keluar</a>
        </div>
    </aside>

    <main class="flex-grow flex flex-col">
        <header class="bg-white shadow-sm border-b p-4 px-8 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Kelola Data Pengguna</h2>
            <div class="flex gap-2">
                <button onclick="openModal('siswa')" class="bg-green-600 text-white px-4 py-2 rounded-xl font-bold hover:bg-green-700 transition shadow-lg text-sm">+ Siswa</button>
                <button onclick="openModal('guru')" class="bg-blue-600 text-white px-4 py-2 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg text-sm">+ Guru</button>
                <button onclick="openModal('admin')" class="bg-indigo-600 text-white px-4 py-2 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg text-sm">+ Admin</button>
            </div>
        </header>

        <div class="p-8">
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-x-auto">
                <table class="w-full text-left min-w-[800px]">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 font-bold text-indigo-900">Nama Lengkap</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">NIS / NIP</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">Role</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">Kelas</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">TTL</th>
                            <th class="px-6 py-4 font-bold text-indigo-900 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-800"><?php echo e($u['nama_lengkap']); ?></div>
                                    <div class="text-[10px] text-gray-400">Username: <?php echo e($u['username']); ?></div>
                                </td>
                                <td class="px-6 py-4 text-gray-600 font-mono text-sm"><?php echo e($u['nis_nip'] ?? '-'); ?></td>
                                <td class="px-6 py-4">
                                    <?php
                                        $role_color = 'bg-gray-100 text-gray-700';
                                        if ($u['role'] == 'admin') $role_color = 'bg-red-100 text-red-700';
                                        if ($u['role'] == 'guru') $role_color = 'bg-blue-100 text-blue-700';
                                        if ($u['role'] == 'siswa') $role_color = 'bg-green-100 text-green-700';
                                    ?>
                                    <span class="px-3 py-1 <?php echo $role_color; ?> rounded-full text-[10px] font-extrabold uppercase tracking-widest">
                                        <?php echo e($u['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600"><?php echo e($u['kelas'] ?? '-'); ?></td>
                                <td class="px-6 py-4 text-gray-500 text-xs italic">
                                    <?php echo $u['tempat_lahir'] ? e($u['tempat_lahir']) : '-'; ?>,
                                    <?php echo $u['tanggal_lahir'] ? date('d/m/Y', strtotime($u['tanggal_lahir'])) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="" method="POST" onsubmit="return confirm('Hapus user ini?')" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo e($u['id']); ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm bg-red-50 px-3 py-1 rounded-lg transition">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Add -->
        <div id="modal-add" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl p-8 overflow-y-auto max-h-[90vh]">
                <h3 id="modal-title" class="text-2xl font-bold text-indigo-900 mb-6">Tambah Pengguna Baru</h3>
                <form action="" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="role" id="input-role">

                    <div id="field-username" class="hidden">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Username Admin</label>
                        <input type="text" name="username" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                    </div>

                    <div id="field-nis-nip">
                        <label id="label-nis-nip" class="block text-sm font-semibold text-gray-700 mb-1">NIS / NIP</label>
                        <input type="text" name="nis_nip" id="input-nis-nip" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                        <p class="text-[10px] text-gray-400 mt-1 italic">* Digunakan untuk login dan password default.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                        </div>
                    </div>

                    <div id="field-kelas">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kelas</label>
                        <input type="text" name="kelas" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password (Opsional)</label>
                        <input type="password" name="password" placeholder="Kosongkan jika ingin sama dengan NIS/NIP" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="button" onclick="closeModal()" class="flex-grow py-3 border border-gray-200 rounded-xl font-bold hover:bg-gray-50 transition">Batal</button>
                        <button type="submit" class="flex-grow py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function openModal(role) {
            document.getElementById('modal-add').classList.remove('hidden');
            document.getElementById('input-role').value = role;
            document.getElementById('modal-title').innerText = 'Tambah Data ' + role.charAt(0).toUpperCase() + role.slice(1);

            // Toggle Fields
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
                document.getElementById('label-nis-nip').innerText = (role === 'siswa') ? 'NIS (Nomor Induk Siswa)' : 'NIP / NIK (Guru)';
            }
        }

        function closeModal() {
            document.getElementById('modal-add').classList.add('hidden');
        }
    </script>
</body>
</html>
