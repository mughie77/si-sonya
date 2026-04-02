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
        $feedback = $_POST['isi_feedback'];
        $user_id = $_SESSION['user_id'];

        $stmt = $pdo->prepare("INSERT INTO feedback (user_id, isi_feedback) VALUES (?, ?)");
        if ($stmt->execute([$user_id, $feedback])) {
            $success = "Terima kasih atas saran Anda! Ini sangat berarti bagi perkembangan sekolah.";
        } else { $error = "Terjadi kesalahan saat mengirim saran."; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback & Saran - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen <?php echo ($role == 'admin') ? 'flex' : ''; ?>">
    <?php if ($role == 'admin') include 'includes/sidebar.php'; ?>

    <div class="flex-grow">
        <?php include 'includes/header.php'; ?>

        <main class="max-w-3xl mx-auto p-6 md:p-10 space-y-8 pb-32">
            <div class="text-center">
                <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Feedback & Saran</h2>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Suara Anda, Masa Depan Sekolah</p>
            </div>

            <div class="bg-white rounded-[40px] p-10 md:p-16 shadow-sm border border-gray-100 text-center">
                <div class="inline-block p-6 bg-purple-50 text-purple-600 rounded-[30px] mb-8 shadow-inner text-4xl">
                    💬
                </div>
                <h3 class="text-2xl font-black text-indigo-900 mb-4 uppercase tracking-tighter">Sampaikan Aspirasi Anda</h3>
                <p class="text-gray-400 mb-10 text-xs font-bold uppercase tracking-widest leading-relaxed">Punya saran untuk fasilitas atau sistem sekolah? <br> Tuliskan secara terbuka atau anonim di sini!</p>

                <?php if ($success): ?>
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-6 mb-10 rounded-2xl text-emerald-700 text-sm text-left font-medium"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-6 mb-10 rounded-2xl text-red-700 text-sm text-left font-medium"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-8 text-left">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div>
                        <label class="block text-[10px] font-black text-indigo-900 uppercase tracking-widest mb-4 ml-4">Pesan atau Masukan:</label>
                        <textarea name="isi_feedback" required rows="6" placeholder="Ketik aspirasi Anda di sini secara bijak..."
                            class="w-full px-8 py-6 rounded-[35px] bg-gray-50 border-none focus:ring-2 focus:ring-purple-100 outline-none transition duration-200 text-lg font-medium custom-scrollbar"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-black py-5 rounded-[25px] shadow-xl shadow-purple-100 transition transform active:scale-95 uppercase text-xs tracking-widest">
                        Kirim Aspirasi Sekarang
                    </button>
                </form>
            </div>
        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>
</body>
</html>
