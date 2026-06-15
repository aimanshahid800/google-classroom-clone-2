<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

// Fetch archived classes
// A class is archived if the teacher archived it (c.is_archived=1) OR the user archived it personally (cm.is_archived=1)
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.section, c.subject, c.code, c.owner_id, u.name AS teacher_name
    FROM classes c
    JOIN class_members cm ON c.id = cm.class_id
    LEFT JOIN users u ON c.owner_id = u.id
    WHERE cm.user_id = ? AND (c.is_archived = 1 OR cm.is_archived = 1)
    ORDER BY c.created_at DESC
');
$stmt->execute([$user['id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Archived Classes | Classroom Clone</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .dashboard-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 32px;
    }
    .dashboard-header h1 {
      margin: 0;
      font-size: 32px;
    }
    .classes-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 24px;
      margin-bottom: 40px;
    }
    .class-card {
      background: var(--surface);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      border: 1px solid var(--border);
      opacity: 0.8;
    }
    .class-banner {
      height: 120px;
      background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%);
      display: flex;
      align-items: flex-end;
      padding: 20px;
      color: #5f6368;
      position: relative;
    }
    .class-banner h3 {
      margin: 0;
      font-size: 20px;
      font-weight: 600;
    }
    .class-footer {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      padding: 12px 16px;
      border-top: 1px solid var(--border);
      background: var(--surface);
    }
    .footer-icon {
      width: 24px;
      height: 24px;
      cursor: pointer;
      transition: opacity 0.2s;
      opacity: 0.7;
    }
    .menu-container {
      position: relative;
    }
    .class-menu-dropdown {
      position: absolute;
      bottom: 100%;
      right: 0;
      width: 160px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      display: none;
      z-index: 10;
      overflow: hidden;
      margin-bottom: 8px;
    }
    .class-menu-dropdown.show {
      display: block;
    }
    .menu-item {
      padding: 10px 16px;
      font-size: 14px;
      color: var(--text);
      cursor: pointer;
      transition: background 0.2s;
      border-bottom: 1px solid var(--border);
      width: 100%;
      text-align: left;
      background: none;
      border: none;
      font: inherit;
    }
    .menu-item:hover {
      background: var(--surface-alt);
    }
    .menu-item.danger {
      color: #d93025;
    }
    .empty-state {
      text-align: center;
      padding: 80px 20px 40px;
      color: var(--muted);
      max-width: 500px;
      margin: 0 auto;
    }
    .empty-state-img {
      max-width: 400px;
      width: 100%;
      height: auto;
      margin: 0 auto 24px;
      display: block;
    }
  </style>
</head>
<body>
  <div class="page-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
      <?php include __DIR__ . '/../includes/navbar.php'; ?>
      
       <div class="dashboard-header">
          <h1>Archived Classes</h1>
        </div>

      <?php if (!empty($classes)): ?>
        <div class="classes-grid">
          <?php foreach ($classes as $class): ?>
            <div class="class-card">
              <div class="class-banner">
                <h3><?php echo htmlspecialchars($class['name']); ?></h3>
              </div>
              <div class="class-footer">
                <div class="menu-container">
                   <img src="<?php echo BASE_URL; ?>/icons/3dots-more-icon.png" class="footer-icon" onclick="toggleClassMenu(this)">
                   <div class="class-menu-dropdown">
                     <form action="../classes/manage.php" method="POST" style="display: contents;">
                       <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                       <button type="submit" name="action" value="restore" class="menu-item" style="width: 100%; text-align: left; background: none; border: none; font: inherit;">
                         Restore to Dashboard
                       </button>
                       <button type="submit" name="action" value="delete" class="menu-item danger" style="width: 100%; text-align: left; background: none; border: none; font: inherit;" onclick="return confirm('Are you sure you want to delete this archived class?')">
                         Delete
                       </button>
                     </form>
                   </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <img src="<?php echo BASE_URL; ?>/icons/emptyArchive.png" class="empty-state-img" alt="No archived classes">
          <p>None of your classes have been archived</p>
        </div>
      <?php endif; ?>
    </main>
  </div>
  <script>
  function toggleClassMenu(element) {
      const dropdown = element.nextElementSibling;
      document.querySelectorAll('.class-menu-dropdown').forEach(menu => {
          if (menu !== dropdown) menu.classList.remove('show');
      });
      dropdown.classList.toggle('show');
  }
  window.onclick = function(event) {
      if (!event.target.closest('.menu-container')) {
          document.querySelectorAll('.class-menu-dropdown').forEach(menu => {
              menu.classList.remove('show');
          });
      }
  }
  </script>
</body>
</html>
