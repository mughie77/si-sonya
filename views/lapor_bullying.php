<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $terlapor = $_POST['terlapor_nama'];
    $tanggal = $_POST['tanggal_kejadian'];
    $lokasi = $_POST['lokasi'];
    $deskripsi = $_POST['deskripsi'];
    $pelapor_id = $_SESSION['user_id'];

    // Handle upload foto (simulasi folder uploads)
    $foto_name = null;
    if (isset($_FILES['bukti_foto']) && $_FILES['bukti_foto']['error'] === 0) {
        $foto_name = time() . '_' . $_FILES['bukti_foto']['name'];
        move_uploaded_file($_FILES['bukti_foto']['tmp_name'], '../uploads/' . $foto_name);
    }

    $stmt = $pdo->prepare("INSERT INTO bullying_reports (pelapor_id, terlapor_nama, tanggal_kejadian, lokasi, deskripsi, bukti_foto) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$pelapor_id, $terlapor, $tanggal, $lokasi, $deskripsi, $foto_name])) {
        $success = "Laporan berhasil dikirim! Kerahasiaan Anda terjamin.";
    } else {
        $error = "Terjadi kesalahan saat mengirim laporan.";
    }
}

// Ambil riwayat laporan saya
$stmt = $pdo->prepare("SELECT * FROM bullying_reports WHERE pelapor_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$riwayat = $stmt->fetchAll();
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
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <!-- Sidebar (Same as Dashboard) -->
    <aside class="w-64 bg-indigo-900 text-white flex-shrink-0 hidden md:flex flex-col shadow-xl">
        <div class="p-6 text-2xl font-bold border-b border-indigo-800 tracking-wider">SI-SONYA</div>
        <nav class="flex-grow p-4 space-y-2">
            <a href="dashboard.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🏠 Dashboard</a>
            <a href="lapor_bullying.php" class="block py-3 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-700 transition font-medium">🛡️ Lapor Bullying</a>
            <a href="mood_tracker.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">😊 Mood Tracker</a>
            <a href="lapor_fasilitas.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🏗️ Lapor Fasilitas</a>
            <a href="feedback.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">💬 Kirim Saran</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../logout.php" class="block py-3 px-4 rounded-xl bg-red-600 hover:bg-red-700 transition text-center font-bold">Keluar</a>
        </div>
    </aside>

    <main class="flex-grow flex flex-col">
        <header class="bg-white shadow-sm border-b p-4 px-8 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Lapor Bullying</h2>
            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold uppercase tracking-wide">Privasi Terjamin</span>
        </header>

        <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Form Laporan -->
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="text-2xl font-bold text-indigo-900 mb-6">Formulir Laporan</h3>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg text-green-700 text-sm"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg text-red-700 text-sm"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Terlapor (Opsional/Boleh Inisial)</label>
                        <input type="text" name="terlapor_nama" placeholder="Siapa yang melakukan?"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Kejadian</label>
                            <input type="date" name="tanggal_kejadian" required
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Lokasi</label>
                            <input type="text" name="lokasi" required placeholder="Contoh: Kantin, Kelas"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Deskripsi Kejadian</label>
                        <textarea name="deskripsi" required rows="4" placeholder="Ceritakan apa yang terjadi secara detail..."
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Unggah Bukti (Opsional)</label>
                        <input type="file" name="bukti_foto"
                            class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none transition duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    </div>
                    <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-red-500/30 transition transform hover:-translate-y-1">
                        Kirim Laporan Saya
                    </button>
                </form>
            </div>

            <!-- Riwayat Laporan -->
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="text-2xl font-bold text-indigo-900 mb-6">Riwayat Laporan Anda</h3>
                <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                    <?php if (empty($riwayat)): ?>
                        <p class="text-gray-500 text-center py-10">Belum ada riwayat laporan.</p>
                    <?php endif; ?>
                    <?php foreach ($riwayat as $r): ?>
                        <div class="p-5 border border-gray-100 rounded-2xl bg-gray-50/50 hover:bg-gray-100/80 transition group relative">
                             <div class="flex justify-between items-start mb-3">
                                <span class="text-xs font-bold text-indigo-500 uppercase tracking-widest"><?php echo $r['tanggal_kejadian']; ?></span>
                                <?php
                                    $status_color = 'bg-amber-100 text-amber-700';
                                    if ($r['status'] == 'proses') $status_color = 'bg-blue-100 text-blue-700';
                                    if ($r['status'] == 'selesai') $status_color = 'bg-green-100 text-green-700';
                                ?>
                                <span class="px-3 py-1 <?php echo $status_color; ?> rounded-full text-[10px] font-extrabold uppercase">
                                    <?php echo $r['status']; ?>
                                </span>
                             </div>
                             <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($r['terlapor_nama'] ?: 'Anonim'); ?></h4>
                             <p class="text-gray-600 text-sm line-clamp-2 mt-1"><?php echo htmlspecialchars($r['deskripsi']); ?></p>
                             <div class="mt-3 flex items-center text-[11px] text-gray-400 font-medium">
                                <span>📍 <?php echo htmlspecialchars($r['lokasi']); ?></span>
                             </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
