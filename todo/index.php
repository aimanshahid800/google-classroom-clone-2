<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

if ($user['role'] === 'teacher') {
    header('Location: ' . BASE_URL . '/home/dashboard.php');
    exit;
}

$tab = $_GET['tab'] ?? 'assigned';
$filter_class = $_GET['class_id'] ?? 'all';

// Get enrolled classes for dropdown
$stmt = $pdo->prepare('
    SELECT c.id, c.name
    FROM classes c
    JOIN class_members cm ON c.id = cm.class_id
    WHERE cm.user_id = ?
    ORDER BY c.name ASC
');
$stmt->execute([$user['id']]);
$enrolled_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$class_filter = '';
if ($filter_class !== 'all') {
    $class_filter = ' AND a.class_id = ' . intval($filter_class);
}

if ($tab === 'assigned') {
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
    $stmt->execute([$user['id'], $user['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($tab === 'missing') {
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
    $stmt->execute([$user['id'], $user['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.due_date, a.topic, c.name as class_name, c.id as class_id,
               s.status, s.submitted_at
        FROM assignments a
        JOIN classes c ON a.class_id = c.id
        JOIN submissions s ON a.id = s.assignment_id AND s.user_id = ?
        JOIN class_members cm ON c.id = cm.class_id AND cm.user_id = ?
        $class_filter
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$user['id'], $user['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Group by time period ──
function groupByPeriod($items, $tab) {
    $now = new DateTime();
    $weekStart     = (clone $now)->modify('monday this week')->setTime(0,0,0);
    $weekEnd       = (clone $now)->modify('sunday this week')->setTime(23,59,59);
    $nextWeekStart = (clone $weekStart)->modify('+7 days');
    $nextWeekEnd   = (clone $weekEnd)->modify('+7 days');
    $lastWeekStart = (clone $weekStart)->modify('-7 days');
    $lastWeekEnd   = (clone $weekEnd)->modify('-7 days');

    if ($tab === 'assigned') {
        $groups = ['No due date' => [], 'This week' => [], 'Next week' => [], 'Later' => []];
        foreach ($items as $item) {
            if (!$item['due_date']) {
                $groups['No due date'][] = $item;
            } else {
                $due = new DateTime($item['due_date']);
                if ($due >= $weekStart && $due <= $weekEnd) {
                    $groups['This week'][] = $item;
                } elseif ($due >= $nextWeekStart && $due <= $nextWeekEnd) {
                    $groups['Next week'][] = $item;
                } else {
                    $groups['Later'][] = $item;
                }
            }
        }
        return $groups;

    } elseif ($tab === 'missing') {
        $groups = ['This week' => [], 'Last week' => [], 'Earlier' => []];
        foreach ($items as $item) {
            if (!$item['due_date']) {
                $groups['Earlier'][] = $item;
            } else {
                $due = new DateTime($item['due_date']);
                if ($due >= $weekStart && $due <= $weekEnd) {
                    $groups['This week'][] = $item;
                } elseif ($due >= $lastWeekStart && $due <= $lastWeekEnd) {
                    $groups['Last week'][] = $item;
                } else {
                    $groups['Earlier'][] = $item;
                }
            }
        }
        return $groups;

    } else {
        // done
        $groups = ['No due date' => [], 'Done early' => [], 'This week' => [], 'Last week' => [], 'Earlier' => []];
        foreach ($items as $item) {
            if (!$item['due_date']) {
                $groups['No due date'][] = $item;
            } else {
                $due = new DateTime($item['due_date']);
                if ($due > $now) {
                    $groups['Done early'][] = $item;
                } elseif ($due >= $weekStart && $due <= $weekEnd) {
                    $groups['This week'][] = $item;
                } elseif ($due >= $lastWeekStart && $due <= $lastWeekEnd) {
                    $groups['Last week'][] = $item;
                } else {
                    $groups['Earlier'][] = $item;
                }
            }
        }
        return $groups;
    }
}

$grouped = groupByPeriod($items, $tab);
$total_items = count($items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To Do | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* ── Layout ── */
        .todo-page {
            margin: 0;
            padding: 0 16px 48px;
        }

        .todo-main-content {
            padding: 0 100px;
        }
        .todo-page-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text);
            margin: 0 0 0;
            padding: 20px 0 16px;
        }

        /* ── Tabs ── */
        .todo-tabs {
            display: flex;
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
            font-size: 14px;
            transition: all 0.15s;
        }
        .tab:hover { color: var(--text); background: rgba(0,0,0,0.03); }
        [data-theme="dark"] .tab:hover { background: rgba(255,255,255,0.04); }
        .tab.active {
            color: var(--primary, #1a73e8);
            border-bottom-color: var(--primary, #1a73e8);
        }

        /* ── Custom Dropdown (All classes) ── */
        .class-filter-wrap {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        .class-filter-btn {
            display: flex;
            align-items: center;
            gap: 32px;
            padding: 20px 16px;
            min-width: 300px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface);
            color: var(--text);
            font-size: 18px;
            cursor: pointer;
            user-select: none;
            transition: border-color 0.15s;
        }
        .class-filter-btn:hover,
        .class-filter-btn.open {
            border-color: var(--primary, #1a73e8);
            border-width: 3px;
            font-weight: 600;
        }
        .class-filter-btn.open {
            box-shadow: 0 0 0 2px rgba(26,115,232,0.18);
        }
        .class-filter-btn .arrow {
            width: 10px;
            height: 16px;
            margin-left: auto;
            transition: transform 0.2s;
            flex-shrink: 0;
        }
        .class-filter-btn.open .arrow {
            transform: rotate(180deg);
        }
        .class-filter-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            min-width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 4px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            z-index: 100;
            overflow: hidden;
        }
        .class-filter-dropdown.open { display: block; }
        .class-filter-option {
            padding: 10px 16px;
            font-size: 14px;
            color: var(--text);
            cursor: pointer;
            transition: background 0.1s;
        }
        .class-filter-option:hover { background: rgba(0,0,0,0.05); }
        .class-filter-option.selected { background: rgba(26,115,232,0.08); }
        [data-theme="dark"] .class-filter-option:hover { background: rgba(255,255,255,0.07); }
        [data-theme="dark"] .class-filter-option.selected { background: rgba(26,115,232,0.15); }

        /* ── Group section ── */
        .group-section { margin-bottom: 2px; }
        .group-header {
            display: flex;
            align-items: center;
            padding: 14px 0;
            cursor: pointer;
            user-select: none;
        }
        .group-header:hover { opacity: 0.8; }
        .group-label {
            font-size: 22px;
            font-weight: 400;
            color: var(--text);
            flex: 1;
        }
        .group-count {
            font-size: 14px;
            color: var(--muted);
            margin-right: 12px;
            min-width: 20px;
            text-align: right;
        }
        .group-count.has-items {
            color: var(--primary, #1a73e8);
            font-weight: 500;
        }
        .group-arrow {
            width: 10px;
            height: 16px;
            transition: transform 0.2s;
            opacity: 0.6;
        }
        .group-section.collapsed .group-arrow { transform: rotate(-90deg); }
        .group-body { }
        .group-section.collapsed .group-body { display: none; }

        /* ── Assignment row ── */
        .asgn-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 0 14px 8px;
            text-decoration: none;
            color: inherit;
            transition: background 0.12s;
            border-radius: 0;
        }
        .asgn-row:hover { background: rgba(0,0,0,0.03); }
        [data-theme="dark"] .asgn-row:hover { background: rgba(255,255,255,0.04); }
        .asgn-clip-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            opacity: 0.75;
        }
        .asgn-info { flex: 1; min-width: 0; }
        .asgn-title {
            font-size: 14px;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .asgn-class {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
        }
        .asgn-due {
            font-size: 12px;
            color: var(--muted);
            flex-shrink: 0;
            white-space: nowrap;
        }
        .asgn-due.overdue { color: #d93025; font-weight: 500; }
        .asgn-handed { font-size: 12px; color: var(--muted); flex-shrink: 0; }

        /* ── Empty state ── */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 16px;
            text-align: center;
        }
        .empty-state img { width: 500px; opacity: 0.8; margin-bottom: 20px; }
        .empty-state-title { font-size: 16px; font-weight: 500; color: var(--text); margin-bottom: 6px; }
        .empty-state-sub { font-size: 13px; color: var(--muted); }
    </style>
</head>
<body>
<div class="page-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
        <?php include __DIR__ . '/../includes/navbar.php'; ?>

        <div class="todo-page">
            <h1 class="todo-page-title">To do</h1>

            <!-- TABS -->
            <div class="todo-tabs">
                <a href="?tab=assigned&class_id=<?= htmlspecialchars($filter_class) ?>"
                   class="tab <?= $tab === 'assigned' ? 'active' : '' ?>">Assigned</a>
                <a href="?tab=missing&class_id=<?= htmlspecialchars($filter_class) ?>"
                   class="tab <?= $tab === 'missing' ? 'active' : '' ?>">Missing</a>
                <a href="?tab=done&class_id=<?= htmlspecialchars($filter_class) ?>"
                   class="tab <?= $tab === 'done' ? 'active' : '' ?>">Done</a>
            </div>

            <div class="todo-main-content">
                <!-- CUSTOM CLASS FILTER DROPDOWN -->
                <div class="class-filter-wrap">
                    <div class="class-filter-btn" id="filterBtn" onclick="toggleFilterDropdown()">
                        <span id="filterLabel">
                            <?php
                            if ($filter_class === 'all') {
                                echo 'All classes';
                            } else {
                                foreach ($enrolled_classes as $ec) {
                                    if ($ec['id'] == $filter_class) echo htmlspecialchars($ec['name']);
                                }
                            }
                            ?>
                        </span>
                        <img class="arrow" src="<?= BASE_URL ?>/icons/down.png" alt="">
                    </div>
                    <div class="class-filter-dropdown" id="filterDropdown">
                        <div class="class-filter-option <?= $filter_class === 'all' ? 'selected' : '' ?>"
                             onclick="selectClass('all', 'All classes')">All classes</div>
                        <?php foreach ($enrolled_classes as $ec): ?>
                            <div class="class-filter-option <?= $filter_class == $ec['id'] ? 'selected' : '' ?>"
                                 onclick="selectClass('<?= $ec['id'] ?>', '<?= htmlspecialchars($ec['name'], ENT_QUOTES) ?>')">
                                <?= htmlspecialchars($ec['name']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CONTENT -->
                <?php if ($total_items === 0 && $tab === 'assigned'): ?>
                    <!-- Empty state only on assigned tab -->
                    <div class="empty-state">
                        <img src="<?= BASE_URL ?>/icons/forEmptyTodo.png" alt="Nothing to do">
                        <div class="empty-state-title">Nothing on your to-do list right now</div>
                        <div class="empty-state-sub">Check back later for new assignments</div>
                    </div>

                <?php else: ?>
                    <?php foreach ($grouped as $period => $group_items):
                        $count = count($group_items);
                        $has = $count > 0;
                        // auto-expand only groups with items
                        $collapsed = !$has ? 'collapsed' : '';
                    ?>
                    <div class="group-section <?= $collapsed ?>" id="group-<?= md5($period) ?>">
                        <div class="group-header" onclick="toggleGroup('<?= md5($period) ?>')">
                            <span class="group-label"><?= htmlspecialchars($period) ?></span>
                            <span class="group-count <?= $has ? 'has-items' : '' ?>"><?= $count ?></span>
                            <img class="group-arrow" src="<?= BASE_URL ?>/icons/down.png" alt="">
                        </div>
                        <div class="group-body">
                            <?php foreach ($group_items as $item):
                                $due_obj = $item['due_date'] ? new DateTime($item['due_date']) : null;
                                $now_obj = new DateTime();
                                $overdue = $due_obj && $due_obj < $now_obj && $tab !== 'done';
                            ?>
                            <a class="asgn-row"
                               href="<?= BASE_URL ?>/assignments/submit.php?assignment_id=<?= $item['id'] ?>&class_id=<?= $item['class_id'] ?>"
                               data-class-id="<?= $item['class_id'] ?>">
                                <img class="asgn-clip-icon" src="<?= BASE_URL ?>/icons/Clipboard.png" alt="">
                                <div class="asgn-info">
                                    <div class="asgn-title"><?= htmlspecialchars($item['title']) ?></div>
                                    <div class="asgn-class"><?= htmlspecialchars($item['class_name']) ?></div>
                                </div>
                                <?php if ($tab === 'done'): ?>
                                    <span class="asgn-handed">Handed in</span>
                                <?php elseif ($due_obj): ?>
                                    <span class="asgn-due <?= $overdue ? 'overdue' : '' ?>">
                                        <?= $due_obj->format('l j M') ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div><!-- /todo-main-content -->

        </div><!-- /todo-page -->
    </main>
</div>

<script>
// ── Group collapse/expand ──
function toggleGroup(id) {
    const section = document.getElementById('group-' + id);
    section.classList.toggle('collapsed');
}

// ── Custom class filter dropdown ──
function toggleFilterDropdown() {
    const btn = document.getElementById('filterBtn');
    const dd  = document.getElementById('filterDropdown');
    btn.classList.toggle('open');
    dd.classList.toggle('open');
}

function selectClass(classId, label) {
    // Update label
    document.getElementById('filterLabel').textContent = label;
    // Close dropdown
    document.getElementById('filterBtn').classList.remove('open');
    document.getElementById('filterDropdown').classList.remove('open');
    // Navigate with filter
    const url = new URL(window.location.href);
    url.searchParams.set('class_id', classId);
    window.location.href = url.toString();
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    const wrap = document.querySelector('.class-filter-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('filterBtn').classList.remove('open');
        document.getElementById('filterDropdown').classList.remove('open');
    }
});
</script>
</body>
</html>
