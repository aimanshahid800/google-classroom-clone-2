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

// Helper: verify user is a member of a class (or die)
function require_class_member($pdo, $class_id, $user_id) {
    $stmt = $pdo->prepare('SELECT id FROM class_members WHERE class_id = ? AND user_id = ?');
    $stmt->execute([$class_id, $user_id]);
    if (!$stmt->fetch()) {
        die('You do not have access to this class.');
    }
}

// Helper: verify user is a teacher in a class (or die)
function require_teacher($pdo, $class_id, $user_id, $message = 'Only teachers can perform this action.') {
    $stmt = $pdo->prepare('
        SELECT cm.id FROM class_members cm
        WHERE cm.class_id = ? AND cm.user_id = ? AND cm.role = "teacher"
    ');
    $stmt->execute([$class_id, $user_id]);
    if (!$stmt->fetch()) {
        die($message);
    }
}

// Helper: fetch class info with teacher name
function get_class_info($pdo, $class_id) {
    $stmt = $pdo->prepare('
        SELECT c.*, u.name AS teacher_name
        FROM classes c
        JOIN users u ON c.owner_id = u.id
        WHERE c.id = ?
    ');
    $stmt->execute([$class_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper: format a datetime string for display
function format_date($datetime) {
    return date('M d, Y • H:i', strtotime($datetime));
}

// Helper: format a status slug for display (e.g. "handed_in" → "Handed in")
function format_status($status) {
    return ucfirst(str_replace('_', ' ', $status));
}
