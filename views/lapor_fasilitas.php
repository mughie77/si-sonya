<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in()) {
    header("Location: ../index.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Terjadi kesalahan keamanan (CSRF Token invalid).";
    } else {
        $nama_fasilitas = trim($_POST['nama_fasilitas']);
        $deskripsi = trim($_POST['deskripsi']);
        $pelapor_id = $_SESSION['user_id'];

        // Handle upload foto
        $foto_name = null;
        if (isset($_FILES['foto_fasilitas']) && $_FILES['foto_fasilitas']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['foto_fasilitas']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $foto_name = 'fas_' . bin2hex(random_bytes(10)) . '.' . $ext;
                move_uploaded_file($_FILES['foto_fasilitas']['tmp_name'], '../uploads/' . $foto_name);
            } else {
                $error = "Format file tidak diizinkan!";
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO facility_reports (pelapor_id, nama_fasilitas, deskripsi_kerusakan, foto_fasilitas) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$pelapor_id, $nama_fasilitas, $deskripsi, $foto_name])) {
                $success = "Laporan fasilitas berhasil dikirim! Tim sarpras akan segera mengeceknya.";
            } else {
                $error = "Terjadi kesalahan saat mengirim laporan.";
            }
        }
    }
}

// Ambil riwayat laporan fasilitas saya
$stmt = $pdo->prepare("SELECT * FROM facility_reports WHERE pelapor_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$riwayat = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapor Fasilitas - SI-SONYA</title>
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
            <h2 class="text-xl font-bold text-gray-800">Lapor Fasilitas Rusak</h2>
            <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold uppercase tracking-wide">Fasilitas Nyaman</span>
        </header>

        <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8 overflow-y-auto">
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 h-fit">
                <h3 class="text-2xl font-bold text-indigo-900 mb-6">Detail Kerusakan</h3>

                <?php if ($success): ?>
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded-lg text-amber-700 text-sm font-medium"><?php echo e($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Fasilitas</label>
                        <input type="text" name="nama_fasilitas" required placeholder="Contoh: Meja Kelas 10A, Kran Toilet"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi Kerusakan</label>
                        <textarea name="deskripsi" required rows="4" placeholder="Ceritakan detail kerusakan yang terjadi..."
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Unggah Foto (Opsional)</label>
                        <input type="file" name="foto_fasilitas"
                            class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none transition duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <button type="submit"
                        class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-amber-500/30 transition transform hover:-translate-y-1">
                        Kirim Laporan Kerusakan
                    </button>
                </form>
            </div>

            <!-- Riwayat -->
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="text-2xl font-bold text-indigo-900 mb-6">Status Laporan Fasilitas</h3>
                <div class="space-y-4 pr-2">
                    <?php if (empty($riwayat)): ?>
                        <p class="text-gray-500 text-center py-10">Belum ada laporan fasilitas.</p>
                    <?php endif; ?>
                    <?php foreach ($riwayat as $r): ?>
                        <div class="p-5 border border-gray-100 rounded-2xl bg-gray-50/50 flex items-center gap-4 group">
                             <div class="w-16 h-16 bg-indigo-100 rounded-xl flex-shrink-0 flex items-center justify-center text-indigo-600 font-bold overflow-hidden">
                                <?php if ($r['foto_fasilitas']): ?>
                                    <img src="../uploads/<?php echo e($r['foto_fasilitas']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    🛠️
                                <?php endif; ?>
                             </div>
                             <div class="flex-grow">
                                <h4 class="font-bold text-gray-800"><?php echo e($r['nama_fasilitas']); ?></h4>
                                <p class="text-gray-500 text-xs mt-1 italic"><?php echo e(date('d M Y', strtotime($r['created_at']))); ?></p>
                                <?php
                                    $status_color = 'text-amber-500';
                                    if ($r['status'] == 'proses') $status_color = 'text-blue-500';
                                    if ($r['status'] == 'selesai') $status_color = 'text-green-500';
                                ?>
                                <p class="text-[10px] font-extrabold uppercase mt-1 <?php echo $status_color; ?>">
                                    Status: <?php echo e($r['status']); ?>
                                </p>
                             </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
