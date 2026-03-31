<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in() || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Filters
$f_role = $_GET['f_role'] ?? '';
$f_mood = $_GET['f_mood'] ?? '';

$query_base = "FROM mood_tracking m JOIN users u ON m.user_id = u.id WHERE 1=1";
$params = [];

if ($f_role) {
    $query_base .= " AND u.role = ?";
    $params[] = $f_role;
}
if ($f_mood) {
    $query_base .= " AND m.mood_score = ?";
    $params[] = (int)$f_mood;
}

// Get total for pagination
$total_stmt = $pdo->prepare("SELECT COUNT(*) " . $query_base);
$total_stmt->execute($params);
$total_items = $total_stmt->fetchColumn();
$total_pages = ceil($total_items / $limit);

// Get data
$sql = "SELECT m.*, u.nama_lengkap, u.role, u.kelas, u.nis_nip " . $query_base . " ORDER BY m.tanggal DESC, m.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$moods = $stmt->fetchAll();

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
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Mood - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen <?php echo ($role == 'admin') ? 'flex' : ''; ?>">
    <?php if ($role == 'admin') include 'includes/sidebar.php'; ?>

    <div class="flex-grow">
        <?php include 'includes/header.php'; ?>

        <main class="max-w-6xl mx-auto p-6 md:p-10 space-y-8 pb-32">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
                <div>
                    <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Laporan Mood</h2>
                    <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Kesehatan Mental Warga Sekolah</p>
                </div>

                <!-- Filter Bar -->
                <form action="" method="GET" class="flex flex-wrap gap-3 bg-white p-4 rounded-[25px] shadow-sm border border-gray-100">
                    <select name="f_role" onchange="this.form.submit()" class="px-4 py-2 rounded-xl bg-gray-50 border-none text-[10px] font-black uppercase tracking-widest focus:ring-2 focus:ring-blue-100 outline-none cursor-pointer">
                        <option value="">Semua Peran</option>
                        <option value="siswa" <?php echo ($f_role == 'siswa') ? 'selected' : ''; ?>>Siswa</option>
                        <option value="guru" <?php echo ($f_role == 'guru') ? 'selected' : ''; ?>>Guru</option>
                    </select>
                    <select name="f_mood" onchange="this.form.submit()" class="px-4 py-2 rounded-xl bg-gray-50 border-none text-[10px] font-black uppercase tracking-widest focus:ring-2 focus:ring-blue-100 outline-none cursor-pointer">
                        <option value="">Semua Mood</option>
                        <option value="5" <?php echo ($f_mood == '5') ? 'selected' : ''; ?>>Sangat Senang</option>
                        <option value="4" <?php echo ($f_mood == '4') ? 'selected' : ''; ?>>Senang</option>
                        <option value="3" <?php echo ($f_mood == '3') ? 'selected' : ''; ?>>Biasa Saja</option>
                        <option value="2" <?php echo ($f_mood == '2') ? 'selected' : ''; ?>>Sedih/Cemas</option>
                        <option value="1" <?php echo ($f_mood == '1') ? 'selected' : ''; ?>>Sangat Sedih</option>
                    </select>
                    <a href="mood_report.php" class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-red-500 hover:bg-red-50 rounded-xl transition">Reset</a>
                </form>
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
                                <td colspan="4" class="px-8 py-20 text-center text-gray-400 italic font-bold uppercase text-xs tracking-widest">Data mood tidak ditemukan.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($moods as $m): ?>
                            <tr class="hover:bg-gray-50/50 transition group">
                                <td class="px-8 py-6">
                                    <div class="text-sm font-bold text-gray-800"><?php echo date('d M Y', strtotime($m['tanggal'])); ?></div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase italic"><?php echo date('H:i', strtotime($m['created_at'])); ?> WIB</div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="font-black text-gray-800 uppercase tracking-tight"><?php echo e($m['nama_lengkap']); ?></div>
                                    <div class="flex gap-2 mt-1">
                                        <span class="px-2 py-0.5 <?php echo ($m['role'] == 'siswa') ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600'; ?> rounded-md text-[9px] font-black uppercase"><?php echo e($m['role']); ?></span>
                                        <span class="text-[10px] text-gray-300 font-mono font-bold"><?php echo e($m['nis_nip']); ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <div class="inline-flex flex-col items-center">
                                        <span class="text-3xl mb-1 group-hover:scale-125 transition"><?php echo getMoodEmoji($m['mood_score']); ?></span>
                                        <span class="text-[9px] font-black uppercase text-gray-500"><?php echo getMoodText($m['mood_score']); ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <p class="text-xs text-gray-600 italic leading-relaxed max-w-sm font-medium">
                                        <?php echo $m['catatan'] ? '"' . e($m['catatan']) . '"' : '<span class="text-gray-300 uppercase font-black text-[10px] tracking-widest">Tidak ada catatan</span>'; ?>
                                    </p>
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
                    $query_string = $_GET;
                    unset($query_string['page']);
                    $qs = http_build_query($query_string);
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
        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>
</body>
</html>
