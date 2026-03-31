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

        $foto_name = null;
        if (isset($_FILES['foto_fasilitas']) && $_FILES['foto_fasilitas']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['foto_fasilitas']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $foto_name = 'fas_' . bin2hex(random_bytes(10)) . '.' . $ext;
                if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
                move_uploaded_file($_FILES['foto_fasilitas']['tmp_name'], '../uploads/' . $foto_name);
            } else { $error = "Format file tidak diizinkan!"; }
        }

        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO facility_reports (pelapor_id, nama_fasilitas, deskripsi_kerusakan, foto_fasilitas) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$pelapor_id, $nama_fasilitas, $deskripsi, $foto_name])) {
                $success = "Laporan fasilitas terkirim! Tim sarpras akan segera mengeceknya.";
            } else { $error = "Terjadi kesalahan saat mengirim laporan."; }
        }
    }
}

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
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="max-w-6xl mx-auto p-6 md:p-10 space-y-8 pb-32">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Lapor Kerusakan</h2>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Bantu Kami Merawat Sekolah</p>
            </div>
            <span class="px-4 py-2 bg-amber-100 text-amber-700 rounded-2xl text-[10px] font-black uppercase tracking-widest">Fasilitas Nyaman</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
            <div class="bg-white p-10 rounded-[40px] shadow-sm border border-gray-100 h-fit">
                <h3 class="text-xl font-black text-indigo-900 mb-8 uppercase tracking-tighter">Detail Kerusakan</h3>

                <?php if ($success): ?>
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-8 rounded-2xl text-amber-700 text-sm font-medium"><?php echo e($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-2xl text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div>
                        <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Nama Fasilitas</label>
                        <input type="text" name="nama_fasilitas" required placeholder="Contoh: AC Ruang Guru, Kursi Kantin"
                            class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Deskripsi Kerusakan</label>
                        <textarea name="deskripsi" required rows="4" placeholder="Jelaskan detail kerusakan yang Anda temui..."
                            class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Lampiran Foto (Opsional)</label>
                        <input type="file" name="foto_fasilitas"
                            class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none outline-none file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-amber-600 file:text-white hover:file:bg-amber-700">
                    </div>
                    <button type="submit"
                        class="w-full bg-amber-600 hover:bg-amber-700 text-white font-black py-5 rounded-[25px] shadow-xl shadow-amber-100 transition transform active:scale-95 uppercase text-xs tracking-widest">
                        Kirim Laporan Kerusakan
                    </button>
                </form>
            </div>

            <!-- Riwayat -->
            <div class="bg-white p-10 rounded-[40px] shadow-sm border border-gray-100">
                <h3 class="text-xl font-black text-indigo-900 mb-8 uppercase tracking-tighter">Status Laporan Anda</h3>
                <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar">
                    <?php if (empty($riwayat)): ?>
                        <div class="text-center py-20">
                            <div class="text-4xl mb-4 opacity-20">🛠️</div>
                            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">Belum ada laporan</p>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($riwayat as $r): ?>
                        <div class="p-6 border border-gray-50 rounded-[30px] bg-gray-50/50 flex items-center gap-6 group hover:bg-white hover:border-blue-100 transition shadow-sm">
                             <div class="w-16 h-16 bg-white rounded-2xl flex-shrink-0 flex items-center justify-center text-indigo-600 font-bold overflow-hidden shadow-sm">
                                <?php if ($r['foto_fasilitas']): ?>
                                    <img src="../uploads/<?php echo e($r['foto_fasilitas']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    🔧
                                <?php endif; ?>
                             </div>
                             <div class="flex-grow">
                                <h4 class="font-black text-gray-800 uppercase tracking-tight text-sm"><?php echo e($r['nama_fasilitas']); ?></h4>
                                <p class="text-gray-400 text-[10px] mt-1 font-bold italic"><?php echo e(date('d M Y, H:i', strtotime($r['created_at']))); ?></p>
                                <?php
                                    $status_class = 'bg-amber-100 text-amber-700';
                                    if ($r['status'] == 'proses') $status_class = 'bg-blue-100 text-blue-700';
                                    if ($r['status'] == 'selesai') $status_class = 'bg-emerald-100 text-emerald-700';
                                ?>
                                <span class="inline-block px-3 py-1 mt-2 <?php echo $status_class; ?> rounded-full text-[9px] font-black uppercase tracking-widest">
                                    <?php echo e($r['status']); ?>
                                </span>
                             </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer_nav.php'; ?>
</body>
</html>
