<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die('Class not found.');
}

require_class_member($pdo, $class_id, $user['id']);
$class = get_class_info($pdo, $class_id);

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

<?php $pageTitle = htmlspecialchars($class['name']) . ' | Classroom Clone'; include __DIR__ . '/../includes/header.php'; ?>
    <style>
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
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <?php
            $classMeta = [
                '&#x1F468;&#x200D;&#x1F3EB; ' . htmlspecialchars($class['teacher_name']),
                'Code: ' . htmlspecialchars($class['code'])
            ];
            include __DIR__ . '/../includes/class_header.php';
            $activeTab = 'stream';
            include __DIR__ . '/../includes/class_tabs.php';
            ?>

            <div class="stream-container">
                <?php include __DIR__ . '/../includes/alerts.php'; ?>

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
                                        <?php echo format_date($ann['created_at']); ?>
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
                        <p>&#x1F4E2; No announcements yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
