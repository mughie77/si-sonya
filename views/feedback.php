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
        $feedback = $_POST['isi_feedback'];
        $user_id = $_SESSION['user_id'];

        $stmt = $pdo->prepare("INSERT INTO feedback (user_id, isi_feedback) VALUES (?, ?)");
        if ($stmt->execute([$user_id, $feedback])) {
            $success = "Terima kasih atas saran Anda! Ini sangat berarti bagi perkembangan sekolah.";
        } else {
            $error = "Terjadi kesalahan saat mengirim saran.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-grow flex flex-col items-center justify-center p-8 overflow-y-auto">
        <div class="max-w-2xl w-full bg-white p-10 rounded-3xl shadow-sm border border-gray-100 text-center h-fit">
            <div class="inline-block p-4 bg-purple-100 rounded-2xl mb-6">
                <span class="text-4xl">💬</span>
            </div>
            <h2 class="text-3xl font-extrabold text-indigo-900 mb-2">Suara Anda, Masa Depan Sekolah</h2>
            <p class="text-gray-500 mb-10">Punya saran untuk fasilitas atau sistem sekolah? Tuliskan di sini!</p>

            <?php if ($success): ?>
                <div class="bg-purple-50 border-l-4 border-purple-500 p-4 mb-10 rounded-lg text-purple-700 text-sm text-left font-medium"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-10 rounded-lg text-red-700 text-sm text-left font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <textarea name="isi_feedback" required rows="6" placeholder="Ketik saran atau masukan Anda di sini..."
                    class="w-full px-6 py-4 rounded-2xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200 text-lg"></textarea>

                <button type="submit"
                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-purple-500/30 transition transform hover:-translate-y-1">
                    Kirim Saran Sekarang
                </button>
            </form>

            <div class="mt-8 pt-8 border-t border-gray-50">
                <p class="text-xs text-gray-400 font-medium tracking-widest uppercase italic">Setiap masukan akan ditinjau secara berkala oleh tim sekolah.</p>
            </div>
        </div>
    </main>
</body>
</html>
