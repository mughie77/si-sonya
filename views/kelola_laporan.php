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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Terjadi kesalahan keamanan (CSRF Token invalid).";
    } else {
        if (isset($_POST['update_status'])) {
            $type = $_POST['type'];
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

$bullying = $pdo->query("SELECT br.*, u.nama_lengkap as pelapor FROM bullying_reports br JOIN users u ON br.pelapor_id = u.id ORDER BY br.created_at DESC")->fetchAll();
$facilities = $pdo->query("SELECT fr.*, u.nama_lengkap as pelapor FROM facility_reports fr JOIN users u ON fr.pelapor_id = u.id ORDER BY fr.created_at DESC")->fetchAll();
$feedbacks = $pdo->query("SELECT fb.*, u.nama_lengkap as pengirim FROM feedback fb JOIN users u ON fb.user_id = u.id ORDER BY fb.created_at DESC")->fetchAll();

$csrf_token = generate_csrf_token();
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen <?php echo ($role == 'admin') ? 'flex' : ''; ?>">
    <?php if ($role == 'admin') include 'includes/sidebar.php'; ?>

    <div class="flex-grow">
        <?php include 'includes/header.php'; ?>

        <main class="max-w-6xl mx-auto p-6 md:p-10 space-y-12 pb-32">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
                <div>
                    <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Manajemen Laporan</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Pantau & Tindak Lanjuti Aduan</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="#bullying" class="px-4 py-2 bg-red-50 text-red-600 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-red-600 hover:text-white transition shadow-sm">Bullying</a>
                    <a href="#fasilitas" class="px-4 py-2 bg-amber-50 text-amber-600 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-600 hover:text-white transition shadow-sm">Fasilitas</a>
                    <a href="#feedback" class="px-4 py-2 bg-purple-50 text-purple-600 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-purple-600 hover:text-white transition shadow-sm">Feedback</a>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-2xl text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-2xl text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <!-- Bullying Section -->
            <section id="bullying">
                <h3 class="text-xl font-black text-indigo-900 mb-6 uppercase tracking-tighter flex items-center gap-3">
                    <span class="w-8 h-8 bg-red-100 rounded-xl flex items-center justify-center text-red-600">🛡️</span>
                    Laporan Perundungan (Bullying)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php if (empty($bullying)): ?>
                        <div class="md:col-span-2 bg-white p-12 rounded-[40px] shadow-sm border border-gray-100 text-center text-gray-400 font-bold uppercase text-xs tracking-widest">Belum ada laporan masuk.</div>
                    <?php endif; ?>
                    <?php foreach ($bullying as $b): ?>
                        <div class="bg-white p-8 rounded-[40px] shadow-sm border border-gray-50 hover:border-red-100 transition-all group relative overflow-hidden">
                            <div class="flex justify-between items-start mb-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center font-black text-indigo-900 border border-gray-100 uppercase"><?php echo e(substr($b['pelapor'], 0, 1)); ?></div>
                                    <div>
                                        <h4 class="font-black text-gray-800 uppercase tracking-tight text-sm"><?php echo e($b['terlapor_nama']); ?></h4>
                                        <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Pelapor: <?php echo e($b['pelapor']); ?></p>
                                    </div>
                                </div>
                                <span class="text-[9px] text-gray-400 font-black uppercase"><?php echo e(date('d/m/y', strtotime($b['created_at']))); ?></span>
                            </div>
                            <div class="bg-red-50/30 p-5 rounded-3xl mb-6 min-h-[100px]">
                                <p class="text-gray-600 text-xs italic font-medium leading-relaxed">"<?php echo e($b['deskripsi']); ?>"</p>
                            </div>
                            <div class="flex justify-between items-center mt-auto pt-6 border-t border-gray-50">
                                <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">📍 <?php echo e($b['lokasi']); ?></span>
                                <form action="" method="POST" class="flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="type" value="bullying">
                                    <input type="hidden" name="id" value="<?php echo e($b['id']); ?>">
                                    <select name="status" onchange="this.form.submit()"
                                        class="text-[9px] font-black uppercase py-2 px-4 rounded-full outline-none focus:ring-2 focus:ring-indigo-100 cursor-pointer shadow-sm <?php echo ($b['status'] == 'pending') ? 'bg-amber-100 text-amber-600' : (($b['status'] == 'proses') ? 'bg-blue-100 text-blue-600' : 'bg-emerald-100 text-emerald-700'); ?>">
                                        <option value="pending" <?php echo ($b['status'] == 'pending') ? 'selected' : ''; ?>>Tunda</option>
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
                <h3 class="text-xl font-black text-indigo-900 mb-6 uppercase tracking-tighter flex items-center gap-3">
                    <span class="w-8 h-8 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600">🏗️</span>
                    Laporan Fasilitas Sekolah
                </h3>
                <div class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-x-auto">
                    <table class="w-full text-left min-w-[800px]">
                        <thead class="bg-gray-50/50 border-b border-gray-100">
                            <tr>
                                <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Fasilitas</th>
                                <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Pelapor</th>
                                <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Kerusakan</th>
                                <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($facilities as $f): ?>
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-8 py-6 font-black text-gray-800 uppercase tracking-tight text-sm"><?php echo e($f['nama_fasilitas']); ?></td>
                                    <td class="px-8 py-6 text-gray-500 text-xs font-bold"><?php echo e($f['pelapor']); ?></td>
                                    <td class="px-8 py-6 text-gray-400 text-xs italic font-medium max-w-xs truncate"><?php echo e($f['deskripsi_kerusakan']); ?></td>
                                    <td class="px-8 py-6">
                                        <form action="" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="update_status" value="1">
                                            <input type="hidden" name="type" value="facility">
                                            <input type="hidden" name="id" value="<?php echo e($f['id']); ?>">
                                            <select name="status" onchange="this.form.submit()"
                                                class="text-[9px] font-black uppercase py-2 px-4 rounded-full outline-none focus:ring-2 focus:ring-indigo-100 shadow-sm <?php echo ($f['status'] == 'pending') ? 'bg-amber-100 text-amber-600' : (($f['status'] == 'proses') ? 'bg-blue-100 text-blue-600' : 'bg-emerald-100 text-emerald-700'); ?>">
                                                <option value="pending" <?php echo ($f['status'] == 'pending') ? 'selected' : ''; ?>>Tunda</option>
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
                <h3 class="text-xl font-black text-indigo-900 mb-6 uppercase tracking-tighter flex items-center gap-3">
                    <span class="w-8 h-8 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600">💬</span>
                    Kotak Feedback & Saran
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($feedbacks as $fb): ?>
                        <div class="bg-white p-8 rounded-[40px] shadow-sm border border-gray-50 border-l-8 border-l-purple-500 hover:shadow-md transition">
                            <p class="text-gray-700 text-xs leading-relaxed mb-6 font-medium italic">"<?php echo e($fb['isi_feedback']); ?>"</p>
                            <div class="flex justify-between items-center text-[9px] font-black text-indigo-400 uppercase tracking-widest pt-4 border-t border-gray-50">
                                <span>👤 <?php echo e($fb['pengirim']); ?></span>
                                <span><?php echo e(date('d/m/y', strtotime($fb['created_at']))); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>
</body>
</html>
