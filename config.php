<?php
// config.php
// Database connection and shared settings

session_start();

$dbHost = 'localhost';
$dbName = 'classroom_clone';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Helper: require login
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}
