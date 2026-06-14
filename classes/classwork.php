<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die('Class not found.');
}

$stmt = $pdo->prepare('SELECT id FROM class_members WHERE class_id = ? AND user_id = ?');
$stmt->execute([$class_id, $user['id']]);
if (!$stmt->fetch()) {
    die('You do not have access to this class.');
}

$stmt = $pdo->prepare('SELECT c.*, u.name AS teacher_name FROM classes c JOIN users u ON c.owner_id = u.id WHERE c.id = ?');
$stmt->execute([$class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

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
        .tab:hover { color: var(--text); }
        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .assignment {
            background: #e9eef6;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            box-sizing: border-box;
            width: 100%;
        }
        [data-theme="dark"] .assignment {
            background: #202125;
        }

        .assignment-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            width: 100%;
        }

        .assignment-left { flex: 1; min-width: 0; }

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
            flex-wrap: wrap;
        }
        .assignment-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .assignment-meta img {
            width: 14px;
            height: 14px;
        }

        .assignment-right {
            display: flex;
            flex-direction: column;
            align-items: center
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-action {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-action-sm {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        [data-theme="dark"] .btn-action,
        [data-theme="dark"] .btn-action-sm {
            background: #173149;
        }

        .status-badge {
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
        }
        .status-handed_in, .status-done {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-missing {
            background: #880005;
            color: white;
        }
        [data-theme="dark"] .status-missing {
            background: #ffd7d7;
            color: #c62828;
        }

        .details-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            color: var(--primary);
            font-size: 13px;
            font-weight: 500;
            margin-top: 16px;
            user-select: none;
            width: fit-content;
            transition: color 0.2s;
        }
        .details-toggle:hover { color: #1557b0; }
        [data-theme="dark"] .details-toggle { color: #8ab4f8; }
        [data-theme="dark"] .details-toggle:hover { color: #aecbff; }
        .details-toggle img {
            width: 12px;
            height: 12px;
            transition: transform 0.3s ease;
        }
        .details-toggle.open img { transform: rotate(180deg); }

        .details-content {
            display: none;
            padding: 16px 0 0 0;
            border-top: 1px solid var(--border);
            margin-top: 12px;
        }

        .instructions-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
            margin-bottom: 6px;
        }
        .instructions-text {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .resource-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 16px;
            text-decoration: none;
            color: var(--text);
            transition: background 0.2s;
            margin-top: 12px;
        }
        .resource-box:hover { background: #f8f9fa; }
        .resource-info { display: flex; align-items: center; gap: 12px; }
        .resource-icon {
            width: 32px;
            height: 32px;
            background: #e8f0fe;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .resource-icon img { width: 20px; height: 20px; }
        .resource-name { font-size: 13px; font-weight: 500; }

        [data-theme="dark"] .resource-box { background: #11110f; border-color: #444; }
        [data-theme="dark"] .resource-box:hover { background: #1c1c1e; }
        [data-theme="dark"] .resource-icon { background: #2a2a2a; }

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

            <div class="tabs">
                <a href="stream.php?class_id=<?php echo $class_id; ?>" class="tab">Stream</a>
                <a href="classwork.php?class_id=<?php echo $class_id; ?>" class="tab active">Classwork</a>
                <a href="people.php?class_id=<?php echo $class_id; ?>" class="tab">People</a>
            </div>

            <div style="width: 100%; max-width: 900px; margin: 0 auto; padding: 0 24px; box-sizing: border-box;">

                <?php if ($user['role'] === 'teacher'): ?>
                    <div style="margin-bottom: 24px;">
                        <a href="<?php echo BASE_URL; ?>/assignments/create.php?class_id=<?php echo $class_id; ?>" class="btn-action">+ Create Assignment</a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($assignments)): ?>
                    <?php foreach ($assignments as $assign):
                        $stmt = $pdo->prepare('SELECT status, file_path FROM submissions WHERE assignment_id = ? AND user_id = ?');
                        $stmt->execute([$assign['id'], $user['id']]);
                        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
                        $status = $submission ? $submission['status'] : 'missing';
                    ?>
                        <div style="display: flex; align-items: center; gap: 24px;">
                            <div class="assignment">
                                <div class="assignment-row">
                                    <div class="assignment-left">
                                        <div class="assignment-title"><?php echo htmlspecialchars($assign['title']); ?></div>
                                        <div class="assignment-meta">
                                            <?php if ($assign['topic']): ?>
                                                <span>
                                                    <img src="<?php echo BASE_URL; ?>/icons/folder icon.svg" alt="folder">
                                                    <?php echo htmlspecialchars($assign['topic']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($assign['due_date']): ?>
                                                <span>
                                                    <img src="<?php echo BASE_URL; ?>/icons/calender-icon.svg" alt="cal">
                                                    Due: <?php echo date('M d, Y • H:i', strtotime($assign['due_date'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="assignment-right">
                                        <?php if ($user['role'] === 'student'): ?>
                                            <div class="status-badge status-<?php echo $status; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                            </div>
                                            <a href="<?php echo BASE_URL; ?>/assignments/submit.php?assignment_id=<?php echo $assign['id']; ?>&class_id=<?php echo $class_id; ?>" class="btn-action-sm">
                                                <?php echo $submission ? 'View' : 'Submit'; ?>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo BASE_URL; ?>/assignments/view_work.php?assignment_id=<?php echo $assign['id']; ?>&class_id=<?php echo $class_id; ?>" class="btn-action-sm">View Submissions</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="details-toggle" onclick="toggleDetails(this)">
                                    <img src="<?php echo BASE_URL; ?>/icons/down.png" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%223%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22m6 9 6 6 6-6%22/></svg>'" alt="arrow">
                                    View more
                                </div>
                                
                                <div class="details-content">
                                    <?php if (!empty($assign['description'])): ?>
                                        <div class="instructions-section">
                                            <div class="instructions-title">Instructions</div>
                                            <div class="instructions-text"><?php echo nl2br(htmlspecialchars($assign['description'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($assign['resource_path'])): ?>
                                        <a href="<?php echo BASE_URL . '/' . $assign['resource_path']; ?>" target="_blank" class="resource-box">
                                            <div class="resource-info">
                                                <div class="resource-icon">
                                                    <img src="<?php echo BASE_URL; ?>/icons/Clipboard.png" alt="File">
                                                </div>
                                                <span class="resource-name"><?php echo htmlspecialchars(basename($assign['resource_path'])); ?></span>
                                            </div>
                                            <img src="<?php echo BASE_URL; ?>/icons/arrow-right.png" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%2212%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22currentColor%22 stroke-width=%223%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22><path d=%22m9 18 6-6-6-6%22/></svg>'" style="width:12px;height:12px;opacity:0.5;" alt="go">
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($user['role'] === 'teacher'): ?>
                                <img src="<?php echo BASE_URL; ?>/icons/trash.svg" 
                                     style="width: 26px; height: 26px; cursor: pointer; opacity: 0.6; transition: opacity 0.2s; margin-bottom: 16px;" 
                                     onclick="deleteAssignment(<?php echo $assign['id']; ?>)" 
                                     onmouseover="this.style.opacity='1'" 
                                     onmouseout="this.style.opacity='0.6'"
                                     alt="Delete">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">
                        <p>No assignments yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    function toggleDetails(element) {
        element.classList.toggle('open');
        const content = element.nextElementSibling;
        if (content && content.classList.contains('details-content')) {
            content.style.display = content.style.display === 'block' ? 'none' : 'block';
        }
    }

    async function deleteAssignment(assignmentId) {
        if (!confirm('Are you sure you want to delete this assignment?')) return;

        try {
            const response = await fetch('<?php echo BASE_URL; ?>/assignments/delete_assignment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'assignment_id=' + assignmentId
            });
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            console.error('Error deleting assignment:', error);
            alert('A network error occurred while deleting the assignment.');
        }
    }
    </script>
</body>
</html>