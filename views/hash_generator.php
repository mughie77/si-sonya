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
    <title>Hash Generator - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="max-w-2xl mx-auto p-6 md:p-10 space-y-8 pb-32">
        <div class="text-center">
            <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Hash Generator</h2>
            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Alat Keamanan Pengembang</p>
        </div>

        <div class="bg-white rounded-[40px] p-10 shadow-sm border border-gray-100">
            <h3 class="text-xl font-black text-indigo-900 mb-2 uppercase tracking-tighter">Buat Hash Password</h3>
            <p class="text-gray-400 mb-8 text-xs font-medium italic">Gunakan alat ini untuk menghasilkan hash password yang aman untuk database Anda secara manual.</p>

            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div>
                    <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Teks Biasa (Plain Password)</label>
                    <input type="text" name="plain_text" required value="<?php echo e($input); ?>"
                        class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200 text-lg font-bold"
                        placeholder="Ketik password di sini...">
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-[25px] shadow-xl shadow-blue-100 transition transform active:scale-95 uppercase text-xs tracking-widest">
                    Hasilkan Hash Sekarang
                </button>
            </form>

            <?php if ($hash): ?>
                <div class="mt-10 p-8 bg-indigo-50 rounded-[30px] border border-blue-100">
                    <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-3 ml-2">Hasil Hash (BCRYPT):</label>
                    <div class="bg-white p-5 rounded-2xl border border-gray-100 font-mono text-xs break-all select-all cursor-pointer hover:bg-blue-50 transition" title="Klik untuk menyeleksi">
                        <?php echo e($hash); ?>
                    </div>
                    <p class="mt-4 text-[10px] text-indigo-400/60 font-black uppercase tracking-widest">*Gunakan hash ini untuk mengisi kolom `password` pada database.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <a href="dashboard.php" class="inline-block px-8 py-3 bg-white text-blue-600 border border-gray-100 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-50 transition">← Kembali ke Dashboard</a>
        </div>
    </main>

    <?php include 'includes/footer_nav.php'; ?>
</body>
</html>
