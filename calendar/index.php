<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

// Optional class filter
$filter_class = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? (int) $_GET['class_id'] : null;

// Week offset (0 = current week, +1 = next, -1 = previous)
$week_offset = isset($_GET['week']) ? (int) $_GET['week'] : 0;

// Compute the Monday of the displayed week
$today = (new DateTime('now'))->setTime(0, 0, 0);
$weekStart = (clone $today)->modify('monday this week');
if ($week_offset !== 0) {
    $weekStart->modify(($week_offset > 0 ? '+' : '-') . abs($week_offset) . ' weeks');
}
$weekEnd = (clone $weekStart)->modify('+6 days');
$rangeEndExclusive = (clone $weekStart)->modify('+7 days');

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

// Assignments due within the displayed week, across the user's classes
$params = [$user['id'], $weekStart->format('Y-m-d H:i:s'), $rangeEndExclusive->format('Y-m-d H:i:s')];
$sql = '
    SELECT a.id, a.title, a.topic, a.due_date, a.class_id, c.name AS class_name
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN class_members cm ON cm.class_id = a.class_id AND cm.user_id = ?
    WHERE a.due_date IS NOT NULL
      AND a.due_date >= ?
      AND a.due_date < ?
';
if ($filter_class) {
    $sql .= ' AND a.class_id = ?';
    $params[] = $filter_class;
}
$sql .= ' ORDER BY a.due_date ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bucket assignments by day (Y-m-d)
$byDay = [];
foreach ($rows as $row) {
    $key = (new DateTime($row['due_date']))->format('Y-m-d');
    $byDay[$key][] = $row;
}

// Build the seven days of the week
$days = [];
for ($i = 0; $i < 7; $i++) {
    $d = (clone $weekStart)->modify("+$i days");
    $days[] = $d;
}

$todayKey = $today->format('Y-m-d');

function range_label(DateTime $start, DateTime $end): string
{
    if ($start->format('Y-m') === $end->format('Y-m')) {
        return $start->format('M j') . ' – ' . $end->format('j, Y');
    }
    return $start->format('M j') . ' – ' . $end->format('M j, Y');
}

$qsClass = $filter_class ? '&class_id=' . $filter_class : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calendar | Classroom Clone</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .cal-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 16px; flex-wrap: wrap; }
    .cal-head h1 { margin: 0; font-size: 28px; }
    .cal-controls { display: flex; align-items: center; gap: 12px; }
    .filter-select {
      padding: 8px 12px;
      border: 1px solid var(--border);
      border-radius: 999px;
      background: var(--surface);
      color: var(--text);
    }
    .nav-btn {
      width: 36px; height: 36px;
      border: 1px solid var(--border);
      border-radius: 50%;
      background: var(--surface);
      color: var(--text);
      font-size: 16px;
      line-height: 1;
      text-decoration: none;
      display: inline-flex; align-items: center; justify-content: center;
    }
    .nav-btn:hover { background: var(--surface-alt); }
    .range-label { font-weight: 600; min-width: 170px; text-align: center; }
    .today-btn {
      padding: 8px 16px;
      border: 1px solid var(--border);
      border-radius: 999px;
      background: var(--surface);
      color: var(--primary);
      font-weight: 600;
      text-decoration: none;
    }
    .week-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 12px;
    }
    .day-col {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      min-height: 220px;
      padding: 10px;
      box-shadow: var(--shadow);
    }
    .day-head { text-align: center; margin-bottom: 10px; }
    .day-name { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; }
    .day-num {
      display: inline-flex; align-items: center; justify-content: center;
      width: 34px; height: 34px; margin-top: 4px;
      font-size: 16px; font-weight: 600; border-radius: 50%;
    }
    .day-col.today .day-num { background: var(--primary); color: #fff; }
    .event {
      display: block;
      background: #e8f0fe;
      border-left: 3px solid var(--primary);
      border-radius: 6px;
      padding: 8px 10px;
      margin-bottom: 8px;
      color: var(--text);
      text-decoration: none;
    }
    .event:hover { background: #d2e3fc; }
    .event .ev-title { font-size: 13px; font-weight: 600; line-height: 1.2; }
    .event .ev-meta { font-size: 11px; color: var(--muted); margin-top: 2px; }
    .day-empty { font-size: 12px; color: var(--border); text-align: center; margin-top: 8px; }
  </style>
</head>
<body>
  <div class="page-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
      <?php include __DIR__ . '/../includes/navbar.php'; ?>

      <div class="cal-head">
        <h1>Calendar</h1>
        <div class="cal-controls">
          <a class="today-btn" href="?week=0<?php echo $qsClass; ?>">Today</a>
          <a class="nav-btn" href="?week=<?php echo $week_offset - 1; ?><?php echo $qsClass; ?>" title="Previous week">&#8249;</a>
          <span class="range-label"><?php echo htmlspecialchars(range_label($weekStart, $weekEnd)); ?></span>
          <a class="nav-btn" href="?week=<?php echo $week_offset + 1; ?><?php echo $qsClass; ?>" title="Next week">&#8250;</a>
          <form method="get" id="filterForm">
            <input type="hidden" name="week" value="<?php echo (int) $week_offset; ?>">
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
      </div>

      <div class="week-grid">
        <?php foreach ($days as $day):
          $key = $day->format('Y-m-d');
          $isToday = $key === $todayKey;
          $events = $byDay[$key] ?? [];
        ?>
          <div class="day-col <?php echo $isToday ? 'today' : ''; ?>">
            <div class="day-head">
              <div class="day-name"><?php echo $day->format('D'); ?></div>
              <div class="day-num"><?php echo $day->format('j'); ?></div>
            </div>
            <?php if (empty($events)): ?>
              <div class="day-empty">&mdash;</div>
            <?php else: ?>
              <?php foreach ($events as $ev): ?>
                <a class="event"
                   href="/Uni-Team-Project/google-classroom-clone-2/assignments/submit.php?assignment_id=<?php echo (int) $ev['id']; ?>&class_id=<?php echo (int) $ev['class_id']; ?>">
                  <span class="ev-title"><?php echo htmlspecialchars($ev['title']); ?></span>
                  <span class="ev-meta">
                    <?php echo htmlspecialchars($ev['class_name']); ?>
                    &middot; <?php echo (new DateTime($ev['due_date']))->format('g:i A'); ?>
                  </span>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </main>
  </div>
</body>
</html>
