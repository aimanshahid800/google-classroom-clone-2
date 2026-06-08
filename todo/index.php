<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

// Selected tab: assigned | missing | done
$tab = $_GET['tab'] ?? 'assigned';
if (!in_array($tab, ['assigned', 'missing', 'done'], true)) {
    $tab = 'assigned';
}

// Optional class filter
$filter_class = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int) $_GET['class_id'] : null;

// Classes the user belongs to (for the filter dropdown)
$stmt = $pdo->prepare('
    SELECT c.id, c.name
    FROM classes c
    JOIN class_members cm ON c.id = cm.class_id
    WHERE cm.user_id = ?
    ORDER BY c.name ASC
');
$stmt->execute([$user['id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// All assignments in the user's classes, with this user's submission (if any)
$params = [$user['id'], $user['id']];
$sql = '
    SELECT a.id, a.title, a.topic, a.due_date, a.class_id,
           c.name AS class_name,
           s.status AS sub_status, s.submitted_at
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN class_members cm ON cm.class_id = a.class_id AND cm.user_id = ?
    LEFT JOIN submissions s ON s.assignment_id = a.id AND s.user_id = ?
';
if ($filter_class) {
    $sql .= ' WHERE a.class_id = ?';
    $params[] = $filter_class;
}
$sql .= ' ORDER BY (a.due_date IS NULL), a.due_date ASC, a.created_at ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$now = new DateTime('now');

// Classify each assignment into assigned / missing / done
function classify_item(array $row, DateTime $now): string
{
    $handed = in_array($row['sub_status'], ['handed_in', 'done'], true);
    if ($handed) {
        return 'done';
    }
    if (!empty($row['due_date'])) {
        $due = new DateTime($row['due_date']);
        if ($due < $now) {
            return 'missing';
        }
    }
    return 'assigned';
}

// Bucket an item by due date for display grouping
function due_group(?string $due_date, DateTime $now): string
{
    if (empty($due_date)) {
        return 'No due date';
    }
    $due = new DateTime($due_date);
    $startOfWeek = (clone $now)->modify('monday this week')->setTime(0, 0, 0);
    $endOfWeek = (clone $startOfWeek)->modify('+7 days');
    $endOfNextWeek = (clone $startOfWeek)->modify('+14 days');

    if ($due < $startOfWeek) {
        return 'Earlier';
    }
    if ($due < $endOfWeek) {
        return 'This week';
    }
    if ($due < $endOfNextWeek) {
        return 'Next week';
    }
    return 'Later';
}

$counts = ['assigned' => 0, 'missing' => 0, 'done' => 0];
$grouped = [];
foreach ($rows as $row) {
    $cls = classify_item($row, $now);
    $counts[$cls]++;
    if ($cls === $tab) {
        $group = due_group($row['due_date'], $now);
        $grouped[$group][] = $row;
    }
}

// Stable display order of groups
$group_order = ['Overdue', 'No due date', 'Earlier', 'This week', 'Next week', 'Later'];
uksort($grouped, function ($a, $b) use ($group_order) {
    return array_search($a, $group_order, true) <=> array_search($b, $group_order, true);
});

function fmt_due(?string $due_date): string
{
    if (empty($due_date)) {
        return 'No due date';
    }
    return 'Due ' . (new DateTime($due_date))->format('D, M j, Y g:i A');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>To Do | Classroom Clone</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .todo-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .todo-head h1 { margin: 0; font-size: 28px; }
    .filter-select {
      padding: 8px 12px;
      border: 1px solid var(--border);
      border-radius: 999px;
      background: var(--surface);
      color: var(--text);
    }
    .tabs { display: flex; gap: 24px; border-bottom: 1px solid var(--border); margin: 16px 0 24px; }
    .tab {
      padding: 12px 4px;
      border-bottom: 3px solid transparent;
      color: var(--muted);
      font-weight: 500;
      text-decoration: none;
    }
    .tab:hover { color: var(--text); }
    .tab.active { color: var(--primary); border-bottom-color: var(--primary); }
    .tab .count {
      display: inline-block;
      margin-left: 6px;
      font-size: 12px;
      background: var(--surface-alt);
      border: 1px solid var(--border);
      border-radius: 999px;
      padding: 0 8px;
    }
    .group-title { font-size: 14px; color: var(--muted); margin: 24px 0 12px; font-weight: 600; }
    .todo-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 16px 20px;
      margin-bottom: 12px;
      box-shadow: var(--shadow);
    }
    .todo-item .title { font-weight: 600; }
    .todo-item .meta { font-size: 13px; color: var(--muted); margin-top: 4px; display: flex; gap: 12px; flex-wrap: wrap; }
    .todo-item .due.overdue { color: #c5221f; font-weight: 600; }
    .badge {
      font-size: 12px;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 999px;
      white-space: nowrap;
    }
    .badge.handed { background: #e6f4ea; color: #137333; }
    .badge.missing { background: #fce8e6; color: #c5221f; }
    .badge.assigned { background: #e8f0fe; color: #1a73e8; }
    .open-link { color: var(--primary); font-weight: 500; white-space: nowrap; }
    .empty-message { text-align: center; padding: 48px 20px; color: var(--muted); }
    .max { max-width: 860px; }
  </style>
</head>
<body>
  <div class="page-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
      <?php include __DIR__ . '/../includes/navbar.php'; ?>

      <div class="max">
        <div class="todo-head">
          <h1>To do</h1>
          <form method="get" id="filterForm">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
            <select name="class_id" class="filter-select" onchange="document.getElementById('filterForm').submit()">
              <option value="">All classes</option>
              <?php foreach ($classes as $c): ?>
                <option value="<?php echo (int) $c['id']; ?>" <?php echo $filter_class === (int) $c['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($c['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>

        <?php
          $qs = $filter_class ? '&class_id=' . $filter_class : '';
        ?>
        <div class="tabs">
          <a class="tab <?php echo $tab === 'assigned' ? 'active' : ''; ?>" href="?tab=assigned<?php echo $qs; ?>">
            Assigned <span class="count"><?php echo $counts['assigned']; ?></span>
          </a>
          <a class="tab <?php echo $tab === 'missing' ? 'active' : ''; ?>" href="?tab=missing<?php echo $qs; ?>">
            Missing <span class="count"><?php echo $counts['missing']; ?></span>
          </a>
          <a class="tab <?php echo $tab === 'done' ? 'active' : ''; ?>" href="?tab=done<?php echo $qs; ?>">
            Done <span class="count"><?php echo $counts['done']; ?></span>
          </a>
        </div>

        <?php if (empty($grouped)): ?>
          <div class="empty-message">
            <p style="font-size:18px;">You're all caught up!</p>
            <p>No <?php echo htmlspecialchars($tab); ?> work right now.</p>
          </div>
        <?php else: ?>
          <?php foreach ($grouped as $group => $items): ?>
            <div class="group-title"><?php echo htmlspecialchars($group); ?></div>
            <?php foreach ($items as $item):
              $overdue = $tab === 'missing';
            ?>
              <div class="todo-item">
                <div>
                  <div class="title"><?php echo htmlspecialchars($item['title']); ?></div>
                  <div class="meta">
                    <span><?php echo htmlspecialchars($item['class_name']); ?></span>
                    <?php if ($item['topic']): ?>
                      <span>&middot; <?php echo htmlspecialchars($item['topic']); ?></span>
                    <?php endif; ?>
                    <span class="due <?php echo $overdue ? 'overdue' : ''; ?>"><?php echo htmlspecialchars(fmt_due($item['due_date'])); ?></span>
                  </div>
                </div>
                <div style="display:flex; align-items:center; gap:16px;">
                  <?php if ($tab === 'done'): ?>
                    <span class="badge handed">Handed in</span>
                  <?php elseif ($tab === 'missing'): ?>
                    <span class="badge missing">Missing</span>
                  <?php else: ?>
                    <span class="badge assigned">Assigned</span>
                  <?php endif; ?>
                  <a class="open-link"
                     href="/Uni-Team-Project/google-classroom-clone-2/assignments/submit.php?assignment_id=<?php echo (int) $item['id']; ?>&class_id=<?php echo (int) $item['class_id']; ?>">
                    <?php echo $tab === 'done' ? 'View' : 'Open'; ?>
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
