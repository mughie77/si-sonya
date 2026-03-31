<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in() || !has_role('admin')) {
    header("Location: ../index.php");
    exit();
}

$success = '';
$error = '';
$count_success = 0;
$count_failed = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF Token invalid.";
    } elseif (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] === 0) {
        $file = $_FILES['file_csv']['tmp_name'];
        $role_imp = $_POST['role_import'] ?? 'siswa';

        if (($handle = fopen($file, "r")) !== FALSE) {
            fgetcsv($handle, 1000, ",");

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) >= 2) {
                    $nama = trim($data[0]);
                    $nis_nip = trim($data[1]);
                    $username = $nis_nip;
                    $kelas = $data[2] ?? '';
                    $tempat_lahir = $data[3] ?? '';
                    $tanggal_lahir = (!empty($data[4])) ? date('Y-m-d', strtotime($data[4])) : null;
                    $password_plain = (!empty($data[5])) ? trim($data[5]) : $nis_nip;
                    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, username, nis_nip, password, role, kelas, tempat_lahir, tanggal_lahir) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nama, $username, $nis_nip, $password_hash, $role_imp, $kelas, $tempat_lahir, $tanggal_lahir]);
                        $count_success++;
                    } catch (PDOException $e) { $count_failed++; }
                }
            }
            fclose($handle);
            $success = "Import selesai! Berhasil: $count_success, Gagal: $count_failed.";
        } else { $error = "Gagal membuka file."; }
    } else { $error = "Pilih file CSV yang valid."; }
}

$csrf_token = generate_csrf_token();
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen <?php echo ($role == 'admin') ? 'flex' : ''; ?>">
    <?php if ($role == 'admin') include 'includes/sidebar.php'; ?>

    <div class="flex-grow">
        <?php include 'includes/header.php'; ?>

        <main class="max-w-4xl mx-auto p-6 md:p-10 space-y-8 pb-32">
            <div class="text-center">
                <h2 class="text-3xl font-black text-indigo-900 uppercase tracking-tighter">Import Data Masal</h2>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Unggah CSV untuk Guru & Siswa</p>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-2xl text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-2xl text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-white rounded-[40px] p-10 shadow-sm border border-gray-100">
                    <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div>
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Import Sebagai:</label>
                            <select name="role_import" class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none focus:ring-2 focus:ring-blue-100 outline-none transition duration-200">
                                <option value="siswa">Siswa (Peserta Didik)</option>
                                <option value="guru">Guru (Tenaga Pendidik)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Pilih File CSV</label>
                            <input type="file" name="file_csv" accept=".csv" required
                                class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none outline-none file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                        </div>

                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-[25px] shadow-xl shadow-blue-100 transition transform active:scale-95 uppercase text-xs tracking-widest">
                            Proses Unggah Data
                        </button>
                    </form>
                </div>

                <div class="bg-indigo-900 rounded-[40px] p-10 shadow-xl text-white">
                    <h3 class="text-xl font-black uppercase tracking-tighter mb-6">💡 Panduan Format</h3>
                    <div class="space-y-4 text-xs font-medium text-indigo-100 leading-relaxed">
                        <p>Pastikan file Anda menggunakan ekstensi <strong class="text-white">.csv</strong> dengan urutan kolom sebagai berikut:</p>
                        <ol class="list-decimal list-inside space-y-2 ml-2">
                            <li><span class="text-white font-bold">nama_lengkap</span></li>
                            <li><span class="text-white font-bold">nis_nip</span></li>
                            <li><span class="text-white font-bold">kelas / mata_pelajaran</span></li>
                            <li><span class="text-white font-bold">tempat_lahir</span></li>
                            <li><span class="text-white font-bold">tanggal_lahir</span> (TTTT-BB-HH)</li>
                            <li><span class="text-white font-bold">password</span> (opsional)</li>
                        </ol>
                    </div>
                </div>
            </div>
        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>
</body>
</html>
