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
        $error = "CSRF Token tidak valid.";
    } else {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($old_pass, $user['password'])) {
            $error = "Kata sandi lama salah!";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "Konfirmasi kata sandi baru tidak cocok!";
        } elseif (strlen($new_pass) < 6) {
            $error = "Kata sandi baru minimal 6 karakter!";
        } else {
            $new_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update->execute([$new_hashed, $_SESSION['user_id']])) {
                $success = "Kata sandi berhasil diperbarui!";
            }
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
    <title>Ganti Kata Sandi - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include 'includes/header.php'; ?>

    <main class="max-w-md mx-auto p-6 md:p-10 space-y-8 pb-32">
        <div class="text-center">
            <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Ganti Sandi</h2>
            <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Keamanan Akun Anda</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-2xl text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-2xl text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-[40px] p-10 shadow-sm border border-gray-100">
            <form action="" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div>
                    <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Kata Sandi Lama</label>
                    <input type="password" name="old_password" required
                        class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                </div>

                <hr class="border-gray-50">

                <div>
                    <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Kata Sandi Baru</label>
                    <input type="password" name="new_password" required
                        class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                </div>
                <div>
                    <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Ulangi Sandi Baru</label>
                    <input type="password" name="confirm_password" required
                        class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-[25px] shadow-xl shadow-blue-100 transition transform active:scale-95 uppercase text-xs tracking-widest">
                    Simpan Perubahan
                </button>
            </form>
        </div>

        <div class="text-center">
            <a href="dashboard.php" class="inline-block px-8 py-3 bg-white text-blue-600 border border-gray-100 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-50 transition">← Batalkan & Kembali</a>
        </div>
    </main>

    <?php include 'includes/footer_nav.php'; ?>
</body>
</html>
