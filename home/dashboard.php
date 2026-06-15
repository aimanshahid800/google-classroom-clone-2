<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.section, c.subject, c.code, c.owner_id, u.name AS teacher_name
    FROM classes c
    JOIN class_members cm ON c.id = cm.class_id
    LEFT JOIN users u ON c.owner_id = u.id
    WHERE cm.user_id = ? AND c.is_archived = 0 AND cm.is_archived = 0
    ORDER BY c.created_at DESC
');
$stmt->execute([$user['id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Fetch next upcoming assignment for each class ──
$upcoming = [];
foreach ($classes as $class) {
    if ($user['role'] === 'student') {
        // not submitted + due in future
        $stmt2 = $pdo->prepare("
            SELECT a.title, a.due_date
            FROM assignments a
            WHERE a.class_id = ?
              AND a.id NOT IN (SELECT assignment_id FROM submissions WHERE user_id = ?)
              AND (a.due_date IS NULL OR a.due_date >= NOW())
            ORDER BY a.due_date IS NULL, a.due_date ASC
            LIMIT 1
        ");
        $stmt2->execute([$class['id'], $user['id']]);
    } else {
        // teacher sees next due assignment in their class
        $stmt2 = $pdo->prepare("
            SELECT a.title, a.due_date
            FROM assignments a
            WHERE a.class_id = ?
              AND (a.due_date IS NULL OR a.due_date >= NOW())
            ORDER BY a.due_date IS NULL, a.due_date ASC
            LIMIT 1
        ");
        $stmt2->execute([$class['id']]);
    }
    $upcoming[$class['id']] = $stmt2->fetch(PDO::FETCH_ASSOC);
}

// ── Due label helper ──
function getDueLabel($due_date_str) {
    if (!$due_date_str) return null;
    $due  = new DateTime($due_date_str);
    $now  = new DateTime();
    $today = new DateTime('today');
    $tomorrow = new DateTime('tomorrow');
    $diff = (int)$today->diff($due)->days;
    $future = $due >= $today;

    if (!$future) return null; // past — skip
    if ($due < $tomorrow) return 'Due today';
    if ($diff === 1)      return 'Due tomorrow';
    return 'Due ' . $due->format('M j');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Classroom Clone</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
    .dashboard-header h1 { margin: 0; font-size: 32px; }

    .classes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin-bottom: 40px; }

    .class-card {
      background: var(--surface); border-radius: 12px; overflow: hidden;
      box-shadow: var(--shadow); transition: all 0.3s ease;
      display: flex; flex-direction: column; border: 1px solid var(--border);
    }
    .class-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.15); transform: translateY(-2px); }

    .class-banner {
      height: 120px; display: flex; align-items: flex-end;
      padding: 20px; color: white; position: relative;
    }
    .class-banner h3 { margin: 0; font-size: 20px; font-weight: 600; }

    /* ── Upcoming assignment area ── */
    .class-info {
      padding: 14px 16px 70px;
      flex: 1;
      min-height: 56px;
      border-bottom: 1px solid var(--border);
    }
    .class-upcoming {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .class-upcoming-label {
      font-size: 13px;
      font-weight: 500;
      color: var(--text);
    }
    .class-upcoming-title {
      font-size: 13px;
      color: var(--muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .class-footer {
      display: flex; justify-content: flex-end; align-items: center;
      gap: 20px; padding: 10px 16px; background: var(--surface);
    }
    .footer-icon { width: 24px; height: 24px; cursor: pointer; transition: opacity 0.2s; opacity: 0.7; }
    .footer-icon:hover { opacity: 1; }

    .menu-container { position: relative; }
    .class-menu-dropdown {
      position: absolute; bottom: 100%; right: 0; width: 160px;
      background: var(--surface); border: 1px solid var(--border);
      border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      display: none; z-index: 10; overflow: hidden; margin-bottom: 8px;
    }
    .class-menu-dropdown.show { display: block; }
    .menu-item {
      padding: 10px 16px; font-size: 14px; color: var(--text);
      cursor: pointer; transition: background 0.2s;
      border-bottom: 1px solid var(--border);
    }
    .menu-item:hover { background: var(--surface-alt); }
    .menu-item.danger { color: #d93025; }

    /* Empty state */
    .empty-state { text-align: center; padding: 40px 20px; color: var(--muted); max-width: 500px; margin: 0 auto; }
    .empty-state-img { max-width: 350px; width: 100%; height: auto; margin: 0 auto 24px; display: block; }
    .empty-state h2 { margin: 0 0 24px; color: var(--text); font-size: 18px; font-weight: 400; }
    .btn-create-class { background: transparent; color: #1a73e8; border: none; padding: 10px 24px; font-weight: 600; font-size: 14px; border-radius: 50px; cursor: pointer; text-decoration: none; transition: background-color 0.2s; }
    .btn-create-class:hover { background-color: rgba(26,115,232,0.08); }
    .btn-join-class { background: #1a73e8; color: white; border: none; padding: 10px 24px; font-weight: 600; font-size: 14px; border-radius: 50px; cursor: pointer; text-decoration: none; transition: background-color 0.2s; }
    .btn-join-class:hover { background: #1557b0; }
  </style>
</head>
<body>
  <div class="page-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
      <?php include __DIR__ . '/../includes/navbar.php'; ?>

      <div class="dashboard-header">
        <h1>Classes</h1>
      </div>

      <?php if (!empty($classes)): ?>
        <div class="classes-grid">
          <?php foreach ($classes as $class):
            $asgn  = $upcoming[$class['id']] ?? null;
            $label = $asgn ? getDueLabel($asgn['due_date']) : null;
          ?>
            <div class="class-card">
              <a href="../classes/stream.php?class_id=<?php echo $class['id']; ?>" style="text-decoration:none;">
                <div class="class-banner" style="background: <?php echo generateGradient($class['name']); ?>;">
                  <h3><?php echo htmlspecialchars($class['name']); ?></h3>
                </div>
              </a>

              <div class="class-info">
                <?php if ($asgn && $label): ?>
                <div class="class-upcoming">
                  <span class="class-upcoming-label"><?php echo $label; ?></span>
                  <span class="class-upcoming-title">
                    <?php
                      // "23:59 – Title" format jaise image mein tha
                      $time = $asgn['due_date'] ? (new DateTime($asgn['due_date']))->format('H:i') : '';
                      echo ($time ? $time . ' – ' : '') . htmlspecialchars($asgn['title']);
                    ?>
                  </span>
                </div>
                <?php endif; ?>
              </div>

              <div class="class-footer">
                <a href="../classes/stream.php?class_id=<?php echo $class['id']; ?>" title="Open Class">
                  <img src="<?php echo BASE_URL; ?>/icons/portrait-icon.svg" class="footer-icon">
                </a>
                <div class="footer-icon" title="Class Folder">
                  <img src="<?php echo BASE_URL; ?>/icons/folder icon.svg" style="width:24px;height:24px;">
                </div>
                <div class="menu-container">
                  <img src="<?php echo BASE_URL; ?>/icons/3dots-more-icon.png" class="footer-icon" onclick="toggleClassMenu(this)">
                  <div class="class-menu-dropdown">
                    <form action="../classes/manage.php" method="POST" style="display:contents;">
                      <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                      <button type="submit" name="action" value="archive" class="menu-item" style="width:100%;text-align:left;background:none;border:none;font:inherit;">
                        Move to Archive
                      </button>
                      <?php if ($user['role'] === 'teacher' && $class['owner_id'] == $user['id']): ?>
                        <button type="submit" name="action" value="delete" class="menu-item danger" style="width:100%;text-align:left;background:none;border:none;font:inherit;" onclick="return confirm('Delete this class permanently?')">
                          Delete
                        </button>
                      <?php endif; ?>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <div class="empty-state">
          <img src="<?php echo BASE_URL; ?>/icons/no preview window.png" class="empty-state-img" alt="No classes">
          <h2>Add a class to get started</h2>
          <div style="margin-top:24px;display:flex;gap:16px;justify-content:center;align-items:center;">
            <?php if ($user && $user['role'] === 'student'): ?>
              <a href="#" class="btn-create-class" onclick="openStudentModal('create'); return false;">Create class</a>
              <a href="#" class="btn-join-class" onclick="openStudentModal('join'); return false;">Join class</a>
            <?php else: ?>
              <a href="../classes/create.php" class="btn-create-class">Create class</a>
              <a href="../classes/create.php" class="btn-join-class">Create class</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <script>
  function toggleClassMenu(element) {
    const dropdown = element.nextElementSibling;
    document.querySelectorAll('.class-menu-dropdown').forEach(m => { if (m !== dropdown) m.classList.remove('show'); });
    dropdown.classList.toggle('show');
  }
  window.onclick = function(event) {
    if (!event.target.closest('.menu-container')) {
      document.querySelectorAll('.class-menu-dropdown').forEach(m => m.classList.remove('show'));
    }
  }
  </script>
</body>
</html>