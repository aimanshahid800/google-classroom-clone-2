<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_POST['class_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$class_id || !$action) {
    header('Location: ' . BASE_URL . '/home/dashboard.php');
    exit;
}

// Verify class existence and ownership
$stmt = $pdo->prepare('SELECT owner_id FROM classes WHERE id = ?');
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if (!$class) {
    header('Location: ' . BASE_URL . '/home/dashboard.php');
    exit;
}

if ($action === 'archive') {
    if ($user['role'] === 'teacher' && $class['owner_id'] == $user['id']) {
        // Teacher archives the whole class for everyone
        $stmt = $pdo->prepare('UPDATE classes SET is_archived = 1 WHERE id = ?');
        $stmt->execute([$class_id]);
    } else {
        // Student archives the class for themselves
        $stmt = $pdo->prepare('UPDATE class_members SET is_archived = 1 WHERE class_id = ? AND user_id = ?');
        $stmt->execute([$class_id, $user['id']]);
    }
} elseif ($action === 'restore') {
    if ($user['role'] === 'teacher' && $class['owner_id'] == $user['id']) {
        // Teacher restores the whole class for everyone
        $stmt = $pdo->prepare('UPDATE classes SET is_archived = 0 WHERE id = ?');
        $stmt->execute([$class_id]);
    } else {
        // Student restores the class for themselves
        $stmt = $pdo->prepare('UPDATE class_members SET is_archived = 0 WHERE class_id = ? AND user_id = ?');
        $stmt->execute([$class_id, $user['id']]);
    }
} elseif ($action === 'delete') {
    if ($user['role'] === 'teacher' && $class['owner_id'] == $user['id']) {
        // Teacher deletes the whole class
        $stmt = $pdo->prepare('DELETE FROM classes WHERE id = ?');
        $stmt->execute([$class_id]);
    } else {
        // Student removes themselves from the class
        $stmt = $pdo->prepare('DELETE FROM class_members WHERE class_id = ? AND user_id = ?');
        $stmt->execute([$class_id, $user['id']]);
    }
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>
