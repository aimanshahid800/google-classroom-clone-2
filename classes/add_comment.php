<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/../config.php';

ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']); 
    exit;
}

$user = current_user();
$entity_id   = $_POST['entity_id']   ?? null;
$entity_type = $_POST['entity_type'] ?? null;
$message     = trim($_POST['message'] ?? '');

if (!$entity_id || !$entity_type || empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']); 
    exit;
}

try {
    if ($entity_type === 'announcement') {
        $stmt = $pdo->prepare('INSERT INTO comments (announcement_id, user_id, message) VALUES (?, ?, ?)');
        $stmt->execute([$entity_id, $user['id'], $message]);
    } elseif ($entity_type === 'assignment') {
        $stmt = $pdo->prepare('INSERT INTO comments (assignment_id, user_id, message) VALUES (?, ?, ?)');
        $stmt->execute([$entity_id, $user['id'], $message]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid entity type']);
        exit;
    }
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
