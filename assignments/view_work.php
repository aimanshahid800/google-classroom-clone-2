<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$assignment_id = $_GET['assignment_id'] ?? null;
$class_id = $_GET['class_id'] ?? null;

if (!$assignment_id || !$class_id) {
    die('Invalid request.');
}

// Verify user is a teacher in this class
$stmt = $pdo->prepare('
    SELECT cm.id FROM class_members cm
    WHERE cm.class_id = ? AND cm.user_id = ? AND cm.role = "teacher"
');
$stmt->execute([$class_id, $user['id']]);
if (!$stmt->fetch()) {
    die('Only teachers can view submissions.');
}

// Get assignment details
$stmt = $pdo->prepare('SELECT a.*, c.name as class_name FROM assignments a JOIN classes c ON a.class_id = c.id WHERE a.id = ? AND a.class_id = ?');
$stmt->execute([$assignment_id, $class_id]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    die('Assignment not found.');
}

// Get all submissions for this assignment
$stmt = $pdo->prepare('
    SELECT s.*, u.name, u.email
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    WHERE s.assignment_id = ?
    ORDER BY s.submitted_at DESC
');
$stmt->execute([$assignment_id]);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all students in class (to see who hasn't submitted)
$stmt = $pdo->prepare('
    SELECT u.id, u.name, u.email
    FROM class_members cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.class_id = ? AND cm.role = "student"
    ORDER BY u.name ASC
');
$stmt->execute([$class_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark submission as done or update grade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['mark_done']) || isset($_POST['update_grade']))) {
    $submission_id = $_POST['submission_id'];
    try {
        if (isset($_POST['mark_done'])) {
            $stmt = $pdo->prepare('UPDATE submissions SET status = ? WHERE id = ?');
            $stmt->execute(['done', $submission_id]);
        } elseif (isset($_POST['update_grade'])) {
            $grade = $_POST['grade'];
            $stmt = $pdo->prepare('UPDATE submissions SET grade = ? WHERE id = ?');
            $stmt->execute([$grade, $submission_id]);
        }
        header('Location: view_work.php?assignment_id=' . $assignment_id . '&class_id=' . $class_id);
        exit;
    } catch (PDOException $e) {
        $error = 'Error updating submission: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .container {
            max-width: 900px;
            margin: 40px auto;
        }
        .assignment-header {
            background: #e9eef6;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        [data-theme="dark"] .assignment-header {
            background: #202125;
        }
        .assignment-header h1 {
            margin: 0 0 12px;
            color: var(--primary);
        }
        .assignment-meta {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--muted);
        }
        .submissions-container {
            background: #e9eef6;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        [data-theme="dark"] .submissions-container {
            background: #202125;
        }
        .submissions-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            background: #e9eef6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        [data-theme="dark"] .submissions-header {
            background: #202125;
        }
        .submission-item {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }
        .submission-item:last-child {
            border-bottom: none;
        }
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .student-name {
            font-weight: 600;
            color: var(--text);
        }
        .student-email {
            font-size: 12px;
            color: var(--muted);
            margin-top: 4px;
        }
        .submission-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-handed-in {
            background: #fff3cd;
            color: #856404;
        }
        .status-done {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-missing {
            background: #ffebee;
            color: #c62828;
        }
        .submission-content {
            background: var(--surface-alt);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 13px;
            color: var(--text);
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        .submission-time {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 12px;
        }
        .mark-done-btn {
            padding: 6px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .mark-done-btn:hover {
            background: #1765cc;
        }
        .mark-done-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .empty-message {
            padding: 40px;
            text-align: center;
            color: var(--text);
            background: #f1f3f4;
            border-radius: 12px;
        }
        [data-theme="dark"] .empty-message {
            background: #2d2e31;
            color: white;
            border-radius: 12px;
        }
        .missing-students {
            margin-top: 24px;
            padding: 20px;
            background: #ffebee;
            border-radius: 12px;
        }
        [data-theme="dark"] .missing-students {
            background: #880005;
            color: white;
        }
        .missing-students h3 {
            margin: 0 0 12px;
            color: #c62828;
        }
        [data-theme="dark"] .missing-students h3 {
            color: white;
        }
        .missing-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .missing-list li {
            padding: 8px 0;
            color: #c62828;
            font-size: 13px;
        }
        [data-theme="dark"] .missing-list li {
            color: white;
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>
            
            <div style="padding: 20px 24px 0; text-align: left;">
                <a href="<?php echo BASE_URL; ?>/classes/classwork.php?class_id=<?php echo $class_id; ?>" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: var(--text); font-size: 14px; font-weight: 500; transition: opacity 0.2s;" class="back-link">
                    <img src="<?php echo BASE_URL; ?>/icons/goback.png" style="width:16px; height:16px; opacity:0.8;" alt="">
                    Back To Classwork
                </a>
            </div>

            <div class="container">
                <div class="assignment-header">
                    <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
                    <div class="assignment-meta">
                        <span><img src="<?php echo BASE_URL; ?>/icons/books (1).png" style="width:14px; height:14px; vertical-align: middle; margin-right: 4px;" alt="book"> Class: <?php echo htmlspecialchars($assignment['class_name']); ?></span>
                        <?php if ($assignment['due_date']): ?>
                            <span><img src="<?php echo BASE_URL; ?>/icons/calender-icon.svg" style="width:14px; height:14px; vertical-align: middle; margin-right: 4px;" alt="cal"> Due: <?php echo date('M d, Y • H:i', strtotime($assignment['due_date'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="submissions-container">
                    <div class="submissions-header">
                        <div><strong>Submissions: <?php echo count($submissions); ?>/<?php echo count($students); ?></strong></div>
                    </div>

                    <?php if (!empty($submissions)): ?>
                        <?php foreach ($submissions as $sub): ?>
                            <div class="submission-item">
                                <div class="submission-header">
                                    <div>
                                        <div class="student-name"><?php echo htmlspecialchars($sub['name']); ?></div>
                                        <div class="student-email"><?php echo htmlspecialchars($sub['email']); ?></div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div class="submission-status status-<?php echo strtolower(str_replace('_', '-', $sub['status'])); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $sub['status'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="submission-time">
                                    Submitted: <?php echo date('M d, Y • H:i', strtotime($sub['submitted_at'])); ?>
                                </div>
                                <?php if (!empty($sub['content'])): ?>
                                <div class="submission-content">
                                    <?php echo htmlspecialchars($sub['content']); ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($sub['file_path'])): ?>
                                <div style="margin-bottom: 12px; padding: 10px 14px; background: #f0f4f9; border-radius: 8px; display: flex; align-items: center; gap: 8px;">
                                    <span>&#128206;</span>
                                    <a href="<?php echo BASE_URL . '/' . htmlspecialchars($sub['file_path']); ?>" target="_blank" style="color: var(--primary); font-weight: 500; font-size: 13px;">
                                        <?php echo htmlspecialchars(basename($sub['file_path'])); ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                                 <div style="display: flex; align-items: center; gap: 12px; margin-top: 12px;">
                                     <?php if ($sub['status'] !== 'done'): ?>
                                         <form method="POST" style="display: inline;">
                                             <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                             <button type="submit" name="mark_done" class="mark-done-btn">Mark as Done</button>
                                         </form>
                                     <?php endif; ?>
                                     
                                     <form method="POST" style="display: inline-flex; align-items: center; gap: 8px;">
                                         <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                         <input type="text" name="grade" value="<?php echo htmlspecialchars($sub['grade'] ?? ''); ?>" placeholder="Grade" style="width: 60px; padding: 5px; border: 1px solid var(--border); border-radius: 4px;">
                                         <button type="submit" name="update_grade" class="mark-done-btn" style="background: #5f6368;">Save Grade</button>
                                     </form>
                                 </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-message">
                            <p><img src="<?php echo BASE_URL; ?>/icons/books (2).png" style="width:24px; height:24px; vertical-align: middle; margin-right: 8px;" alt="book"> No submissions yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                $submitted_ids = array_map(function($s) { return $s['user_id']; }, $submissions);
                $missing_students = array_filter($students, function($s) use ($submitted_ids) { return !in_array($s['id'], $submitted_ids); });
                ?>

                <?php if (!empty($missing_students)): ?>
                    <div class="missing-students">
                        <h3>⚠️ Missing Submissions (<?php echo count($missing_students); ?>)</h3>
                        <ul class="missing-list">
                            <?php foreach ($missing_students as $student): ?>
                                <li>👤 <?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['email']); ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
