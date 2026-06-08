<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = filter_input(INPUT_GET, 'class_id', FILTER_VALIDATE_INT);

if (!$class_id) {
    die('Class not found.');
}

// Verify user is a member of this class
$stmt = $pdo->prepare('SELECT id FROM class_members WHERE class_id = ? AND user_id = ?');
$stmt->execute([$class_id, $user['id']]);
if (!$stmt->fetch()) {
    die('You do not have access to this class.');
}

// Get class info
$stmt = $pdo->prepare('SELECT c.*, u.name AS teacher_name FROM classes c JOIN users u ON c.owner_id = u.id WHERE c.id = ?');
$stmt->execute([$class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

// Get assignments
$stmt = $pdo->prepare('SELECT * FROM assignments WHERE class_id = ? ORDER BY due_date ASC');
$stmt->execute([$class_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classwork | Classroom Clone</title>
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
        .empty-message {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
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
                </div>
            </div>

            <div class="tabs">
                <a href="stream.php?class_id=<?php echo (int)$class_id; ?>" class="tab">Stream</a>
                <a href="classwork.php?class_id=<?php echo (int)$class_id; ?>" class="tab active">Classwork</a>
                <a href="people.php?class_id=<?php echo (int)$class_id; ?>" class="tab">People</a>
            </div>

            <div style="max-width: 800px;">
                <?php if ($user['role'] === 'teacher'): ?>
                    <div style="margin-bottom: 24px;">
                        <a href="/Uni-Team-Project/google-classroom-clone-2/assignments/create.php?class_id=<?php echo (int)$class_id; ?>" style="display: inline-block; padding: 10px 20px; background: var(--primary); color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">+ Create Assignment</a>
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
                                            <span>📁 <?php echo htmlspecialchars($assign['topic']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($assign['due_date']): ?>
                                            <span>📅 Due: <?php echo date('M d, Y • H:i', strtotime($assign['due_date'])); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($user['role'] === 'student'): ?>
                                    <div style="text-align: right;">
                                        <div style="font-size: 12px; padding: 6px 12px; border-radius: 6px; background: <?php echo $status === 'handed_in' ? '#e8f5e9' : ($status === 'done' ? '#e8f5e9' : '#ffebee'); ?>; color: <?php echo $status === 'handed_in' ? '#2e7d32' : ($status === 'done' ? '#2e7d32' : '#c62828'); ?>; font-weight: 600; margin-bottom: 8px;">
                                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                        </div>
                                        <a href="/Uni-Team-Project/google-classroom-clone-2/assignments/submit.php?assignment_id=<?php echo $assign['id']; ?>&class_id=<?php echo (int)$class_id; ?>" style="display: inline-block; padding: 8px 12px; background: var(--primary); color: white; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;">
                                            <?php echo $submission ? 'View' : 'Submit'; ?>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <a href="/Uni-Team-Project/google-classroom-clone-2/assignments/view_work.php?assignment_id=<?php echo $assign['id']; ?>&class_id=<?php echo (int)$class_id; ?>" style="display: inline-block; padding: 8px 12px; background: var(--primary); color: white; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600;">View Submissions</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">
                        <p>📋 No assignments yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
