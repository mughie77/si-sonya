<?php
require_once 'config/security.php';
require_once 'config/database.php';

if (is_logged_in()) {
    header("Location: views/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Terjadi kesalahan keamanan (CSRF Token invalid).";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];
                header("Location: views/dashboard.php");
                exit();
            } else {
                $error = "Username atau password salah!";
            }
        } else {
            $error = "Gagal terhubung ke database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-[#E2F2FF] min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full">
        <!-- Logo/Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-[30px] shadow-xl shadow-blue-100 mb-6 text-4xl">
                🏫
            </div>
            <h1 class="text-4xl font-black text-indigo-900 tracking-tighter uppercase">SI-SONYA</h1>
            <p class="text-blue-400 text-[10px] font-black uppercase tracking-[0.3em] mt-2">Sistem Sekolah Aman & Nyaman</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-[50px] shadow-2xl shadow-blue-200/50 p-10 md:p-12 border border-white relative overflow-hidden">
            <!-- Decorative circle -->
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-50 rounded-full opacity-50"></div>

            <h2 class="text-2xl font-black text-indigo-900 mb-8 uppercase tracking-tighter relative">Selamat Datang</h2>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-8 rounded-2xl text-red-700 text-[11px] font-bold uppercase tracking-wider">
                    ⚠️ <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-6 relative">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                <div>
                    <label class="block text-[10px] font-black text-indigo-900 uppercase tracking-widest mb-2 ml-4">ID Pengguna (NIS/NIP)</label>
                    <input type="text" name="username" required
                        class="w-full px-8 py-5 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200 font-bold text-gray-700"
                        placeholder="Contoh: 2024001">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-indigo-900 uppercase tracking-widest mb-2 ml-4">Kata Sandi</label>
                    <input type="password" name="password" required
                        class="w-full px-8 py-5 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200 font-bold text-gray-700"
                        placeholder="••••••••">
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-[25px] shadow-xl shadow-blue-200 transition transform active:scale-95 uppercase text-xs tracking-widest">
                        Masuk Sekarang
                    </button>
                </div>
            </form>

            <div class="mt-10 text-center relative pt-6 border-t border-gray-50">
                <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest">Masalah login? <br> <span class="text-blue-500">Hubungi Admin Sekolah</span></p>
            </div>
        </div>

        <!-- Footer Note -->
        <p class="mt-12 text-center text-[10px] font-black text-indigo-900/30 uppercase tracking-[0.4em]">ZenMind Edition v6.0</p>
    </div>
</body>
</html>
