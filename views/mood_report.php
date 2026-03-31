<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in() || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Ambil laporan mood dengan detail user
$moods = $pdo->query("
    SELECT m.*, u.nama_lengkap, u.role, u.kelas, u.nis_nip
    FROM mood_tracking m
    JOIN users u ON m.user_id = u.id
    ORDER BY m.tanggal DESC, m.created_at DESC
")->fetchAll();

function getMoodEmoji($score) {
    switch ($score) {
        case 5: return '🤩';
        case 4: return '😊';
        case 3: return '😐';
        case 2: return '😟';
        case 1: return '😢';
        default: return '❓';
    }
}

function getMoodText($score) {
    switch ($score) {
        case 5: return 'Sangat Senang';
        case 4: return 'Senang';
        case 3: return 'Biasa Saja';
        case 2: return 'Cemas/Sedih';
        case 1: return 'Sangat Sedih';
        default: return '???';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Mood - SI-SONYA Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="max-w-6xl mx-auto p-6 md:p-10 space-y-8 pb-32">
        <div class="flex justify-between items-end">
            <div>
                <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Laporan Mood</h2>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Kesehatan Mental Warga Sekolah</p>
            </div>
        </div>

        <div class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-x-auto">
            <table class="w-full text-left min-w-[900px]">
                <thead class="bg-gray-50/50 border-b border-gray-100">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Tanggal & Waktu</th>
                        <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Pengguna</th>
                        <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest text-center">Mood</th>
                        <th class="px-8 py-5 text-[10px] font-black text-indigo-900 uppercase tracking-widest">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($moods)): ?>
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center text-gray-400 italic">Belum ada data mood masuk.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($moods as $m): ?>
                        <tr class="hover:bg-gray-50/50 transition group">
                            <td class="px-8 py-6">
                                <div class="text-sm font-bold text-gray-800"><?php echo date('d M Y', strtotime($m['tanggal'])); ?></div>
                                <div class="text-[10px] text-gray-400 font-bold"><?php echo date('H:i', strtotime($m['created_at'])); ?> WIB</div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="font-black text-gray-800 uppercase tracking-tight"><?php echo e($m['nama_lengkap']); ?></div>
                                <div class="flex gap-2 mt-1">
                                    <span class="px-2 py-0.5 <?php echo ($m['role'] == 'siswa') ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600'; ?> rounded-md text-[9px] font-black uppercase"><?php echo e($m['role']); ?></span>
                                    <span class="text-[10px] text-gray-400 font-mono"><?php echo e($m['nis_nip']); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <div class="inline-flex flex-col items-center">
                                    <span class="text-3xl mb-1 group-hover:scale-125 transition"><?php echo getMoodEmoji($m['mood_score']); ?></span>
                                    <span class="text-[9px] font-black uppercase text-gray-500"><?php echo getMoodText($m['mood_score']); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <p class="text-xs text-gray-600 italic leading-relaxed max-w-sm">
                                    <?php echo $m['catatan'] ? '"' . e($m['catatan']) . '"' : '<span class="text-gray-300">Kosong</span>'; ?>
                                </p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php include 'includes/footer_nav.php'; ?>
</body>
</html>
