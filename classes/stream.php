<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die('Class not found.');
}

// Verify user is a member of this class
$stmt = $pdo->prepare('
    SELECT cm.id FROM class_members cm
    WHERE cm.class_id = ? AND cm.user_id = ?
');
$stmt->execute([$class_id, $user['id']]);
if (!$stmt->fetch()) {
    die('You do not have access to this class.');
}

// Get class info
$stmt = $pdo->prepare('
    SELECT c.*, u.name AS teacher_name
    FROM classes c
    JOIN users u ON c.owner_id = u.id
    WHERE c.id = ?
');
$stmt->execute([$class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle new announcement
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'teacher') {
    $message = trim($_POST['message'] ?? '');
    if (empty($message)) {
        $errors[] = 'Announcement message is required.';
    } else {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO announcements (class_id, user_id, message)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$class_id, $user['id'], $message]);
            header('Location: stream.php?class_id=' . $class_id);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Error posting announcement: ' . $e->getMessage();
        }
    }
}

// Get announcements
$stmt = $pdo->prepare('
    SELECT a.*, u.name AS author_name
    FROM announcements a
    JOIN users u ON a.user_id = u.id
    WHERE a.class_id = ?
    ORDER BY a.created_at DESC
');
$stmt->execute([$class_id]);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($class['name']); ?> | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .class-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 24px;
            border-radius: 12px;
            margin-bottom: 32px;
        }
        .class-header h1 {
            margin: 0;
            font-size: 32px;
        }
        .class-header p {
            margin: 8px 0 0;
            opacity: 0.9;
        }
        .class-meta {
            display: flex;
            gap: 24px;
            margin-top: 16px;
            font-size: 14px;
        }
        .tabs {
            display: flex;
            gap: 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .tab {
            padding: 12px 0;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            color: var(--muted);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .tab:hover {
            color: var(--text);
        }
        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .stream-container {
            max-width: 800px;
        }
        .post-form {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .post-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 12px;
        }
        .post-form textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        .post-form button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .post-form button:hover {
            background: #1765cc;
        }
        .announcement {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .announcement-header {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            align-items: center;
        }
        .announcement-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        .announcement-info {
            flex: 1;
        }
        .announcement-author {
            font-weight: 600;
            color: var(--text);
        }
        .announcement-time {
            font-size: 12px;
            color: var(--muted);
        }
        .announcement-message {
            color: var(--text);
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .empty-message {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }
        .alert {
            padding: 12px;
            background: #ffebee;
            color: #c62828;
            border-radius: 8px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <div class="class-header">
                <h1><?php echo htmlspecialchars($class['name']); ?></h1>
                <div class="class-meta">
                    <?php if ($class['section']): ?>
                        <span>Section: <?php echo htmlspecialchars($class['section']); ?></span>
                    <?php endif; ?>
                    <span>👨‍🏫 <?php echo htmlspecialchars($class['teacher_name']); ?></span>
                    <span>Code: <?php echo htmlspecialchars($class['code']); ?></span>
                </div>
            </div>

            <div class="tabs">
                <a href="stream.php?class_id=<?php echo $class_id; ?>" class="tab active">Stream</a>
                <a href="classwork.php?class_id=<?php echo $class_id; ?>" class="tab">Classwork</a>
                <a href="people.php?class_id=<?php echo $class_id; ?>" class="tab">People</a>
            </div>

            <div class="stream-container">
                <?php if (!empty($errors)): ?>
                    <div class="alert">
                        <?php foreach ($errors as $error): ?>
                            <div>✗ <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($user['role'] === 'teacher'): ?>
                    <div class="post-form">
                        <form method="POST">
                            <textarea name="message" placeholder="Share an announcement with your class..." required></textarea>
                            <button type="submit">Post</button>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $ann): ?>
                        <div class="announcement">
                            <div class="announcement-header">
                                <div class="announcement-avatar">
                                    <?php echo strtoupper(substr($ann['author_name'], 0, 1)); ?>
                                </div>
                                <div class="announcement-info">
                                    <div class="announcement-author"><?php echo htmlspecialchars($ann['author_name']); ?></div>
                                    <div class="announcement-time">
                                        <?php echo date('M d, Y • H:i', strtotime($ann['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="announcement-message">
                                <?php echo htmlspecialchars($ann['message']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">
                        <p>📢 No announcements yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
