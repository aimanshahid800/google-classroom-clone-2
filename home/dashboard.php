<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

// Fetch all enrolled classes for the current user (excluding archived ones)
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.section, c.subject, c.code, c.owner_id, u.name AS teacher_name
    FROM classes c
    JOIN class_members cm ON c.id = cm.class_id
    LEFT JOIN users u ON c.owner_id = u.id
    WHERE cm.user_id = ? AND c.is_archived = 0
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
  <title>Dashboard | Classroom Clone</title>
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
    .header-actions {
      display: flex;
      gap: 12px;
    }
    .action-btn {
      padding: 10px 20px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .action-btn:hover {
      background: #1765cc;
    }
    .action-btn-outline {
      background: white;
      color: var(--primary);
      border: 2px solid var(--primary);
    }
    .action-btn-outline:hover {
      background: #f0f4f9;
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
    }
    .class-card:hover {
      box-shadow: 0 4px 16px rgba(0,0,0,0.15);
      transform: translateY(-2px);
    }
    .class-banner {
      height: 120px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: flex-end;
      padding: 20px;
      color: white;
      position: relative;
    }
    .class-banner h3 {
      margin: 0;
      font-size: 20px;
      font-weight: 600;
    }
    .class-info {
      padding: 60px;
      flex: 1;
    }
    .class-section {
      font-size: 13px;
      color: var(--muted);
      margin-bottom: 4px;
    }
    .class-teacher {
      font-size: 14px;
      color: var(--text);
      font-weight: 500;
      margin-bottom: 12px;
    }
    .class-code {
      font-size: 12px;
      background: var(--surface-alt);
      padding: 6px 10px;
      border-radius: 6px;
      color: var(--muted);
      margin-bottom: 12px;
      word-break: break-all;
    }
   .class-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 50px;
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
    .footer-icon:hover {
      opacity: 1;
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
    }
    .menu-item:hover {
      background: var(--surface-alt);
    }
    .menu-item.danger {
      color: #d93025;
    }
    .menu-divider {
      height: 1px;
      background: var(--border);
      margin: 4px 0;
    }

    /* Empty state styled container */
    .empty-state {
      text-align: center;
      padding: 80px 20px 40px;
      color: var(--muted);
      max-width: 500px;
      margin: 0 auto;
    }
    .empty-state-img {
      max-width: 300px;
      width: 100%;
      height: auto;
      margin: 0 auto 24px;
      display: block;
    }
    .empty-state h2 {
      margin: 0 0 24px;
      color: var(--text);
      font-size: 18px;
      font-weight: 400;
    }
    .btn-create-class {
      background: transparent;
      color: #1a73e8;
      border: none;
      padding: 10px 24px;
      font-weight: 600;
      font-size: 14px;
      border-radius: 50px;
      cursor: pointer;
      text-decoration: none;
      transition: background-color 0.2s, color 0.2s;
    }
    .btn-create-class:hover {
      background-color: rgba(26, 115, 232, 0.08);
      color: #1557b0;
    }
    .btn-join-class {
      background: #1a73e8;
      color: white;
      border: none;
      padding: 10px 24px;
      font-weight: 600;
      font-size: 14px;
      border-radius: 50px;
      cursor: pointer;
      text-decoration: none;
      transition: background-color 0.2s, box-shadow 0.2s;
    }
    .btn-join-class:hover {
      background: #1557b0;
      box-shadow: 0 1px 3px rgba(60,64,67,0.3);
    }

    /* Change container background based on theme when classes are empty */
    html:not([data-theme="dark"]) body:has(.empty-state) .content {
      background-color: #f8fafd !important;
    }
    html[data-theme="dark"] body:has(.empty-state) .content {
      background-color: #1e1e1e !important;
    }
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
          <?php foreach ($classes as $class): ?>
            <div class="class-card">
              <div class="class-banner">
                <h3><?php echo htmlspecialchars($class['name']); ?></h3>
              </div>
               <div class="class-info">
               </div>

              <div class="class-footer">
                <a href="../classes/stream.php?class_id=<?php echo $class['id']; ?>" title="Open Class">
                  <img src="<?php echo BASE_URL; ?>/icons/portrait-icon.svg" class="footer-icon">
                </a>
                <div class="footer-icon" title="Class Folder">
                  <img src="<?php echo BASE_URL; ?>/icons/folder icon.svg" style="width: 24px; height: 24px;">
                </div>
                <div class="menu-container">
                   <img src="<?php echo BASE_URL; ?>/icons/3dots-more-icon.png" class="footer-icon" onclick="toggleClassMenu(this)">
                   <div class="class-menu-dropdown">

                    <div class="menu-item">Move</div>
                    <div class="menu-item">Hide</div>
                    <div class="menu-item">Unenroll</div>
                    <div class="menu-divider"></div>
                    <div class="menu-item danger">Report abuse</div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <img src="<?php echo BASE_URL; ?>/icons/no preview window.png" class="empty-state-img" alt="No classes started">
          <h2>Add a class to get started</h2>
          <div style="margin-top: 24px; display: flex; gap: 16px; justify-content: center; align-items: center;">
            <?php if ($user && $user['role'] === 'student'): ?>
              <a href="#" class="btn-create-class" onclick="openStudentModal('create'); return false;">Create class</a>
              <a href="#" class="btn-join-class" onclick="openStudentModal('join'); return false;">Join class</a>
            <?php else: ?>
              <a href="../classes/create.php" class="btn-create-class">Create class</a>
              <a href="../classes/join.php" class="btn-join-class">Join class</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
<script>
function toggleClassMenu(element) {
    const dropdown = element.nextElementSibling;
    
    // Close all other dropdowns first
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
</html>