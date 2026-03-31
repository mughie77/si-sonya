<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

$success = '';
$error = '';

// Handle CRUD Operations (Sederhana)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $nama = $_POST['nama_lengkap'];
            $role = $_POST['role'];

            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, role) VALUES (?, ?, ?, ?)");
            try {
                $stmt->execute([$username, $password, $nama, $role]);
                $success = "User berhasil ditambahkan!";
            } catch (PDOException $e) {
                $error = "Gagal menambah user: " . $e->getMessage();
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['id'];
            if ($id != $_SESSION['user_id']) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $success = "User berhasil dihapus!";
            } else {
                $error = "Anda tidak bisa menghapus diri sendiri!";
            }
        }
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY role, nama_lengkap")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - SI-SONYA Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex min-h-screen">
    <aside class="w-64 bg-indigo-900 text-white flex-shrink-0 hidden md:flex flex-col shadow-xl">
        <div class="p-6 text-2xl font-bold border-b border-indigo-800 tracking-wider">SI-SONYA</div>
        <nav class="flex-grow p-4 space-y-2">
            <a href="dashboard.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">🏠 Dashboard</a>
            <a href="kelola_user.php" class="block py-3 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-700 transition font-medium">👥 Kelola User</a>
            <a href="kelola_laporan.php" class="block py-3 px-4 rounded-xl hover:bg-indigo-700 transition">📊 Kelola Laporan</a>
        </nav>
        <div class="p-4 border-t border-indigo-800">
            <a href="../logout.php" class="block py-3 px-4 rounded-xl bg-red-600 hover:bg-red-700 transition text-center font-bold">Keluar</a>
        </div>
    </aside>

    <main class="flex-grow flex flex-col">
        <header class="bg-white shadow-sm border-b p-4 px-8 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Kelola Data Pengguna</h2>
            <button onclick="document.getElementById('modal-add').classList.remove('hidden')" class="bg-indigo-600 text-white px-6 py-2 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg">+ Tambah User</button>
        </header>

        <div class="p-8">
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg text-green-700 text-sm font-medium"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg text-red-700 text-sm font-medium"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 font-bold text-indigo-900">Nama Lengkap</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">Username</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">Role</th>
                            <th class="px-6 py-4 font-bold text-indigo-900">Dibuat Pada</th>
                            <th class="px-6 py-4 font-bold text-indigo-900 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-semibold text-gray-800"><?php echo htmlspecialchars($u['nama_lengkap']); ?></td>
                                <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td class="px-6 py-4">
                                    <?php
                                        $role_color = 'bg-gray-100 text-gray-700';
                                        if ($u['role'] == 'admin') $role_color = 'bg-red-100 text-red-700';
                                        if ($u['role'] == 'guru') $role_color = 'bg-blue-100 text-blue-700';
                                        if ($u['role'] == 'siswa') $role_color = 'bg-green-100 text-green-700';
                                    ?>
                                    <span class="px-3 py-1 <?php echo $role_color; ?> rounded-full text-[10px] font-extrabold uppercase tracking-widest">
                                        <?php echo $u['role']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-400 text-xs italic"><?php echo $u['created_at']; ?></td>
                                <td class="px-6 py-4 text-center">
                                    <form action="" method="POST" onsubmit="return confirm('Hapus user ini?')" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Add (Very Basic) -->
        <div id="modal-add" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
            <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl p-8 overflow-hidden">
                <h3 class="text-2xl font-bold text-indigo-900 mb-6">Tambah Pengguna Baru</h3>
                <form action="" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" required class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" required class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
                        <select name="role" class="w-full px-4 py-2 rounded-xl border border-gray-200 outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500">
                            <option value="siswa">Siswa</option>
                            <option value="guru">Guru</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" class="flex-grow py-3 border border-gray-200 rounded-xl font-bold hover:bg-gray-50 transition">Batal</button>
                        <button type="submit" class="flex-grow py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
