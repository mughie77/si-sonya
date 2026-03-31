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
            if ($_POST['action'] == 'add_guru') {
                $role = 'guru';
                $nama = trim($_POST['nama_lengkap']);
                $nip = trim($_POST['nip']);
                $username = $nip;
                $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
                $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
                $kelas = trim($_POST['kelas'] ?? '');

                $password_plain = !empty($_POST['password']) ? $_POST['password'] : $nip;
                $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, nis_nip, kelas, tempat_lahir, tanggal_lahir) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $password_hash, $nama, $role, $nip, $kelas, $tempat_lahir, $tanggal_lahir]);
                    $success = "Data Guru berhasil ditambahkan!";
                } catch (PDOException $e) {
                    $error = "Gagal menambah data guru: " . $e->getMessage();
                }
            } elseif ($_POST['action'] == 'edit_guru') {
                $id = $_POST['id'];
                $nama = trim($_POST['nama_lengkap']);
                $nip = trim($_POST['nip']);
                $username = $nip;
                $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
                $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
                $kelas = trim($_POST['kelas'] ?? '');

                $sql = "UPDATE users SET username = ?, nama_lengkap = ?, nis_nip = ?, kelas = ?, tempat_lahir = ?, tanggal_lahir = ?";
                $params = [$username, $nama, $nip, $kelas, $tempat_lahir, $tanggal_lahir];

                if (!empty($_POST['password'])) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }

                $sql .= " WHERE id = ? AND role = 'guru'";
                $params[] = $id;

                try {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success = "Data Guru berhasil diperbarui!";
                } catch (PDOException $e) {
                    $error = "Gagal memperbarui data guru: " . $e->getMessage();
                }
            } elseif ($_POST['action'] == 'delete') {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'guru'");
                $stmt->execute([$id]);
                $success = "Data Guru berhasil dihapus!";
            }
        }
    }
}

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users WHERE role = 'guru'";
$params = [];
if ($search) {
    $query .= " AND (nama_lengkap LIKE ? OR nis_nip LIKE ? OR kelas LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$query .= " ORDER BY nama_lengkap";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$teachers = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru - SI-SONYA</title>
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
                    <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Manajemen Guru</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Kelola Seluruh Data Pengajar</p>
                </div>
                <button onclick="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition shadow-xl shadow-blue-100">+ Guru Baru</button>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-2xl text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-2xl text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form action="" method="GET" class="relative max-w-md">
                <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Cari Nama atau NIP/NIK..."
                    class="w-full pl-12 pr-4 py-4 rounded-2xl border-none shadow-sm focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                <div class="absolute left-4 top-4 text-gray-400">🔍</div>
            </form>

            <div class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-x-auto">
                <table class="w-full text-left min-w-[800px]">
                    <thead class="bg-gray-50/50 border-b border-gray-100">
                        <tr>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">NIP / NIK</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Nama Lengkap</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Keterangan</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">TTL</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($teachers)): ?>
                            <tr><td colspan="5" class="px-8 py-20 text-center text-gray-400 italic font-medium">Data tidak ditemukan.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($teachers as $t): ?>
                            <tr class="hover:bg-gray-50/50 transition group">
                                <td class="px-8 py-6 text-blue-600 font-mono text-sm font-bold"><?php echo e($t['nis_nip']); ?></td>
                                <td class="px-8 py-6 font-black text-gray-800 uppercase tracking-tight"><?php echo e($t['nama_lengkap']); ?></td>
                                <td class="px-8 py-6"><span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-[10px] font-black uppercase"><?php echo e($t['kelas'] ?? '-'); ?></span></td>
                                <td class="px-8 py-6 text-gray-500 text-xs italic">
                                    <?php echo $t['tempat_lahir'] ? e($t['tempat_lahir']) : '-'; ?>,
                                    <?php echo $t['tanggal_lahir'] ? date('d/m/Y', strtotime($t['tanggal_lahir'])) : '-'; ?>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex justify-center gap-3">
                                        <button onclick='openModal("edit", <?php echo json_encode($t); ?>)' class="w-10 h-10 flex items-center justify-center bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition shadow-sm">✏️</button>
                                        <form action="" method="POST" onsubmit="return confirm('Hapus data guru ini?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo e($t['id']); ?>">
                                            <button type="submit" class="w-10 h-10 flex items-center justify-center bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition shadow-sm">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="modal-guru" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-lg rounded-[40px] p-10 shadow-2xl overflow-y-auto max-h-[90vh]">
                    <h3 id="modal-title" class="text-2xl font-black text-indigo-900 mb-8 uppercase tracking-tighter">Guru Baru</h3>
                    <form action="" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" id="input-action" value="add_guru">
                        <input type="hidden" name="id" id="input-id">

                        <div>
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">NIP / NIK Guru</label>
                            <input type="text" name="nip" id="input-nip" required class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Nama Lengkap & Gelar</label>
                            <input type="text" name="nama_lengkap" id="input-nama" required class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" id="input-tempat" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" id="input-tanggal" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Keterangan (Mata Pelajaran)</label>
                            <input type="text" name="kelas" id="input-kelas" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Kata Sandi Baru (Opsional)</label>
                            <input type="password" name="password" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                        </div>
                        <div class="flex gap-4 pt-4">
                            <button type="button" onclick="closeModal()" class="flex-grow py-5 bg-gray-50 text-gray-500 rounded-[25px] font-black uppercase text-xs tracking-widest transition">Batal</button>
                            <button type="submit" id="btn-submit" class="flex-grow py-5 bg-blue-600 text-white rounded-[25px] font-black uppercase text-xs tracking-widest shadow-xl shadow-blue-100 transition transform active:scale-95">Simpan Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>

    <script>
        function openModal(mode, data = null) {
            const modal = document.getElementById('modal-guru');
            if (mode === 'edit' && data) {
                document.getElementById('modal-title').innerText = 'Edit Data Guru';
                document.getElementById('input-action').value = 'edit_guru';
                document.getElementById('input-id').value = data.id;
                document.getElementById('input-nip').value = data.nis_nip;
                document.getElementById('input-nama').value = data.nama_lengkap;
                document.getElementById('input-tempat').value = data.tempat_lahir || '';
                document.getElementById('input-tanggal').value = data.tanggal_lahir || '';
                document.getElementById('input-kelas').value = data.kelas || '';
            } else {
                document.getElementById('modal-title').innerText = 'Guru Baru';
                document.getElementById('input-action').value = 'add_guru';
                document.getElementById('input-id').value = '';
                document.getElementById('input-nip').value = '';
                document.getElementById('input-nama').value = '';
                document.getElementById('input-tempat').value = '';
                document.getElementById('input-tanggal').value = '';
                document.getElementById('input-kelas').value = '';
            }
            modal.classList.remove('hidden');
        }
        function closeModal() { document.getElementById('modal-guru').classList.add('hidden'); }
    </script>
</body>
</html>
