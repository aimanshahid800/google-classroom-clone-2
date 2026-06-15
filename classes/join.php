<?php
require_once __DIR__ . '/../config.php';
require_login();
 
$user = current_user();
 
// ── AJAX request ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $code = strtoupper(trim($_POST['code'] ?? ''));
 
    if (empty($code)) {
        echo json_encode(['success' => false, 'error' => 'Class code is required.']);
        exit;
    }
 
    try {
        $stmt = $pdo->prepare('SELECT id, name FROM classes WHERE code = ?');
        $stmt->execute([$code]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
 
        if (!$class) {
            echo json_encode(['success' => false, 'error' => 'That class code is invalid. Ask your teacher to check it.']);
            exit;
        }
 
        $stmt = $pdo->prepare('SELECT id FROM class_members WHERE class_id = ? AND user_id = ?');
        $stmt->execute([$class['id'], $user['id']]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'You are already a member of this class.']);
            exit;
        }
 
        $stmt = $pdo->prepare('INSERT INTO class_members (class_id, user_id, role) VALUES (?, ?, ?)');
        $stmt->execute([$class['id'], $user['id'], 'student']);
        echo json_encode([
            'success' => true,
            'redirect' => BASE_URL . '/classes/stream.php?class_id=' . $class['id']
        ]);
        exit;
 
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
        exit;
    }
}
 
// ── Direct page access — redirect to dashboard ──
header('Location: ' . BASE_URL . '/home/dashboard.php');
exit;
