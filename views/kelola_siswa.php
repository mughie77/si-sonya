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

// Handle CRUD Operations (Student Only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Terjadi kesalahan keamanan (CSRF Token invalid).";
    } else {
        if (isset($_POST['action'])) {
            if ($_POST['action'] == 'add_student') {
                $role = 'siswa';
                $nama = trim($_POST['nama_lengkap']);
                $nis = trim($_POST['nis']);
                $username = $nis;
                $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
                $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
                $kelas = trim($_POST['kelas'] ?? '');

                $password_plain = !empty($_POST['password']) ? $_POST['password'] : $nis;
                $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, nis_nip, kelas, tempat_lahir, tanggal_lahir) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $password_hash, $nama, $role, $nis, $kelas, $tempat_lahir, $tanggal_lahir]);
                    $success = "Data Siswa berhasil ditambahkan!";
                } catch (PDOException $e) {
                    $error = "Gagal menambah data siswa: " . $e->getMessage();
                }
            } elseif ($_POST['action'] == 'edit_student') {
                $id = $_POST['id'];
                $nama = trim($_POST['nama_lengkap']);
                $nis = trim($_POST['nis']);
                $username = $nis;
                $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
                $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
                $kelas = trim($_POST['kelas'] ?? '');

                $sql = "UPDATE users SET username = ?, nama_lengkap = ?, nis_nip = ?, kelas = ?, tempat_lahir = ?, tanggal_lahir = ?";
                $params = [$username, $nama, $nis, $kelas, $tempat_lahir, $tanggal_lahir];

                if (!empty($_POST['password'])) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id = ? AND role = 'siswa'";
                $params[] = $id;

                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success = "Data Siswa berhasil diperbarui!";
                } catch (PDOException $e) {
                    $error = "Gagal memperbarui data siswa: " . $e->getMessage();
                }
            } elseif ($_POST['action'] == 'delete') {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'siswa'");
                $stmt->execute([$id]);
                $success = "Data Siswa berhasil dihapus!";
            }
        }
    }
}

// Search and List Students
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users WHERE role = 'siswa'";
$params = [];
if ($search) {
    $query .= " AND (nama_lengkap LIKE ? OR nis_nip LIKE ? OR kelas LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$query .= " ORDER BY kelas, nama_lengkap";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Siswa - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-grow flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm border-b p-4 px-8 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Manajemen Data Siswa</h2>
            <button onclick="openModal('add')" class="bg-green-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-green-700 transition shadow-lg text-sm">+ Tambah Siswa</button>
        </header>

        <div class="p-8 overflow-y-auto">
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <!-- Search Bar -->
            <form action="" method="GET" class="mb-6">
                <div class="relative max-w-md">
                    <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Cari Nama, NIS, atau Kelas..."
                        class="w-full pl-12 pr-4 py-3 rounded-2xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition duration-200">
                    <div class="absolute left-4 top-3.5 text-gray-400">🔍</div>
                </div>
            </form>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-x-auto">
                <table class="w-full text-left min-w-[800px]">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 font-bold text-indigo-900">NIS</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">Nama Lengkap</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">Kelas</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">TTL</th>
                            <th class="px-6 py-4 font-bold text-indigo-900 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic font-medium">Tidak ada data siswa ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($students as $s): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-indigo-600 font-mono text-sm font-bold"><?php echo e($s['nis_nip']); ?></td>
                                <td class="px-6 py-4 font-semibold text-gray-800"><?php echo e($s['nama_lengkap']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold"><?php echo e($s['kelas'] ?? '-'); ?></span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-xs italic">
                                    <?php echo $s['tempat_lahir'] ? e($s['tempat_lahir']) : '-'; ?>,
                                    <?php echo $s['tanggal_lahir'] ? date('d/m/Y', strtotime($s['tanggal_lahir'])) : '-'; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button onclick='openModal("edit", <?php echo json_encode($s); ?>)' class="text-indigo-600 hover:text-indigo-800 font-bold text-xs bg-indigo-50 px-3 py-1 rounded-lg transition-all">Edit</button>
                                        <form action="" method="POST" onsubmit="return confirm('Hapus data siswa ini?')" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo e($s['id']); ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs bg-red-50 px-3 py-1 rounded-lg transition-all">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Add/Edit Student -->
        <div id="modal-student" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl p-8 overflow-y-auto max-h-[90vh]">
                <h3 id="modal-title" class="text-2xl font-extrabold text-indigo-900 mb-6">Tambah Data Siswa Baru</h3>
                <form action="" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" id="input-action" value="add_student">
                    <input type="hidden" name="id" id="input-id">

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nomor Induk Siswa (NIS)</label>
                        <input type="text" name="nis" id="input-nis" required placeholder="Contoh: 12345" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                        <p class="text-[10px] text-gray-400 mt-1 italic">* Digunakan untuk login dan password default.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" id="input-nama" required placeholder="Nama Siswa" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" id="input-tempat" placeholder="Kota" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="input-tanggal" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Kelas</label>
                        <input type="text" name="kelas" id="input-kelas" placeholder="Contoh: X RPL 1" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Password Baru (Kosongkan jika tidak diganti)</label>
                        <input type="password" name="password" placeholder="Masukkan password baru..." class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="button" onclick="closeModal()" class="flex-grow py-3 border border-gray-200 rounded-xl font-bold hover:bg-gray-50 transition">Batal</button>
                        <button type="submit" id="btn-submit" class="flex-grow py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 transition">Simpan Data Siswa</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function openModal(mode, data = null) {
            const modal = document.getElementById('modal-student');
            const title = document.getElementById('modal-title');
            const action = document.getElementById('input-action');
            const btn = document.getElementById('btn-submit');

            modal.classList.remove('hidden');

            if (mode === 'edit' && data) {
                title.innerText = 'Edit Data Siswa';
                action.value = 'edit_student';
                btn.innerText = 'Perbarui Data';
                btn.className = 'flex-grow py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition';

                document.getElementById('input-id').value = data.id;
                document.getElementById('input-nis').value = data.nis_nip;
                document.getElementById('input-nama').value = data.nama_lengkap;
                document.getElementById('input-tempat').value = data.tempat_lahir || '';
                document.getElementById('input-tanggal').value = data.tanggal_lahir || '';
                document.getElementById('input-kelas').value = data.kelas || '';
            } else {
                title.innerText = 'Tambah Data Siswa Baru';
                action.value = 'add_student';
                btn.innerText = 'Simpan Data Siswa';
                btn.className = 'flex-grow py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 transition';

                document.getElementById('input-id').value = '';
                document.getElementById('input-nis').value = '';
                document.getElementById('input-nama').value = '';
                document.getElementById('input-tempat').value = '';
                document.getElementById('input-tanggal').value = '';
                document.getElementById('input-kelas').value = '';
            }
        }

        function closeModal() {
            document.getElementById('modal-student').classList.add('hidden');
        }
    </script>
</body>
</html>
