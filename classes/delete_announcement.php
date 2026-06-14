<?php
require_once __DIR__ . '/../config.php';
require_login();
$user = current_user();
$id = $_POST['announcement_id'] ?? null;
if (!$id) { echo json_encode(['success'=>false]); exit; }

// teacher sirf apni delete kar sakti hai, student ka button hi nahi hai
$stmt = $pdo->prepare('DELETE FROM announcements WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);
echo json_encode(['success' => $stmt->rowCount() > 0]);
?>