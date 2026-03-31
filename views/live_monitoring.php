<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

if (!is_logged_in() || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'guru')) {
    header("Location: ../index.php");
    exit();
}

$nama = $_SESSION['nama_lengkap'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Monitoring - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .blink { animation: blinker 1s linear infinite; }
        @keyframes blinker { 50% { opacity: 0; } }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-black border-r border-gray-800 flex-shrink-0 hidden md:flex flex-col shadow-2xl">
        <div class="p-6 text-2xl font-bold border-b border-gray-800 tracking-wider text-indigo-500">SI-SONYA <span class="text-xs text-red-500 block">LIVE MONITOR</span></div>
        <nav class="flex-grow p-4 space-y-2">
            <a href="dashboard.php" class="block py-3 px-4 rounded-xl hover:bg-gray-800 transition">🏠 Dashboard</a>
            <a href="live_monitoring.php" class="block py-3 px-4 rounded-xl bg-gray-800 transition font-bold border-l-4 border-indigo-500">📡 Live View</a>
            <?php if ($role == 'admin'): ?>
                <a href="kelola_user.php" class="block py-3 px-4 rounded-xl hover:bg-gray-800 transition">👥 Kelola User</a>
                <a href="kelola_laporan.php" class="block py-3 px-4 rounded-xl hover:bg-gray-800 transition">📊 Kelola Laporan</a>
            <?php endif; ?>
        </nav>
        <div class="p-4 border-t border-gray-800">
            <a href="../logout.php" class="block py-3 px-4 rounded-xl bg-red-900/50 hover:bg-red-800 transition text-center font-bold text-red-200">Keluar</a>
        </div>
    </aside>

    <main class="flex-grow flex flex-col overflow-hidden">
        <header class="bg-black/50 backdrop-blur-md border-b border-gray-800 p-4 px-8 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="w-3 h-3 bg-red-500 rounded-full animate-ping"></div>
                <h2 class="text-xl font-black uppercase tracking-widest text-white">Live Security Feed</h2>
                <span id="last-update" class="text-xs text-gray-500 font-mono">Loading...</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-3 py-1 bg-indigo-900/50 text-indigo-300 rounded-full text-[10px] font-bold uppercase"><?php echo e($role); ?>: <?php echo e($nama); ?></span>
            </div>
        </header>

        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6 overflow-y-auto">
            <!-- Panic Button Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-red-900/30 pb-2">
                    <h3 class="text-lg font-bold text-red-400 flex items-center gap-2">
                        <span class="text-2xl">🆘</span> ACTIVE PANIC ALERTS
                    </h3>
                    <span id="panic-count" class="bg-red-600 text-white text-xs px-2 py-1 rounded-full font-bold">0</span>
                </div>
                <div id="panic-container" class="space-y-3">
                    <!-- Dynamic Content -->
                    <div class="p-10 text-center text-gray-600 italic">No active emergencies.</div>
                </div>
            </div>

            <!-- Bullying Feed Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-indigo-900/30 pb-2">
                    <h3 class="text-lg font-bold text-indigo-400 flex items-center gap-2">
                        <span class="text-2xl">🛡️</span> LATEST BULLYING REPORTS (24H)
                    </h3>
                    <span id="bullying-count" class="bg-indigo-600 text-white text-xs px-2 py-1 rounded-full font-bold">0</span>
                </div>
                <div id="bullying-container" class="space-y-3">
                    <!-- Dynamic Content -->
                    <div class="p-10 text-center text-gray-600 italic">No reports in the last 24 hours.</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Alert Sound -->
    <audio id="alert-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

    <script>
        const panicContainer = document.getElementById('panic-container');
        const bullyingContainer = document.getElementById('bullying-container');
        const lastUpdateSpan = document.getElementById('last-update');
        const panicCount = document.getElementById('panic-count');
        const bullyingCount = document.getElementById('bullying-count');
        const alertSound = document.getElementById('alert-sound');

        function fetchData() {
            fetch('../controllers/live_data.php')
                .then(res => res.json())
                .then(data => {
                    lastUpdateSpan.innerText = 'Last Update: ' + data.timestamp;

                    // Play sound if new emergency
                    if (data.alert) {
                        alertSound.play().catch(e => console.log("Audio play blocked by browser"));
                        // Show browser notification if possible
                        if (Notification.permission === "granted") {
                            new Notification("🚨 EMERGENCY!", { body: "New Panic Button Alert detected!" });
                        }
                    }

                    // Update Panics
                    panicCount.innerText = data.panics.length;
                    if (data.panics.length > 0) {
                        panicContainer.innerHTML = data.panics.map(p => `
                            <div class="bg-red-900/20 border border-red-500/30 p-5 rounded-3xl relative overflow-hidden group hover:border-red-500 transition-all duration-300">
                                <div class="absolute top-0 right-0 p-3">
                                    <div class="w-4 h-4 bg-red-500 rounded-full blink shadow-[0_0_15px_rgba(239,68,68,1)]"></div>
                                </div>
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="text-xl font-black text-red-200 uppercase">${p.nama_lengkap}</h4>
                                        <p class="text-xs text-red-400 font-mono">${p.nis_nip} • Kelas ${p.kelas}</p>
                                    </div>
                                    <span class="text-[10px] bg-red-600 text-white px-2 py-1 rounded-lg font-bold">${p.created_at}</span>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <a href="https://www.google.com/maps?q=${p.latitude},${p.longitude}" target="_blank" class="flex-grow bg-red-600 hover:bg-red-700 text-white py-2 rounded-xl text-center font-bold text-sm transition shadow-lg">📍 LIHAT LOKASI</a>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        panicContainer.innerHTML = '<div class="p-10 text-center text-gray-600 italic">Semua aman. Tidak ada sinyal darurat aktif.</div>';
                    }

                    // Update Bullying
                    bullyingCount.innerText = data.bullying.length;
                    if (data.bullying.length > 0) {
                        bullyingContainer.innerHTML = data.bullying.map(b => `
                            <div class="bg-gray-800/50 border border-indigo-500/20 p-5 rounded-3xl hover:border-indigo-500/50 transition-all">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="px-3 py-1 bg-indigo-900 text-indigo-300 text-[10px] font-black rounded-full uppercase">LAPORAN BARU</span>
                                    <span class="text-[10px] text-gray-500">${b.created_at}</span>
                                </div>
                                <p class="text-sm text-gray-200 leading-relaxed mb-3">"${b.deskripsi.substring(0, 100)}..."</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-indigo-400 font-bold uppercase italic">📍 ${b.lokasi}</span>
                                    <a href="kelola_laporan.php" class="text-xs text-indigo-500 hover:underline">Detail &rarr;</a>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        bullyingContainer.innerHTML = '<div class="p-10 text-center text-gray-600 italic">Tidak ada laporan bullying terbaru.</div>';
                    }
                });
        }

        // Request notification permission
        if (Notification.permission !== "granted") {
            Notification.requestPermission();
        }

        // Poll every 5 seconds
        setInterval(fetchData, 5000);
        fetchData(); // Initial load
    </script>
</body>
</html>
