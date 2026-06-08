<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$tab = $_GET['tab'] ?? 'assigned';
$filter_class = $_GET['class_id'] ?? 'all';

// Get user's enrolled classes for filter dropdown
$stmt = $pdo->prepare('
    SELECT c.id, c.name
    FROM classes c
    JOIN class_members cm ON c.id = cm.class_id
    WHERE cm.user_id = ?
    ORDER BY c.name ASC
');
$stmt->execute([$user['id']]);
$enrolled_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build query based on tab
$class_filter = '';
$params = [$user['id']];

if ($filter_class !== 'all') {
    $class_filter = ' AND a.class_id = ?';
    $params[] = $filter_class;
}

if ($tab === 'assigned') {
    // Assignments that are not submitted yet
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.due_date, a.topic, c.name as class_name, c.id as class_id
        FROM assignments a
        JOIN classes c ON a.class_id = c.id
        JOIN class_members cm ON c.id = cm.class_id AND cm.user_id = ?
        WHERE a.id NOT IN (
            SELECT assignment_id FROM submissions WHERE user_id = ?
        )
        $class_filter
        ORDER BY a.due_date ASC
    ");
    $params_assigned = [$user['id'], $user['id']];
    if ($filter_class !== 'all') {
        $params_assigned[] = $filter_class;
    }
    $stmt->execute($params_assigned);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($tab === 'missing') {
    // Assignments past due date and not submitted
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.due_date, a.topic, c.name as class_name, c.id as class_id
        FROM assignments a
        JOIN classes c ON a.class_id = c.id
        JOIN class_members cm ON c.id = cm.class_id AND cm.user_id = ?
        WHERE a.due_date < NOW()
        AND a.id NOT IN (
            SELECT assignment_id FROM submissions WHERE user_id = ?
        )
        $class_filter
        ORDER BY a.due_date DESC
    ");
    $params_missing = [$user['id'], $user['id']];
    if ($filter_class !== 'all') {
        $params_missing[] = $filter_class;
    }
    $stmt->execute($params_missing);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Done - submitted assignments
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.due_date, a.topic, c.name as class_name, c.id as class_id, s.status, s.submitted_at
        FROM assignments a
        JOIN classes c ON a.class_id = c.id
        JOIN submissions s ON a.id = s.assignment_id AND s.user_id = ?
        JOIN class_members cm ON c.id = cm.class_id AND cm.user_id = ?
        $class_filter
        ORDER BY s.submitted_at DESC
    ");
    $params_done = [$user['id'], $user['id']];
    if ($filter_class !== 'all') {
        $params_done[] = $filter_class;
    }
    $stmt->execute($params_done);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Group items by time period
function groupByPeriod($items) {
    $groups = ['This week' => [], 'Last week' => [], 'Earlier' => []];
    $now = new DateTime();
    $weekStart = (clone $now)->modify('monday this week');
    $lastWeekStart = (clone $weekStart)->modify('-7 days');

    foreach ($items as $item) {
        $date = $item['due_date'] ? new DateTime($item['due_date']) : null;
        if (!$date) {
            $groups['Earlier'][] = $item;
        } elseif ($date >= $weekStart) {
            $groups['This week'][] = $item;
        } elseif ($date >= $lastWeekStart) {
            $groups['Last week'][] = $item;
        } else {
            $groups['Earlier'][] = $item;
        }
    }
    return array_filter($groups);
}

$grouped = groupByPeriod($items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To Do | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .todo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .todo-header h1 {
            margin: 0;
            font-size: 28px;
        }
        .filter-dropdown {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        .filter-dropdown:focus {
            outline: none;
            border-color: var(--primary);
        }
        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .tab {
            padding: 12px 24px;
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
        .group-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 24px 0 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
        }
        .todo-item {
            background: white;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.2s;
        }
        .todo-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .todo-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }
        .todo-info {
            flex: 1;
        }
        .todo-title {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }
        .todo-meta {
            font-size: 13px;
            color: var(--muted);
            display: flex;
            gap: 12px;
        }
        .todo-due {
            color: var(--muted);
        }
        .todo-due.overdue {
            color: #c62828;
            font-weight: 600;
        }
        .todo-status {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .status-handed-in {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-done {
            background: #e3f2fd;
            color: #1565c0;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .submit-link {
            padding: 6px 14px;
            background: var(--primary);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .submit-link:hover {
            background: #1765cc;
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <div class="todo-header">
                <h1>To Do</h1>
                <form method="GET" style="display: flex; gap: 8px; align-items: center;">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                    <select name="class_id" class="filter-dropdown" onchange="this.form.submit()">
                        <option value="all">All classes</option>
                        <?php foreach ($enrolled_classes as $ec): ?>
                            <option value="<?php echo $ec['id']; ?>" <?php echo $filter_class == $ec['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ec['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="tabs">
                <a href="?tab=assigned&class_id=<?php echo htmlspecialchars($filter_class); ?>" class="tab <?php echo $tab === 'assigned' ? 'active' : ''; ?>">Assigned</a>
                <a href="?tab=missing&class_id=<?php echo htmlspecialchars($filter_class); ?>" class="tab <?php echo $tab === 'missing' ? 'active' : ''; ?>">Missing</a>
                <a href="?tab=done&class_id=<?php echo htmlspecialchars($filter_class); ?>" class="tab <?php echo $tab === 'done' ? 'active' : ''; ?>">Done</a>
            </div>

            <div style="max-width: 800px;">
                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <?php if ($tab === 'assigned'): ?>
                                &#128203;
                            <?php elseif ($tab === 'missing'): ?>
                                &#9989;
                            <?php else: ?>
                                &#127881;
                            <?php endif; ?>
                        </div>
                        <p>
                            <?php if ($tab === 'assigned'): ?>
                                No pending assignments. You're all caught up!
                            <?php elseif ($tab === 'missing'): ?>
                                No missing assignments. Great job!
                            <?php else: ?>
                                No completed assignments yet.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($grouped as $period => $group_items): ?>
                        <div class="group-title"><?php echo $period; ?></div>
                        <?php foreach ($group_items as $item): ?>
                            <div class="todo-item">
                                <div class="todo-icon">
                                    <?php echo $tab === 'done' ? '&#10003;' : '&#128221;'; ?>
                                </div>
                                <div class="todo-info">
                                    <div class="todo-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <div class="todo-meta">
                                        <span><?php echo htmlspecialchars($item['class_name']); ?></span>
                                        <?php if ($item['due_date']): ?>
                                            <?php
                                            $due = new DateTime($item['due_date']);
                                            $now = new DateTime();
                                            $overdue = $due < $now && $tab !== 'done';
                                            ?>
                                            <span class="todo-due <?php echo $overdue ? 'overdue' : ''; ?>">
                                                Due: <?php echo $due->format('M j, g:i A'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="todo-due">No due date</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($tab === 'done' && isset($item['status'])): ?>
                                    <span class="todo-status status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $item['status'])); ?>
                                    </span>
                                <?php elseif ($tab !== 'done'): ?>
                                    <a href="<?php echo BASE_URL; ?>/assignments/submit.php?assignment_id=<?php echo $item['id']; ?>&class_id=<?php echo $item['class_id']; ?>" class="submit-link">Submit</a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
