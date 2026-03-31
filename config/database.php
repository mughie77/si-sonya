<?php
// config/database.php - Switch back to MySQL
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'si_sonya';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // MySQL specific settings if needed
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    // Koneksi gagal
    $error_db = $e->getMessage();
}
?>
