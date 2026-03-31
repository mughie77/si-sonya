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
        $role = $_POST['role_import'] ?? 'siswa';

        if (($handle = fopen($file, "r")) !== FALSE) {
            // Lewati baris pertama (header)
            fgetcsv($handle, 1000, ",");

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Format: nama_lengkap, nis_nip, kelas, tempat_lahir, tanggal_lahir, password (opsional)
                if (count($data) >= 2) {
                    $nama = trim($data[0]);
                    $nis_nip = trim($data[1]);
                    $username = $nis_nip;
                    $kelas = $data[2] ?? '';
                    $tempat_lahir = $data[3] ?? '';
                    $tanggal_lahir = (!empty($data[4])) ? date('Y-m-d', strtotime($data[4])) : null;

                    // If password column is empty, use nis_nip as default password
                    $password_plain = (!empty($data[5])) ? trim($data[5]) : $nis_nip;
                    $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

                    try {
                        $stmt = $pdo->prepare("INSERT INTO users (nama_lengkap, username, nis_nip, password, role, kelas, tempat_lahir, tanggal_lahir) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nama, $username, $nis_nip, $password_hash, $role, $kelas, $tempat_lahir, $tanggal_lahir]);
                        $count_success++;
                    } catch (PDOException $e) {
                        $count_failed++;
                    }
                }
            }
            fclose($handle);
            $success = "Proses import selesai. Berhasil: $count_success, Gagal: $count_failed.";
        } else {
            $error = "Gagal membuka file.";
        }
    } else {
        $error = "Pilih file CSV yang valid.";
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data - SI-SONYA Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-grow flex flex-col p-8 overflow-y-auto">
        <div class="max-w-2xl mx-auto w-full bg-white p-10 rounded-3xl shadow-sm border border-gray-100">
            <h2 class="text-3xl font-extrabold text-indigo-900 mb-6">Import Data Guru & Siswa</h2>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="mb-8 p-4 bg-indigo-50 rounded-2xl text-indigo-700 text-xs leading-relaxed">
                <p class="font-bold mb-2 uppercase">💡 Petunjuk CSV:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Gunakan file format <strong>.csv</strong></li>
                    <li>Urutan kolom: <strong>nama_lengkap, nis_nip, kelas, tempat_lahir, tanggal_lahir (YYYY-MM-DD), password (opsional)</strong></li>
                    <li>Baris pertama harus berisi header (akan dilewati)</li>
                    <li>Jika password dikosongkan, maka <strong>nis_nip</strong> akan digunakan sebagai password default.</li>
                </ul>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Import Sebagai:</label>
                    <select name="role_import" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none transition duration-200">
                        <option value="siswa">Siswa</option>
                        <option value="guru">Guru</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Pilih File CSV</label>
                    <input type="file" name="file_csv" accept=".csv" required
                        class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none transition duration-200 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>

                <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-indigo-500/30 transition transform hover:-translate-y-1">
                    Mulai Proses Import
                </button>
            </form>
        </div>
    </main>
</body>
</html>
