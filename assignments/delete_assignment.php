<?php
require_once __DIR__ . '/../config.php';
require_login();
$user = current_user();
$id = $_POST['assignment_id'] ?? null;
if (!$id || $user['role'] !== 'teacher') { 
    echo json_encode(['success'=>false]); exit; 
}
$stmt = $pdo->prepare('DELETE FROM assignments WHERE id = ? AND class_id IN (SELECT id FROM classes WHERE owner_id = ?)');
$stmt->execute([$id, $user['id']]);
echo json_encode(['success' => $stmt->rowCount() > 0]);
?>