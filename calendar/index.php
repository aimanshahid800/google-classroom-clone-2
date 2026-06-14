<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$filter_class = $_GET['class_id'] ?? 'all';

// Week navigation
$week_offset = intval($_GET['week'] ?? 0);
$today = new DateTime();
$monday = (clone $today)->modify('monday this week')->modify("$week_offset weeks");
$sunday = (clone $monday)->modify('+6 days');

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

// Get assignments for this week
$class_filter = '';
$params = [$user['id'], $monday->format('Y-m-d 00:00:00'), $sunday->format('Y-m-d 23:59:59')];

if ($filter_class !== 'all') {
    $class_filter = ' AND a.class_id = ?';
    $params[] = $filter_class;
}

$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.due_date, a.topic, c.name as class_name, c.id as class_id
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN class_members cm ON c.id = cm.class_id AND cm.user_id = ?
    WHERE a.due_date BETWEEN ? AND ?
    $class_filter
    ORDER BY a.due_date ASC
");
$stmt->execute($params);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize assignments by day of week
$days = ['Mon' => [], 'Tue' => [], 'Wed' => [], 'Thu' => [], 'Fri' => [], 'Sat' => [], 'Sun' => []];
$dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

foreach ($assignments as $a) {
    $date = new DateTime($a['due_date']);
    $dayIndex = intval($date->format('N')) - 1;
    $dayName = $dayNames[$dayIndex];
    $days[$dayName][] = $a;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .calendar-header h1 {
            margin: 0;
            font-size: 28px;
        }
        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .nav-btn {
            width: 36px;
            height: 36px;
            border: 1px solid var(--border);
            border-radius: 50%;
            background: var(--surface);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.2s;
        }
        .nav-btn:hover {
            background: var(--surface-alt);
            border-color: var(--primary);
        }
        .week-range {
            font-size: 16px;
            font-weight: 500;
            color: var(--text);
        }
        .calendar-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 24px;
        }
        .filter-dropdown {
            padding: 8px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--surface);
            color: var(--text);
            cursor: pointer;
        }
        .filter-dropdown:focus {
            outline: none;
            border-color: var(--primary);
        }
        .today-btn {
            padding: 8px 16px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
        }
        .today-btn:hover {
            background: var(--surface-alt);
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            background: var(--surface);
        }
        .calendar-day {
            background: var(--surface);
            min-height: 140px;
            padding: 12px;
            border-right: 1px solid var(--border);
            box-sizing: border-box;
        }
        .calendar-day:last-child {
            border-right: none;
        }
        .day-header {
            text-align: center;
            margin-bottom: 8px;
        }
        .day-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .day-number {
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-top: 4px;
        }
        .day-number.today {
            background: var(--primary);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .day-events {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .day-event {
            padding: 4px 8px;
            background: #e3f2fd;
            border-radius: 4px;
            font-size: 11px;
            color: #1565c0;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-decoration: none;
            display: block;
        }
        .day-event:hover {
            background: #bbdefb;
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <div class="calendar-header">
                <h1>Calendar</h1>
                <div class="calendar-nav">
                    <a href="?week=<?php echo $week_offset - 1; ?>&class_id=<?php echo htmlspecialchars($filter_class); ?>" class="nav-btn">&lt;</a>
                    <span class="week-range">
                        <?php echo $monday->format('M j'); ?> &ndash; <?php echo $sunday->format('M j, Y'); ?>
                    </span>
                    <a href="?week=<?php echo $week_offset + 1; ?>&class_id=<?php echo htmlspecialchars($filter_class); ?>" class="nav-btn">&gt;</a>
                </div>
            </div>

            <div class="calendar-controls">
                <a href="?week=0&class_id=<?php echo htmlspecialchars($filter_class); ?>" class="today-btn">Today</a>
                <form method="GET" style="display: inline;">
                    <input type="hidden" name="week" value="<?php echo $week_offset; ?>">
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

            <div class="calendar-grid">
                <?php
                $todayStr = (new DateTime())->format('Y-m-d');
                for ($i = 0; $i < 7; $i++):
                    $currentDay = (clone $monday)->modify("+$i days");
                    $dayStr = $currentDay->format('Y-m-d');
                    $dayName = $dayNames[$i];
                    $isToday = ($dayStr === $todayStr);
                ?>
                    <div class="calendar-day">
                        <div class="day-header">
                            <div class="day-name"><?php echo $dayName; ?></div>
                            <div class="day-number <?php echo $isToday ? 'today' : ''; ?>">
                                <?php echo $currentDay->format('j'); ?>
                            </div>
                        </div>
                        <div class="day-events">
                            <?php if (!empty($days[$dayName])): ?>
                                <?php foreach ($days[$dayName] as $event): ?>
                                    <a href="<?php echo BASE_URL; ?>/assignments/submit.php?assignment_id=<?php echo $event['id']; ?>&class_id=<?php echo $event['class_id']; ?>" class="day-event" title="<?php echo htmlspecialchars($event['title'] . ' - ' . $event['class_name']); ?>">
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </main>
    </div>
</body>
</html>
