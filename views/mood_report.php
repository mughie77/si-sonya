<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in() || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch all mood reports with user details
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
        case 3: return 'Biasa';
        case 2: return 'Sedih';
        case 1: return 'Sangat Sedih';
        default: return 'Tidak Diketahui';
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
<body class="bg-gray-50 flex min-h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-grow flex flex-col overflow-hidden">
        <header class="bg-white shadow-sm border-b p-4 px-8 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Laporan Kesehatan Mental & Mood</h2>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-[10px] font-bold uppercase tracking-widest">Data Real-time</span>
            </div>
        </header>

        <div class="p-8 overflow-y-auto">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-x-auto">
                <table class="w-full text-left min-w-[1000px]">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 font-bold text-indigo-900">Tanggal</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">User</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">Identitas</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">Mood</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($moods)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic font-medium">Belum ada data mood masuk.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($moods as $m): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-800"><?php echo date('d M Y', strtotime($m['tanggal'])); ?></div>
                                    <div class="text-[10px] text-gray-400"><?php echo date('H:i', strtotime($m['created_at'])); ?> WIB</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800"><?php echo e($m['nama_lengkap']); ?></div>
                                    <div class="flex gap-1 mt-1">
                                        <span class="px-2 py-0.5 <?php echo ($m['role'] == 'siswa') ? 'bg-green-50 text-green-600' : 'bg-blue-50 text-blue-600'; ?> rounded-md text-[9px] font-black uppercase"><?php echo e($m['role']); ?></span>
                                        <?php if ($m['kelas']): ?>
                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-md text-[9px] font-black uppercase"><?php echo e($m['kelas']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-500"><?php echo e($m['nis_nip'] ?? '-'); ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl"><?php echo getMoodEmoji($m['mood_score']); ?></span>
                                        <span class="text-[10px] font-extrabold uppercase text-gray-600"><?php echo getMoodText($m['mood_score']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs text-gray-600 italic leading-relaxed max-w-sm">
                                        <?php echo $m['catatan'] ? '"' . e($m['catatan']) . '"' : '<span class="text-gray-300">Tidak ada catatan</span>'; ?>
                                    </p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
