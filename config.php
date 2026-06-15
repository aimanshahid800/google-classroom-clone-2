<?php
session_start();
 
define('BASE_URL', '/Uni-Team-Project/google-classroom-clone-2');
 
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
 
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}
 
function current_user() {
    return $_SESSION['user'] ?? null;
}
 
// ── Paired: same index = same class gets matching solid + gradient ──
// solid colors are the dominant/end color of each gradient
$GLOBALS['_class_color_pairs'] = [
    // index => [solid,  gradient]
    0 => ['#302b63', 'linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%)'],
    1 => ['#0f3460', 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)'],
    2 => ['#2d1b69', 'linear-gradient(135deg, #0d0d0d 0%, #1a0533 50%, #2d1b69 100%)'],
    3 => ['#2c5364', 'linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%)'],
    4 => ['#928dab', 'linear-gradient(135deg, #1f1c2c 0%, #928dab 100%)'],
    5 => ['#6f0000', 'linear-gradient(135deg, #200122 0%, #6f0000 100%)'],
    6 => ['#004e92', 'linear-gradient(135deg, #000428 0%, #004e92 100%)'],
    7 => ['#237a57', 'linear-gradient(135deg, #093028 0%, #237a57 100%)'],
    8 => ['#750000', 'linear-gradient(135deg, #1a0000 0%, #3d0000 50%, #750000 100%)'],
    9 => ['#16213e', 'linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%)'],
   10 => ['#035aa6', 'linear-gradient(135deg, #120136 0%, #035aa6 100%)'],
];
 
function _class_index($string) {
    return abs(crc32($string)) % count($GLOBALS['_class_color_pairs']);
}
 
// sidebar circle color — matches gradient
function generateColor($string) {
    return $GLOBALS['_class_color_pairs'][_class_index($string)][0];
}
 
// banner/card gradient
function generateGradient($string) {
    return $GLOBALS['_class_color_pairs'][_class_index($string)][1];
}