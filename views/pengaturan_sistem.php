<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in() || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF Token invalid.";
    } else {
        $contact1 = trim($_POST['emergency_contact_1'] ?? '');
        $contact2 = trim($_POST['emergency_contact_2'] ?? '');

        try {
            $pdo->prepare("UPDATE settings SET key_value = ? WHERE key_name = 'emergency_contact_1'")->execute([$contact1]);
            $pdo->prepare("UPDATE settings SET key_value = ? WHERE key_name = 'emergency_contact_2'")->execute([$contact2]);
            $success = "Pengaturan sistem berhasil diperbarui!";
        } catch (PDOException $e) {
            $error = "Gagal memperbarui pengaturan: " . $e->getMessage();
        }
    }
}

// Fetch current settings
$stmt = $pdo->query("SELECT key_name, key_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$csrf_token = generate_csrf_token();
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sistem - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen <?php echo ($role == 'admin') ? 'flex' : ''; ?>">
    <?php if ($role == 'admin') include 'includes/sidebar.php'; ?>

    <div class="flex-grow min-w-0">
        <?php include 'includes/header.php'; ?>

        <main class="max-w-4xl mx-auto p-6 md:p-10 space-y-8 pb-32">
            <div class="text-center md:text-left">
                <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Pengaturan Sistem</h2>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Konfigurasi Global SI-SONYA</p>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-2xl text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-2xl text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-[40px] p-10 shadow-sm border border-gray-100">
                <form action="" method="POST" class="space-y-8">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="space-y-6">
                        <div class="flex items-center gap-4 mb-4">
                            <span class="text-2xl">📞</span>
                            <h3 class="text-xl font-black text-indigo-900 uppercase tracking-tighter">Kontak Darurat Global</h3>
                        </div>
                        <p class="text-gray-400 text-xs font-medium italic">Kontak ini akan digunakan untuk kebutuhan darurat seluruh warga sekolah.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-3 ml-2">Kontak Darurat 1</label>
                                <input type="text" name="emergency_contact_1" value="<?php echo e($settings['emergency_contact_1'] ?? ''); ?>" required
                                    class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200 text-sm font-bold text-gray-700">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-3 ml-2">Kontak Darurat 2</label>
                                <input type="text" name="emergency_contact_2" value="<?php echo e($settings['emergency_contact_2'] ?? ''); ?>" required
                                    class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200 text-sm font-bold text-gray-700">
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-50">
                        <button type="submit"
                            class="w-full md:w-auto px-12 bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-[25px] shadow-xl shadow-blue-100 transition transform active:scale-95 uppercase text-xs tracking-widest">
                            Simpan Perubahan Global
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>
</body>
</html>
