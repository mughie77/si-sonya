<?php
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

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
    } elseif (isset($_FILES['file_excel']) && $_FILES['file_excel']['error'] === 0) {
        $file_path = $_FILES['file_excel']['tmp_name'];
        $role_imp = $_POST['role_import'] ?? 'siswa';

        try {
            $spreadsheet = IOFactory::load($file_path);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Skip header row
            for ($i = 1; $i < count($rows); $i++) {
                $data = $rows[$i];
                if (isset($data[0]) && !empty($data[0])) {
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
            $success = "Import selesai! Berhasil: $count_success, Gagal: $count_failed.";
        } catch (Exception $e) {
            $error = "Gagal memproses file Excel: " . $e->getMessage();
        }
    } else { $error = "Pilih file Excel yang valid."; }
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
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">Unggah Excel untuk Guru & Siswa</p>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-2xl text-green-700 text-sm font-medium"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-2xl text-red-700 text-sm font-medium"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="grid md:grid-cols-2 gap-8 items-start">
                <!-- Upload Box -->
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
                            <label class="block text-xs font-black text-indigo-900 uppercase tracking-widest mb-2 ml-2">Pilih File Excel (.xlsx)</label>
                            <input type="file" name="file_excel" accept=".xlsx, .xls" required
                                class="w-full px-6 py-4 rounded-[25px] bg-gray-50 border-none outline-none file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                        </div>

                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-[25px] shadow-xl shadow-blue-100 transition transform active:scale-95 uppercase text-xs tracking-widest">
                            Proses Unggah Data
                        </button>
                    </form>
                </div>

                <!-- Template & Help Box -->
                <div class="bg-indigo-900 rounded-[40px] p-10 shadow-xl text-white">
                    <h3 class="text-xl font-black uppercase tracking-tighter mb-6">📥 Unduh Template</h3>
                    <div class="grid grid-cols-1 gap-4 mb-8">
                        <a href="../controllers/download_template.php?type=siswa" class="flex items-center justify-between p-4 bg-white/10 hover:bg-white/20 border border-white/10 rounded-2xl transition group">
                            <span class="text-xs font-black uppercase tracking-widest">Template Siswa</span>
                            <span class="text-xl group-hover:scale-125 transition">📄</span>
                        </a>
                        <a href="../controllers/download_template.php?type=guru" class="flex items-center justify-between p-4 bg-white/10 hover:bg-white/20 border border-white/10 rounded-2xl transition group">
                            <span class="text-xs font-black uppercase tracking-widest">Template Guru</span>
                            <span class="text-xl group-hover:scale-125 transition">📄</span>
                        </a>
                    </div>

                    <h3 class="text-lg font-black uppercase tracking-tighter mb-4">💡 Aturan Kolom</h3>
                    <div class="space-y-3 text-[10px] font-medium text-indigo-100 leading-relaxed">
                        <p>Pastikan urutan kolom sesuai dengan template:</p>
                        <ol class="list-decimal list-inside space-y-1 ml-2">
                            <li><span class="text-white font-bold">Nama Lengkap</span></li>
                            <li><span class="text-white font-bold">NIS / NIP</span></li>
                            <li><span class="text-white font-bold">Kelas / Mapel</span></li>
                            <li><span class="text-white font-bold">Tempat Lahir</span></li>
                            <li><span class="text-white font-bold">Tgl Lahir (YYYY-MM-DD)</span></li>
                            <li><span class="text-white font-bold">Password</span></li>
                        </ol>
                    </div>
                </div>
            </div>
        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>
</body>
</html>
