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

// Get assignments
$stmt = $pdo->prepare('SELECT * FROM assignments WHERE class_id = ? ORDER BY due_date ASC');
$stmt->execute([$class_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $pageTitle = 'Classwork | Classroom Clone'; include __DIR__ . '/../includes/header.php'; ?>
    <style>
        .assignment {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary);
        }
        .assignment-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text);
            margin-bottom: 8px;
        }
        .assignment-meta {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <?php
            $classMeta = ['&#x1F468;&#x200D;&#x1F3EB; ' . htmlspecialchars($class['teacher_name'])];
            include __DIR__ . '/../includes/class_header.php';
            $activeTab = 'classwork';
            include __DIR__ . '/../includes/class_tabs.php';
            ?>

            <div style="max-width: 800px;">
                <?php if ($user['role'] === 'teacher'): ?>
                    <div style="margin-bottom: 24px;">
                        <a href="/Uni-Team-Project/google-classroom-clone-2/assignments/create.php?class_id=<?php echo $class_id; ?>" style="display: inline-block; padding: 10px 20px; background: var(--primary); color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">+ Create Assignment</a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($assignments)): ?>
                    <?php foreach ($assignments as $assign): 
                        // Get submission status for this user
                        $stmt = $pdo->prepare('SELECT status FROM submissions WHERE assignment_id = ? AND user_id = ?');
                        $stmt->execute([$assign['id'], $user['id']]);
                        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
                        $status = $submission ? $submission['status'] : 'missing';
                    ?>
                        <div class="assignment">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <div class="assignment-title"><?php echo htmlspecialchars($assign['title']); ?></div>
                                    <div class="assignment-meta">
                                        <?php if ($assign['topic']): ?>
                                            <span>&#x1F4C1; <?php echo htmlspecialchars($assign['topic']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($assign['due_date']): ?>
                                            <span>&#x1F4C5; Due: <?php echo format_date($assign['due_date']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($user['role'] === 'student'): ?>
                                    <div style="text-align: right;">
                                        <div class="status-badge status-<?php echo strtolower(str_replace('_', '-', $status)); ?>" style="margin-bottom: 8px;">
                                            <?php echo format_status($status); ?>
                                        </div>
                                        <a href="/Uni-Team-Project/google-classroom-clone-2/assignments/submit.php?assignment_id=<?php echo $assign['id']; ?>&class_id=<?php echo $class_id; ?>" style="display: inline-block; padding: 8px 12px; background: var(--primary); color: white; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;">
                                            <?php echo $submission ? 'View' : 'Submit'; ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <a href="/Uni-Team-Project/google-classroom-clone-2/assignments/view_work.php?assignment_id=<?php echo $assign['id']; ?>&class_id=<?php echo $class_id; ?>" style="display: inline-block; padding: 8px 12px; background: var(--primary); color: white; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;">View Submissions</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">
                        <p>&#x1F4CB; No assignments yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
