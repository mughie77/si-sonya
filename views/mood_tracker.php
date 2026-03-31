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
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Terjadi kesalahan keamanan (CSRF Token invalid).";
    } else {
        $mood = $_POST['mood_score'] ?? 0;
        $catatan = trim($_POST['catatan'] ?? '');

        if ($mood < 1 || $mood > 5) {
            $error = "Silakan pilih mood kamu terlebih dahulu.";
        } else {
            // Cek apakah hari ini sudah isi
            $check = $pdo->prepare("SELECT id FROM mood_tracking WHERE user_id = ? AND tanggal = CURRENT_DATE");
            $check->execute([$user_id]);

            if ($check->fetch()) {
                $stmt = $pdo->prepare("UPDATE mood_tracking SET mood_score = ?, catatan = ? WHERE user_id = ? AND tanggal = CURRENT_DATE");
                if ($stmt->execute([$mood, $catatan, $user_id])) {
                    $success = "Mood kamu hari ini berhasil diperbarui! ✨";
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO mood_tracking (user_id, mood_score, catatan) VALUES (?, ?, ?)");
                if ($stmt->execute([$user_id, $mood, $catatan])) {
                    $success = "Terima kasih sudah berbagi perasaanmu hari ini! ✨";
                }
            }
        }
    }
}

// Ambil riwayat mood 7 hari terakhir
$stmt = $pdo->prepare("SELECT * FROM mood_tracking WHERE user_id = ? ORDER BY tanggal DESC LIMIT 7");
$stmt->execute([$user_id]);
$riwayat = $stmt->fetchAll();

$csrf_token = generate_csrf_token();
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
        .bg-zen { background-color: #E2F2FF; }
        .card-zen { background: white; border-radius: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .mood-box { transition: all 0.3s ease; }
        .mood-input:checked + .mood-box { border-color: #FFD600; background-color: #FFFBE6; transform: scale(1.05); }
    </style>
</head>
<body class="<?php echo ($role == 'admin') ? 'bg-gray-50' : 'bg-zen'; ?> flex min-h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-grow flex flex-col overflow-hidden">
        <header class="bg-white/70 backdrop-blur-md shadow-sm border-b p-4 px-8 flex justify-between items-center z-10">
            <h2 class="text-xl font-bold text-gray-800">Mood Tracker</h2>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase tracking-widest">Self Care</span>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-8 overflow-y-auto max-w-4xl mx-auto w-full">
            <div class="card-zen p-10">
                <div class="text-center mb-10">
                    <h3 class="text-2xl font-black text-gray-800 leading-tight">Tap the emoji that matches your today's mood.</h3>
                    <p class="text-gray-400 text-sm mt-1 uppercase tracking-widest font-bold"><?php echo date('jS F Y'); ?></p>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-10 rounded-lg text-green-700 text-sm font-medium"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-10 rounded-lg text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-10">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-6">
                        <!-- Sangat Sedih (Angry/Sad alternative) -->
                        <label class="cursor-pointer">
                            <input type="radio" name="mood_score" value="1" class="hidden mood-input" required>
                            <div class="mood-box p-6 border-2 border-gray-100 rounded-3xl text-center">
                                <span class="text-5xl block mb-3">😢</span>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Sad</span>
                            </div>
                        </label>
                        <!-- Sedih -->
                        <label class="cursor-pointer">
                            <input type="radio" name="mood_score" value="2" class="hidden mood-input">
                            <div class="mood-box p-6 border-2 border-gray-100 rounded-3xl text-center">
                                <span class="text-5xl block mb-3">😟</span>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Anxious</span>
                            </div>
                        </label>
                        <!-- Biasa -->
                        <label class="cursor-pointer">
                            <input type="radio" name="mood_score" value="3" class="hidden mood-input">
                            <div class="mood-box p-6 border-2 border-gray-100 rounded-3xl text-center">
                                <span class="text-5xl block mb-3">😐</span>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Neutral</span>
                            </div>
                        </label>
                        <!-- Senang -->
                        <label class="cursor-pointer">
                            <input type="radio" name="mood_score" value="4" class="hidden mood-input">
                            <div class="mood-box p-6 border-2 border-gray-100 rounded-3xl text-center">
                                <span class="text-5xl block mb-3">😊</span>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Happy</span>
                            </div>
                        </label>
                        <!-- Sangat Senang (Excited) -->
                        <label class="cursor-pointer">
                            <input type="radio" name="mood_score" value="5" class="hidden mood-input">
                            <div class="mood-box p-6 border-2 border-gray-100 rounded-3xl text-center">
                                <span class="text-5xl block mb-3">🤩</span>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Excited</span>
                            </div>
                        </label>
                        <!-- Angry (Scale of 1 alternative) -->
                        <label class="cursor-pointer">
                            <input type="radio" name="mood_score" value="1" class="hidden mood-input">
                            <div class="mood-box p-6 border-2 border-gray-100 rounded-3xl text-center">
                                <span class="text-5xl block mb-3">😡</span>
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Angry</span>
                            </div>
                        </label>
                    </div>

                    <div class="space-y-4">
                        <label class="block text-sm font-bold text-gray-800 uppercase tracking-widest">Notes (Optional)</label>
                        <textarea name="catatan" rows="4" placeholder="Want to write something about your day?"
                            class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none outline-none focus:ring-2 focus:ring-indigo-100 transition duration-200 text-gray-700"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full bg-[#A9DCFF] hover:bg-indigo-400 text-indigo-900 font-black py-5 rounded-[25px] shadow-lg shadow-indigo-100 transition-all transform hover:-translate-y-1 active:scale-95 uppercase tracking-widest text-sm">
                        Submit Mood
                    </button>
                </form>
            </div>

            <!-- History Section (Matching the clean design) -->
            <div class="card-zen p-10">
                <h3 class="text-xl font-black text-gray-800 mb-8 uppercase tracking-tighter">Your Journey</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (empty($riwayat)): ?>
                        <div class="col-span-2 text-center text-gray-400 py-10 italic">No mood history yet.</div>
                    <?php endif; ?>
                    <?php foreach ($riwayat as $r): ?>
                        <div class="p-6 bg-gray-50/50 rounded-[25px] flex items-center gap-6 border border-white hover:bg-white hover:shadow-xl transition-all group">
                            <div class="text-4xl transform group-hover:scale-110 transition">
                                <?php
                                    if ($r['mood_score'] == 5) echo "🤩";
                                    else if ($r['mood_score'] == 4) echo "😊";
                                    else if ($r['mood_score'] == 3) echo "😐";
                                    else if ($r['mood_score'] == 2) echo "😟";
                                    else echo "😢";
                                ?>
                            </div>
                            <div class="flex-grow">
                                <h4 class="font-black text-gray-800 uppercase tracking-tighter text-xs">
                                    <?php
                                        if ($r['mood_score'] == 5) echo "Excited";
                                        else if ($r['mood_score'] == 4) echo "Happy";
                                        else if ($r['mood_score'] == 3) echo "Neutral";
                                        else if ($r['mood_score'] == 2) echo "Anxious";
                                        else echo "Sad";
                                    ?>
                                </h4>
                                <p class="text-gray-400 text-[10px] italic mt-1 line-clamp-1"><?php echo e($r['catatan'] ?: 'No notes provided'); ?></p>
                            </div>
                            <div class="text-[9px] font-black text-indigo-300 uppercase">
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
