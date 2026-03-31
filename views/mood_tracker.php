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
        $mood = $_POST['mood_score'];
        $catatan = $_POST['catatan'];
        $user_id = $_SESSION['user_id'];

        // Cek apakah hari ini sudah isi
        $check = $pdo->prepare("SELECT id FROM mood_tracking WHERE user_id = ? AND tanggal = CURRENT_DATE");
        $check->execute([$user_id]);

        if ($check->fetch()) {
            $stmt = $pdo->prepare("UPDATE mood_tracking SET mood_score = ?, catatan = ? WHERE user_id = ? AND tanggal = CURRENT_DATE");
            if ($stmt->execute([$mood, $catatan, $user_id])) {
                $success = "Mood Anda hari ini berhasil diperbarui!";
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO mood_tracking (user_id, mood_score, catatan) VALUES (?, ?, ?)");
            if ($stmt->execute([$user_id, $mood, $catatan])) {
                $success = "Terima kasih sudah berbagi perasaanmu hari ini! ✨";
            }
        }
    }
}

// Ambil riwayat mood 7 hari terakhir
$stmt = $pdo->prepare("SELECT * FROM mood_tracking WHERE user_id = ? ORDER BY tanggal DESC LIMIT 7");
$stmt->execute([$_SESSION['user_id']]);
$riwayat = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood Tracker - SI-SONYA</title>
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
            <a href="lapor_bullying.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🛡️ Lapor Bullying</a>
            <a href="mood_tracker.php" class="block py-3 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-700 transition font-medium">😊 Mood Tracker</a>
            <a href="lapor_fasilitas.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🏗️ Lapor Fasilitas</a>
            <a href="feedback.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">💬 Kirim Saran</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../logout.php" class="block py-3 px-4 rounded-xl bg-red-600 hover:bg-red-700 transition text-center font-bold">Keluar</a>
        </div>
    </aside>

    <main class="flex-grow flex flex-col">
        <header class="bg-white shadow-sm border-b p-4 px-8 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Mood Tracker</h2>
            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase tracking-wide">Stay Happy</span>
        </header>

        <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-10 rounded-3xl shadow-sm border border-gray-100 text-center">
                <h3 class="text-3xl font-extrabold text-indigo-900 mb-2">Apa Perasaanmu Hari Ini?</h3>
                <p class="text-gray-500 mb-10">Mengenali perasaanmu adalah langkah awal menuju kebahagiaan.</p>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-10 rounded-lg text-green-700 text-sm text-left font-medium"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-10 rounded-lg text-red-700 text-sm text-left font-medium"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-10">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="flex justify-around items-center gap-2">
                        <label class="cursor-pointer group flex flex-col items-center">
                            <input type="radio" name="mood_score" value="1" class="hidden peer" required>
                            <span class="text-5xl grayscale peer-checked:grayscale-0 group-hover:grayscale-0 transform transition peer-checked:scale-125 group-active:scale-110 mb-2">😢</span>
                            <span class="text-[10px] font-bold text-gray-400 group-hover:text-indigo-600 peer-checked:text-indigo-600">Sangat Sedih</span>
                        </label>
                        <label class="cursor-pointer group flex flex-col items-center">
                            <input type="radio" name="mood_score" value="2" class="hidden peer">
                            <span class="text-5xl grayscale peer-checked:grayscale-0 group-hover:grayscale-0 transform transition peer-checked:scale-125 group-active:scale-110 mb-2">😟</span>
                            <span class="text-[10px] font-bold text-gray-400 group-hover:text-indigo-600 peer-checked:text-indigo-600">Sedih</span>
                        </label>
                        <label class="cursor-pointer group flex flex-col items-center">
                            <input type="radio" name="mood_score" value="3" class="hidden peer">
                            <span class="text-5xl grayscale peer-checked:grayscale-0 group-hover:grayscale-0 transform transition peer-checked:scale-125 group-active:scale-110 mb-2">😐</span>
                            <span class="text-[10px] font-bold text-gray-400 group-hover:text-indigo-600 peer-checked:text-indigo-600">Biasa</span>
                        </label>
                        <label class="cursor-pointer group flex flex-col items-center">
                            <input type="radio" name="mood_score" value="4" class="hidden peer">
                            <span class="text-5xl grayscale peer-checked:grayscale-0 group-hover:grayscale-0 transform transition peer-checked:scale-125 group-active:scale-110 mb-2">😊</span>
                            <span class="text-[10px] font-bold text-gray-400 group-hover:text-indigo-600 peer-checked:text-indigo-600">Senang</span>
                        </label>
                        <label class="cursor-pointer group flex flex-col items-center">
                            <input type="radio" name="mood_score" value="5" class="hidden peer">
                            <span class="text-5xl grayscale peer-checked:grayscale-0 group-hover:grayscale-0 transform transition peer-checked:scale-125 group-active:scale-110 mb-2">🤩</span>
                            <span class="text-[10px] font-bold text-gray-400 group-hover:text-indigo-600 peer-checked:text-indigo-600">Sangat Senang</span>
                        </label>
                    </div>

                    <div class="text-left">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Ada cerita untuk hari ini? (Opsional)</label>
                        <textarea name="catatan" rows="3" placeholder="Apa yang membuatmu merasa demikian?"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-indigo-500/30 transition transform hover:-translate-y-1">
                        Simpan Perasaan Saya
                    </button>
                </form>
            </div>

            <!-- Riwayat Mood -->
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="text-2xl font-bold text-indigo-900 mb-6">Jejak Perasaan Anda</h3>
                <div class="space-y-4">
                    <?php if (empty($riwayat)): ?>
                        <p class="text-gray-500 text-center py-10">Belum ada riwayat mood.</p>
                    <?php endif; ?>
                    <?php foreach ($riwayat as $r): ?>
                        <div class="p-5 border border-gray-100 rounded-2xl flex items-center gap-6 hover:bg-gray-50 transition">
                            <div class="text-4xl">
                                <?php
                                    if ($r['mood_score'] == 5) echo "🤩";
                                    else if ($r['mood_score'] == 4) echo "😊";
                                    else if ($r['mood_score'] == 3) echo "😐";
                                    else if ($r['mood_score'] == 2) echo "😟";
                                    else echo "😢";
                                ?>
                            </div>
                            <div class="flex-grow">
                                <h4 class="font-bold text-gray-800">
                                    <?php
                                        if ($r['mood_score'] == 5) echo "Luar Biasa!";
                                        else if ($r['mood_score'] == 4) echo "Senang";
                                        else if ($r['mood_score'] == 3) echo "Biasa Saja";
                                        else if ($r['mood_score'] == 2) echo "Kurang Baik";
                                        else echo "Sangat Sedih";
                                    ?>
                                </h4>
                                <p class="text-gray-500 text-xs italic mt-1 line-clamp-1">"<?php echo e($r['catatan'] ?: 'Tidak ada catatan'); ?>"</p>
                            </div>
                            <div class="text-right text-[10px] font-bold text-indigo-400 uppercase">
                                <?php echo date('d M', strtotime($r['tanggal'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
