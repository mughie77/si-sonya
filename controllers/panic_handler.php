<?php
session_start();
require_once '../config/security.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!is_logged_in() || $_SESSION['role'] != 'siswa') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak (Unauthorized)']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['lat']) && isset($data['lng'])) {
    $lat = $data['lat'];
    $lng = $data['lng'];
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO panic_events (user_id, latitude, longitude, status) VALUES (?, ?, ?, 'active')");
        $stmt->execute([$user_id, $lat, $lng]);
        echo json_encode(['success' => true, 'message' => 'Sinyal Darurat terkirim! Bantuan segera meluncur ke lokasi Anda.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
}
?>
