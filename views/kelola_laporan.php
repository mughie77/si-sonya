<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in() || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'guru')) {
    header("Location: ../index.php");
    exit();
}

$success = '';
$error = '';

// Update Status Laporan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Terjadi kesalahan keamanan (CSRF Token invalid).";
    } else {
        if (isset($_POST['update_status'])) {
            $type = $_POST['type']; // 'bullying' atau 'facility'
            $id = $_POST['id'];
            $status = $_POST['status'];

            $table = ($type == 'bullying') ? 'bullying_reports' : 'facility_reports';
            $stmt = $pdo->prepare("UPDATE $table SET status = ? WHERE id = ?");
            if ($stmt->execute([$status, $id])) {
                $success = "Status laporan berhasil diperbarui!";
            }
        }
    }
}

// Ambil Laporan Bullying
$bullying = $pdo->query("SELECT br.*, u.nama_lengkap as pelapor FROM bullying_reports br JOIN users u ON br.pelapor_id = u.id ORDER BY br.created_at DESC")->fetchAll();

// Ambil Laporan Fasilitas
$facilities = $pdo->query("SELECT fr.*, u.nama_lengkap as pelapor FROM facility_reports fr JOIN users u ON fr.pelapor_id = u.id ORDER BY fr.created_at DESC")->fetchAll();

// Ambil Feedback
$feedbacks = $pdo->query("SELECT fb.*, u.nama_lengkap as pengirim FROM feedback fb JOIN users u ON fb.user_id = u.id ORDER BY fb.created_at DESC")->fetchAll();

$csrf_token = generate_csrf_token();
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - SI-SONYA Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <aside class="w-64 bg-indigo-900 text-white flex-shrink-0 hidden md:flex flex-col shadow-xl">
        <div class="p-6 text-2xl font-bold border-b border-indigo-800 tracking-wider">SI-SONYA</div>
        <nav class="flex-grow p-4 space-y-2">
            <a href="dashboard.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🏠 Dashboard</a>
            <a href="live_monitoring.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">📡 Live View <span class="bg-red-500 text-[10px] px-2 py-0.5 rounded-full animate-pulse tracking-tighter">LIVE</span></a>
            <?php if ($role == 'admin'): ?>
                <a href="kelola_user.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">👥 Kelola User</a>
                <a href="import_data.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">📥 Import Data</a>
                <a href="kelola_laporan.php" class="block py-3 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-700 transition font-medium">📊 Kelola Laporan</a>
                <a href="hash_generator.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🛡️ Hash Generator</a>
            <?php endif; ?>
            <a href="change_password.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition italic">🔑 Ganti Password</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../logout.php" class="block py-3 px-4 rounded-xl bg-red-600 hover:bg-red-700 transition text-center font-bold">Keluar</a>
        </div>
    </aside>

    <main class="flex-grow flex flex-col">
        <header class="bg-white shadow-sm border-b p-4 px-8 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Manajemen Laporan & Feedback</h2>
            <div class="flex space-x-2">
                <a href="#bullying" class="px-4 py-1.5 bg-red-100 text-red-600 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-red-200 transition">Bullying</a>
                <a href="#fasilitas" class="px-4 py-1.5 bg-amber-100 text-amber-600 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-amber-200 transition">Fasilitas</a>
                <a href="#feedback" class="px-4 py-1.5 bg-purple-100 text-purple-600 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-purple-200 transition">Feedback</a>
            </div>
        </header>

        <div class="p-8 space-y-12">
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <!-- Bullying Section -->
            <section id="bullying">
                <h3 class="text-2xl font-bold text-indigo-900 mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center text-red-600 text-xl">🛡️</span>
                    Laporan Bullying
                </h3>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <?php if (empty($bullying)): ?>
                        <div class="xl:col-span-2 bg-white p-10 rounded-3xl shadow-sm border border-gray-100 text-center text-gray-400">Belum ada laporan masuk.</div>
                    <?php endif; ?>
                    <?php foreach ($bullying as $b): ?>
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 hover:border-red-300 transition-all group">
                             <div class="flex justify-between items-start mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center font-bold text-indigo-900"><?php echo e(substr($b['pelapor'], 0, 1)); ?></div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-lg"><?php echo e($b['terlapor_nama']); ?></h4>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Pelapor: <?php echo e($b['pelapor']); ?></p>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-400 font-medium"><?php echo e(date('d M Y', strtotime($b['created_at']))); ?></span>
                             </div>
                             <div class="bg-red-50/30 p-4 rounded-2xl mb-5">
                                <p class="text-gray-600 text-sm italic leading-relaxed line-clamp-3">"<?php echo e($b['deskripsi']); ?>"</p>
                             </div>
                             <div class="flex justify-between items-center mt-auto pt-4 border-t border-gray-50">
                                <span class="text-[10px] font-bold text-gray-400 uppercase">📍 <?php echo e($b['lokasi']); ?></span>
                                <form action="" method="POST" class="flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="type" value="bullying">
                                    <input type="hidden" name="id" value="<?php echo e($b['id']); ?>">
                                    <select name="status" onchange="this.form.submit()"
                                        class="text-[10px] font-extrabold uppercase py-1.5 px-3 rounded-full outline-none focus:ring-2 focus:ring-indigo-200 cursor-pointer <?php echo ($b['status'] == 'pending') ? 'bg-amber-100 text-amber-600' : (($b['status'] == 'proses') ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'); ?>">
                                        <option value="pending" <?php echo ($b['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="proses" <?php echo ($b['status'] == 'proses') ? 'selected' : ''; ?>>Proses</option>
                                        <option value="selesai" <?php echo ($b['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                    </select>
                                </form>
                             </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Fasilitas Section -->
            <section id="fasilitas">
                <h3 class="text-2xl font-bold text-indigo-900 mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 text-xl">🏗️</span>
                    Laporan Fasilitas
                </h3>
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 font-bold text-gray-700">Fasilitas</th>
                                <th class="px-6 py-4 font-bold text-gray-700">Pelapor</th>
                                <th class="px-6 py-4 font-bold text-gray-700">Kerusakan</th>
                                <th class="px-6 py-4 font-bold text-gray-700">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($facilities as $f): ?>
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-4 font-bold text-gray-800"><?php echo e($f['nama_fasilitas']); ?></td>
                                    <td class="px-6 py-4 text-gray-600 text-sm"><?php echo e($f['pelapor']); ?></td>
                                    <td class="px-6 py-4 text-gray-500 text-xs italic max-w-xs truncate"><?php echo e($f['deskripsi_kerusakan']); ?></td>
                                    <td class="px-6 py-4">
                                        <form action="" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="update_status" value="1">
                                            <input type="hidden" name="type" value="facility">
                                            <input type="hidden" name="id" value="<?php echo e($f['id']); ?>">
                                            <select name="status" onchange="this.form.submit()"
                                                class="text-[9px] font-extrabold uppercase py-1 px-3 rounded-full outline-none focus:ring-2 focus:ring-indigo-100 <?php echo ($f['status'] == 'pending') ? 'bg-amber-100 text-amber-600' : (($f['status'] == 'proses') ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'); ?>">
                                                <option value="pending" <?php echo ($f['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="proses" <?php echo ($f['status'] == 'proses') ? 'selected' : ''; ?>>Proses</option>
                                                <option value="selesai" <?php echo ($f['status'] == 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Feedback Section -->
            <section id="feedback">
                <h3 class="text-2xl font-bold text-indigo-900 mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600 text-xl">💬</span>
                    Feedback & Saran
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($feedbacks as $fb): ?>
                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 border-l-4 border-l-purple-500">
                             <p class="text-gray-700 text-sm leading-relaxed mb-4 italic">"<?php echo e($fb['isi_feedback']); ?>"</p>
                             <div class="flex justify-between items-center text-[10px] font-bold text-gray-400 uppercase">
                                <span>👤 <?php echo e($fb['pengirim']); ?></span>
                                <span><?php echo e(date('d/m/y', strtotime($fb['created_at']))); ?></span>
                             </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        </div>
    </main>
</body>
</html>
