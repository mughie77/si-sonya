<?php
session_start();
require_once '../config/security.php';

if (!is_logged_in() || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$hash = '';
$input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $input = $_POST['plain_text'] ?? '';
        if ($input) {
            $hash = password_hash($input, PASSWORD_DEFAULT);
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hash Generator - SI-SONYA Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-grow flex flex-col p-8 items-center justify-center overflow-y-auto">
        <div class="max-w-2xl w-full bg-white p-10 rounded-3xl shadow-sm border border-gray-100 h-fit">
            <h2 class="text-3xl font-extrabold text-indigo-900 mb-2">Password Hash Generator</h2>
            <p class="text-gray-500 mb-10 text-sm italic">Gunakan alat ini untuk menghasilkan hash password yang aman untuk database Anda.</p>

            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Plain Password</label>
                    <input type="text" name="plain_text" required value="<?php echo e($input); ?>"
                        class="w-full px-6 py-4 rounded-2xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200 text-lg"
                        placeholder="Masukkan password di sini...">
                </div>

                <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-indigo-500/30 transition transform hover:-translate-y-1">
                    Hasilkan Hash Sekarang
                </button>
            </form>

            <?php if ($hash): ?>
                <div class="mt-10 p-6 bg-gray-50 rounded-2xl border border-indigo-100">
                    <label class="block text-xs font-bold text-indigo-400 uppercase tracking-widest mb-3">Hasil Hash:</label>
                    <div class="bg-white p-4 rounded-xl border border-gray-200 font-mono text-xs break-all select-all cursor-pointer hover:bg-indigo-50 transition" title="Klik untuk menyeleksi">
                        <?php echo e($hash); ?>
                    </div>
                    <p class="mt-4 text-[10px] text-gray-400 font-medium">*Gunakan hash ini untuk kolom `password` pada tabel `users`.</p>
                </div>
            <?php endif; ?>

            <div class="mt-8 pt-8 border-t border-gray-50 text-center">
                <a href="dashboard.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-bold transition">← Kembali ke Dashboard</a>
            </div>
        </div>
    </main>
</body>
</html>
