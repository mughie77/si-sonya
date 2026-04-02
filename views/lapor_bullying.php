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
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Terjadi kesalahan keamanan (CSRF Token invalid).";
    } else {
        $terlapor = trim($_POST['terlapor_nama']);
        $tanggal = $_POST['tanggal_kejadian'];
        $lokasi = trim($_POST['lokasi']);
        $deskripsi = trim($_POST['deskripsi']);
        $pelapor_id = $_SESSION['user_id'];

        $foto_name = null;
        if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['bukti_foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $foto_name = bin2hex(random_bytes(10)) . '.' . $ext;
                move_uploaded_file($_FILES['bukti_foto']['tmp_name'], '../uploads/' . $foto_name);
            } else {
                $error = "Format file tidak diizinkan!";
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO bullying_reports (pelapor_id, terlapor_nama, tanggal_kejadian, lokasi, deskripsi, bukti_foto) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$pelapor_id, $terlapor, $tanggal, $lokasi, $deskripsi, $foto_name])) {
                $success = "Laporan berhasil dikirim! Kerahasiaan Anda terjamin.";
            } else {
                $error = "Terjadi kesalahan saat mengirim laporan.";
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM bullying_reports WHERE pelapor_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$riwayat = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapor Bullying - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .bg-zen { background-color: #E2F2FF; }
        .card-zen { background: white; border-radius: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
    </style>
</head>
<body class="<?php echo ($role == 'admin') ? 'bg-gray-50' : 'bg-zen'; ?> min-h-screen <?php echo ($role == 'admin') ? 'flex' : ''; ?>">
    <?php if ($role == 'admin') include 'includes/sidebar.php'; ?>

    <div class="flex-grow">
        <?php include 'includes/header.php'; ?>

        <main class="max-w-4xl mx-auto p-6 md:p-10 space-y-8 pb-32">
            <div class="card-zen p-8 md:p-10">
                <h3 class="text-2xl font-black text-indigo-900 mb-6 uppercase tracking-tighter">Formulir Laporan Bullying</h3>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-2xl text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-2xl text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div>
                        <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Nama Terlapor (Boleh Inisial)</label>
                        <input type="text" name="terlapor_nama" placeholder="Siapa yang melakukan?"
                            class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-indigo-100 outline-none transition duration-200">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Tanggal Kejadian</label>
                            <input type="date" name="tanggal_kejadian" required
                                class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-indigo-100 outline-none transition duration-200">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Lokasi</label>
                            <input type="text" name="lokasi" required placeholder="Contoh: Kantin, Kelas"
                                class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-indigo-100 outline-none transition duration-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Kronologi / Deskripsi</label>
                        <textarea name="deskripsi" required rows="4" placeholder="Ceritakan apa yang terjadi secara detail..."
                            class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-indigo-100 outline-none transition duration-200"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Unggah Bukti Foto (Opsional)</label>
                        <input type="file" name="bukti_foto"
                            class="w-full px-4 py-3 rounded-[25px] bg-gray-50 outline-none transition duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200">
                    </div>
                    <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-black py-5 rounded-[30px] shadow-xl shadow-red-100 transition-all transform active:scale-95 uppercase tracking-widest text-xs">
                        Kirim Laporan Sekarang
                    </button>
                </form>
            </div>

            <div class="card-zen p-8 md:p-10">
                <h3 class="text-xl font-black text-indigo-900 mb-8 uppercase tracking-tighter">Riwayat Laporan Anda</h3>
                <div class="space-y-4">
                    <?php if (empty($riwayat)): ?>
                        <p class="text-gray-400 text-center py-10 italic">Belum ada riwayat laporan.</p>
                    <?php endif; ?>
                    <?php foreach ($riwayat as $r): ?>
                        <div class="p-6 bg-gray-50/50 rounded-[30px] border border-white hover:bg-white hover:shadow-xl transition-all">
                             <div class="flex justify-between items-start mb-4">
                                <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest"><?php echo e($r['tanggal_kejadian']); ?></span>
                                <?php
                                    $status_color = 'bg-amber-100 text-amber-700';
                                    if ($r['status'] == 'proses') $status_color = 'bg-blue-100 text-blue-700';
                                    if ($r['status'] == 'selesai') $status_color = 'bg-green-100 text-green-700';
                                ?>
                                <span class="px-3 py-1 <?php echo $status_color; ?> rounded-full text-[9px] font-black uppercase">
                                    <?php echo e($r['status']); ?>
                                </span>
                             </div>
                             <h4 class="font-black text-gray-800 uppercase tracking-tight"><?php echo e($r['terlapor_nama'] ?: 'Anonim'); ?></h4>
                             <p class="text-gray-500 text-xs line-clamp-2 mt-2 italic">"<?php echo e($r['deskripsi']); ?>"</p>
                             <div class="mt-4 pt-4 border-t border-gray-100 flex items-center text-[10px] text-gray-400 font-bold uppercase">
                                <span>📍 <?php echo e($r['lokasi']); ?></span>
                             </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>
</body>
</html>
