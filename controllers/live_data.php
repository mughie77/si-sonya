<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!is_logged_in() || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'guru')) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Fetch Active Panic Events
$panics = $pdo->query("SELECT p.*, u.nama_lengkap, u.nis_nip, u.kelas
                      FROM panic_events p
                      JOIN users u ON p.user_id = u.id
                      WHERE p.status = 'active'
                      ORDER BY p.created_at DESC")->fetchAll();

// Fetch Latest Bullying Reports (Last 24 Hours)
$bullying = $pdo->query("SELECT b.*, u.nama_lengkap as pelapor_nama_asli
                        FROM bullying_reports b
                        JOIN users u ON b.pelapor_id = u.id
                        WHERE b.created_at >= NOW() - INTERVAL 1 DAY
                        ORDER BY b.created_at DESC")->fetchAll();

// Check for new notifications (to play sound)
$new_panic = $pdo->query("SELECT COUNT(*) FROM panic_events WHERE is_notified = 0 AND status = 'active'")->fetchColumn();
$new_bullying = $pdo->query("SELECT COUNT(*) FROM bullying_reports WHERE is_notified = 0")->fetchColumn();

// Mark as notified
if ($new_panic > 0) {
    $pdo->exec("UPDATE panic_events SET is_notified = 1 WHERE status = 'active'");
}
if ($new_bullying > 0) {
    $pdo->exec("UPDATE bullying_reports SET is_notified = 1");
}

echo json_encode([
    'panics' => $panics,
    'bullying' => $bullying,
    'alert' => ($new_panic > 0),
    'timestamp' => date('H:i:s')
]);
?>
