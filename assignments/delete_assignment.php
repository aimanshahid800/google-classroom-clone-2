<?php
require_once __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json');

$user = current_user();
$id = $_POST['assignment_id'] ?? null;

if (!$id || $user['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Pehle submissions delete karo
    $stmt = $pdo->prepare('DELETE FROM submissions WHERE assignment_id = ?');
    $stmt->execute([$id]);

    // Phir assignment delete karo
    $stmt = $pdo->prepare('DELETE FROM assignments WHERE id = ? AND class_id IN (SELECT id FROM classes WHERE owner_id = ?)');
    $stmt->execute([$id, $user['id']]);

    echo json_encode(['success' => $stmt->rowCount() > 0]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>