<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once __DIR__ . '/../config.php';

ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['comments' => []]);
    exit;
}

$entity_id   = $_GET['entity_id']   ?? null;
$entity_type = $_GET['entity_type'] ?? null;

if (!$entity_id || !$entity_type) {
    echo json_encode(['comments' => []]);
    exit;
}

try {
    if ($entity_type === 'announcement') {
        $stmt = $pdo->prepare('SELECT c.message, u.name, c.created_at FROM comments c JOIN users u ON c.user_id = u.id WHERE c.announcement_id = ? ORDER BY c.created_at ASC');
    } elseif ($entity_type === 'assignment') {
        $stmt = $pdo->prepare('SELECT c.message, u.name, c.created_at FROM comments c JOIN users u ON c.user_id = u.id WHERE c.assignment_id = ? ORDER BY c.created_at ASC');
    } else {
        echo json_encode(['comments' => []]);
        exit;
    }

    $stmt->execute([$entity_id]);
    echo json_encode(['comments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (PDOException $e) {
    echo json_encode(['comments' => [], 'error' => $e->getMessage()]);
}
