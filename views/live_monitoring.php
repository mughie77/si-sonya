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
    <title>Pantauan Langsung - SI-SONYA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .blink { animation: blinker 1s linear infinite; }
        @keyframes blinker { 50% { opacity: 0; } }
        .bg-security { background-color: #0F172A; }
    </style>
</head>
<body class="bg-security text-gray-100 min-h-screen <?php echo ($role == 'admin') ? 'flex' : ''; ?>">
    <?php if ($role == 'admin') include 'includes/sidebar.php'; ?>

    <div class="flex-grow min-w-0">
        <?php include 'includes/header.php'; ?>

        <main class="max-w-5xl mx-auto p-6 md:p-10 space-y-8 pb-32">
            <header class="flex justify-between items-center border-b border-gray-800 pb-6">
                <div class="flex items-center gap-4">
                    <div class="w-3 h-3 bg-red-500 rounded-full animate-ping"></div>
                    <h2 class="text-2xl font-black uppercase tracking-widest text-white">Live Security Feed</h2>
                </div>
                <span id="last-update" class="text-[10px] text-gray-500 font-mono font-bold uppercase">Memuat...</span>
            </header>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Panic Section -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b border-red-900/30 pb-3">
                        <h3 class="text-lg font-black text-red-400 flex items-center gap-3 tracking-tighter">
                            <span class="text-2xl">🚨</span> SINYAL DARURAT AKTIF
                        </h3>
                        <span id="panic-count" class="bg-red-600 text-white text-[10px] px-2.5 py-1 rounded-full font-black">0</span>
                    </div>
                    <div id="panic-container" class="space-y-4">
                        <div class="p-10 text-center text-gray-600 italic">Memindai sinyal...</div>
                    </div>
                </div>

                <!-- Bullying Section -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between border-b border-indigo-900/30 pb-3">
                        <h3 class="text-lg font-black text-indigo-400 flex items-center gap-3 tracking-tighter">
                            <span class="text-2xl">🛡️</span> LAPORAN BULLYING (24 J)
                        </h3>
                        <span id="bullying-count" class="bg-indigo-600 text-white text-[10px] px-2.5 py-1 rounded-full font-black">0</span>
                    </div>
                    <div id="bullying-container" class="space-y-4">
                        <div class="p-10 text-center text-gray-600 italic">Memantau laporan...</div>
                    </div>
                </div>
            </div>
        </main>

        <?php include 'includes/footer_nav.php'; ?>
    </div>

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
                    lastUpdateSpan.innerText = 'Update Terakhir: ' + data.timestamp;

                    if (data.alert) {
                        alertSound.play().catch(e => console.log("Audio diblokir"));
                    }

                    panicCount.innerText = data.panics.length;
                    if (data.panics.length > 0) {
                        panicContainer.innerHTML = data.panics.map(p => `
                            <div class="bg-red-900/10 border border-red-500/30 p-6 rounded-[35px] relative overflow-hidden group hover:border-red-500 transition-all">
                                <div class="absolute top-0 right-0 p-4">
                                    <div class="w-4 h-4 bg-red-500 rounded-full blink"></div>
                                </div>
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h4 class="text-xl font-black text-red-100 uppercase tracking-tighter">${p.nama_lengkap}</h4>
                                        <p class="text-[10px] text-red-400 font-bold uppercase tracking-widest">${p.nis_nip} • KELAS ${p.kelas}</p>
                                    </div>
                                    <span class="text-[9px] bg-red-600/20 text-red-400 px-2 py-1 rounded-lg font-black">${p.created_at}</span>
                                </div>
                                <a href="https://www.google.com/maps?q=${p.latitude},${p.longitude}" target="_blank" class="block w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-2xl text-center font-black text-xs transition uppercase tracking-widest shadow-lg">📍 Buka Lokasi GPS</a>
                            </div>
                        `).join('');
                    } else {
                        panicContainer.innerHTML = '<div class="p-10 text-center text-gray-600 italic">Situasi Kondusif. Tidak ada sinyal darurat.</div>';
                    }

                    bullyingCount.innerText = data.bullying.length;
                    if (data.bullying.length > 0) {
                        bullyingContainer.innerHTML = data.bullying.map(b => `
                            <div class="bg-gray-800/40 border border-indigo-500/20 p-6 rounded-[35px] hover:border-indigo-500/50 transition-all">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="px-3 py-1 bg-indigo-900/50 text-indigo-300 text-[9px] font-black rounded-full uppercase tracking-widest border border-indigo-500/20">Laporan Baru</span>
                                    <span class="text-[9px] text-gray-500 font-bold">${b.created_at}</span>
                                </div>
                                <p class="text-sm text-gray-300 leading-relaxed mb-4 italic">"${b.deskripsi.substring(0, 120)}..."</p>
                                <div class="flex items-center justify-between pt-4 border-t border-gray-800">
                                    <span class="text-[10px] text-indigo-400 font-black uppercase italic">📍 ${b.lokasi}</span>
                                    <a href="kelola_laporan.php" class="text-[10px] text-indigo-500 font-black uppercase hover:underline tracking-widest">Detail &rarr;</a>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        bullyingContainer.innerHTML = '<div class="p-10 text-center text-gray-600 italic">Tidak ada laporan bullying terbaru hari ini.</div>';
                    }
                });
        }

        setInterval(fetchData, 5000);
        fetchData();
    </script>
</body>
</html>
