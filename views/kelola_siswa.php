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
            if ($_POST['action'] == 'add_student') {
                $role = 'siswa';
                $nama = trim($_POST['nama_lengkap']);
                $nis = trim($_POST['nis']);
                $username = $nis;
                $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
                $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
                $kelas = trim($_POST['kelas'] ?? '');
                $kontak1 = trim($_POST['kontak_darurat_1'] ?? '');
                $kontak2 = trim($_POST['kontak_darurat_2'] ?? '');

                $password_plain = !empty($_POST['password']) ? $_POST['password'] : $nis;
                $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role, nis_nip, kelas, tempat_lahir, tanggal_lahir, kontak_darurat_1, kontak_darurat_2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $password_hash, $nama, $role, $nis, $kelas, $tempat_lahir, $tanggal_lahir, $kontak1, $kontak2]);
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
                $kontak1 = trim($_POST['kontak_darurat_1'] ?? '');
                $kontak2 = trim($_POST['kontak_darurat_2'] ?? '');

                $sql = "UPDATE users SET username = ?, nama_lengkap = ?, nis_nip = ?, kelas = ?, tempat_lahir = ?, tanggal_lahir = ?, kontak_darurat_1 = ?, kontak_darurat_2 = ?";
                $params = [$username, $nama, $nis, $kelas, $tempat_lahir, $tanggal_lahir, $kontak1, $kontak2];

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

// Pagination & Filter Setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$f_kelas = $_GET['f_kelas'] ?? '';

$query_base = "FROM users WHERE role = 'siswa'";
$params = [];

if ($search) {
    $query_base .= " AND (nama_lengkap LIKE ? OR nis_nip LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($f_kelas) {
    $query_base .= " AND kelas = ?";
    $params[] = $f_kelas;
}

// Get Total for Pagination
$total_stmt = $pdo->prepare("SELECT COUNT(*) " . $query_base);
$total_stmt->execute($params);
$total_items = $total_stmt->fetchColumn();
$total_pages = ceil($total_items / $limit);

// Get Students
$sql = "SELECT * " . $query_base . " ORDER BY kelas, nama_lengkap LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get unique classes for filter
$classes = $pdo->query("SELECT DISTINCT kelas FROM users WHERE role = 'siswa' AND kelas IS NOT NULL ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

$csrf_token = generate_csrf_token();
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen <?php echo ($role == 'admin') ? 'flex' : ''; ?>">
    <?php if ($role == 'admin') include 'includes/sidebar.php'; ?>

    <div class="flex-grow min-w-0">
        <?php include 'includes/header.php'; ?>

        <main class="max-w-6xl mx-auto p-6 md:p-10 space-y-8 pb-32">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                <div>
                    <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Manajemen Siswa</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Kelola Seluruh Data Murid</p>
                </div>
                <button onclick="openModal('add')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition shadow-xl shadow-indigo-100">+ Siswa Baru</button>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-2xl text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-2xl text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="flex flex-col md:flex-row gap-4">
                <!-- Search -->
                <form action="" method="GET" class="relative flex-grow">
                    <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Cari Nama atau NIS..."
                        class="w-full pl-12 pr-4 py-4 rounded-2xl border-none shadow-sm focus:ring-2 focus:ring-indigo-100 outline-none transition duration-200">
                    <div class="absolute left-4 top-4 text-gray-400">🔍</div>
                    <?php if ($f_kelas): ?><input type="hidden" name="f_kelas" value="<?php echo e($f_kelas); ?>"><?php endif; ?>
                </form>

                <!-- Filter -->
                <form action="" method="GET" class="w-full md:w-64">
                    <select name="f_kelas" onchange="this.form.submit()" class="w-full px-6 py-4 rounded-2xl bg-white border-none shadow-sm text-xs font-black uppercase tracking-widest focus:ring-2 focus:ring-indigo-100 outline-none cursor-pointer">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo e($c); ?>" <?php echo ($f_kelas == $c) ? 'selected' : ''; ?>><?php echo e($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($search): ?><input type="hidden" name="search" value="<?php echo e($search); ?>"><?php endif; ?>
                </form>
            </div>

            <div class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-x-auto custom-scrollbar">
                <table class="w-full text-left min-w-[1000px]">
                    <thead class="bg-gray-50/50 border-b border-gray-100">
                        <tr>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Identitas</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Kelas</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">TTL</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Kontak Darurat</th>
                            <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($students)): ?>
                            <tr><td colspan="5" class="px-8 py-20 text-center text-gray-400 italic font-medium uppercase text-xs tracking-widest">Data tidak ditemukan.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($students as $s): ?>
                            <tr class="hover:bg-gray-50/50 transition group">
                                <td class="px-8 py-6">
                                    <div class="font-black text-gray-800 uppercase tracking-tight"><?php echo e($s['nama_lengkap']); ?></div>
                                    <div class="text-[10px] text-indigo-600 font-mono font-bold"><?php echo e($s['nis_nip']); ?></div>
                                </td>
                                <td class="px-8 py-6"><span class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-[10px] font-black uppercase"><?php echo e($s['kelas'] ?? '-'); ?></span></td>
                                <td class="px-8 py-6 text-gray-500 text-xs italic">
                                    <?php echo $s['tempat_lahir'] ? e($s['tempat_lahir']) : '-'; ?>,
                                    <?php echo $s['tanggal_lahir'] ? date('d/m/Y', strtotime($s['tanggal_lahir'])) : '-'; ?>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="text-[10px] font-bold text-gray-400">1: <?php echo $s['kontak_darurat_1'] ?: '<span class="italic">Belum set</span>'; ?></div>
                                    <div class="text-[10px] font-bold text-gray-400">2: <?php echo $s['kontak_darurat_2'] ?: '<span class="italic">Belum set</span>'; ?></div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex justify-center gap-3">
                                        <button onclick='openModal("edit", <?php echo json_encode($s); ?>)' class="w-10 h-10 flex items-center justify-center bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-600 hover:text-white transition shadow-sm">✏️</button>
                                        <form action="" method="POST" onsubmit="return confirm('Hapus data siswa ini?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo e($s['id']); ?>">
                                            <button type="submit" class="w-10 h-10 flex items-center justify-center bg-red-50 text-red-600 rounded-xl hover:bg-red-600 hover:text-white transition shadow-sm">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex justify-center items-center gap-2 pb-10">
                <?php
                    $qs = http_build_query(array_filter(['search' => $search, 'f_kelas' => $f_kelas]));
                    $qs = $qs ? "&$qs" : "";
                ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i . $qs; ?>"
                       class="w-10 h-10 flex items-center justify-center rounded-xl font-black text-xs transition <?php echo ($page == $i) ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-gray-400 hover:bg-indigo-50 border border-gray-100'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <!-- Modal Siswa -->
            <div id="modal-student" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[150] hidden flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-2xl rounded-[40px] p-10 shadow-2xl overflow-y-auto max-h-[90vh] custom-scrollbar">
                    <h3 id="modal-title" class="text-2xl font-black text-indigo-900 mb-8 uppercase tracking-tighter">Siswa Baru</h3>
                    <form action="" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" id="input-action" value="add_student">
                        <input type="hidden" name="id" id="input-id">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Nomor Induk Siswa (NIS)</label>
                                <input type="text" name="nis" id="input-nis" required class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-indigo-100 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" id="input-nama" required class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-indigo-100 outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Tempat Lahir</label>
                                <input type="text" name="tempat_lahir" id="input-tempat" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-indigo-100 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" id="input-tanggal" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-indigo-100 outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Kelas</label>
                                <input type="text" name="kelas" id="input-kelas" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-indigo-100 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Kata Sandi (Opsional)</label>
                                <input type="password" name="password" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-indigo-100 outline-none">
                            </div>
                        </div>

                        <div class="p-6 bg-blue-50/50 rounded-[30px] border border-blue-100/50 space-y-4">
                            <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest ml-2">📞 Kontak Darurat (Maks 2)</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="text" name="kontak_darurat_1" id="input-kontak1" placeholder="Nomor Kontak 1" class="w-full px-6 py-4 rounded-[20px] bg-white border-none focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                                <input type="text" name="kontak_darurat_2" id="input-kontak2" placeholder="Nomor Kontak 2" class="w-full px-6 py-4 rounded-[20px] bg-white border-none focus:ring-2 focus:ring-blue-100 outline-none text-sm">
                            </div>
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button type="button" onclick="closeModal()" class="flex-grow py-5 bg-gray-50 text-gray-500 rounded-[25px] font-black uppercase text-xs tracking-widest transition">Batal</button>
                            <button type="submit" id="btn-submit" class="flex-grow py-5 bg-indigo-600 text-white rounded-[25px] font-black uppercase text-xs tracking-widest shadow-xl shadow-indigo-100 transition transform active:scale-95">Simpan Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>

    <script>
        function openModal(mode, data = null) {
            const modal = document.getElementById('modal-student');
            if (mode === 'edit' && data) {
                document.getElementById('modal-title').innerText = 'Edit Data Siswa';
                document.getElementById('input-action').value = 'edit_student';
                document.getElementById('input-id').value = data.id;
                document.getElementById('input-nis').value = data.nis_nip;
                document.getElementById('input-nama').value = data.nama_lengkap;
                document.getElementById('input-tempat').value = data.tempat_lahir || '';
                document.getElementById('input-tanggal').value = data.tanggal_lahir || '';
                document.getElementById('input-kelas').value = data.kelas || '';
                document.getElementById('input-kontak1').value = data.kontak_darurat_1 || '';
                document.getElementById('input-kontak2').value = data.kontak_darurat_2 || '';
            } else {
                document.getElementById('modal-title').innerText = 'Siswa Baru';
                document.getElementById('input-action').value = 'add_student';
                document.getElementById('input-id').value = '';
                document.getElementById('input-nis').value = '';
                document.getElementById('input-nama').value = '';
                document.getElementById('input-tempat').value = '';
                document.getElementById('input-tanggal').value = '';
                document.getElementById('input-kelas').value = '';
                document.getElementById('input-kontak1').value = '';
                document.getElementById('input-kontak2').value = '';
            }
            modal.classList.remove('hidden');
        }
        function closeModal() { document.getElementById('modal-student').classList.add('hidden'); }
    </script>
</body>
</html>
