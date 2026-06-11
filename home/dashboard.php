<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

// Fetch all enrolled classes for the current user (excluding archived ones)
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.section, c.subject, c.code, c.owner_id, u.name AS teacher_name
    FROM classes c
    JOIN class_members cm ON c.id = cm.class_id
    JOIN users u ON c.owner_id = u.id
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
      padding: 20px;
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
      justify-content: space-around;
      align-items: center;
      padding: 12px;
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

    .empty-state {
      text-align: center;
      padding: 80px 20px 40px;
      color: var(--muted);
      max-width: 500px;
      margin: 0 auto;
    }
    .empty-state h2 {
      margin: 0 0 24px;
      color: var(--text);
      font-size: 18px;
      font-weight: 400;
    }
    .btn-create-class {
      background: none;
      color: var(--primary);
      border: none;
      padding: 10px 16px;
      font-weight: 500;
      font-size: 14px;
      border-radius: 4px;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s;
    }
    .btn-create-class:hover {
      background: var(--primary-light);
    }
    .btn-join-class {
      background: var(--primary);
      color: white;
      border: none;
      padding: 10px 24px;
      font-weight: 500;
      font-size: 14px;
      border-radius: 20px;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.2s, box-shadow 0.2s;
    }
    .btn-join-class:hover {
      background: #1557b0;
      box-shadow: 0 1px 3px rgba(60,64,67,0.3), 0 4px 8px 3px rgba(60,64,67,0.15);
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
                <?php if ($class['section']): ?>
                  <div class="class-section"><?php echo htmlspecialchars($class['section']); ?></div>
                <?php endif; ?>
                <div class="class-teacher">👨‍🏫 <?php echo htmlspecialchars($class['teacher_name']); ?></div>
                <div class="class-code">Code: <strong><?php echo htmlspecialchars($class['code']); ?></strong></div>
                <div class="class-actions">
                  <a href="../classes/stream.php?class_id=<?php echo $class['id']; ?>" class="class-action-link">Stream</a>
                  <a href="../classes/classwork.php?class_id=<?php echo $class['id']; ?>" class="class-action-link">Classwork</a>
                  <a href="../classes/people.php?class_id=<?php echo $class['id']; ?>" class="class-action-link">People</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="classes-help-tip" style="position: absolute; top: 20px; right: 40px; text-align: right; color: var(--muted); font-size: 13px; line-height: 1.4; pointer-events: none;">
          <div>Don't see your classes?</div>
          <div>Try another account.</div>
          <svg width="45" height="45" viewBox="0 0 45 45" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: 8px 10px 0 auto;">
            <path d="M10,40 C15,28 28,20 38,10" stroke="var(--muted)" stroke-width="1.5" stroke-dasharray="3,3" fill="none" />
            <path d="M30,12 L38,10 L39,18" stroke="var(--muted)" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>

        <div class="empty-state">
          <!-- Beautiful SVG Window & Desk Illustration -->
          <svg width="240" height="200" viewBox="0 0 240 200" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: 0 auto 24px;">
            <!-- Window Frame -->
            <rect x="80" y="20" width="80" height="100" rx="4" stroke="#dadce0" stroke-width="2" />
            <line x1="120" y1="20" x2="120" y2="120" stroke="#dadce0" stroke-width="2" />
            <line x1="80" y1="70" x2="160" y2="70" stroke="#dadce0" stroke-width="2" />
            
            <!-- Plant in Window -->
            <path d="M90,110 Q95,95 105,95 Q100,110 90,110 Z" fill="#81c995" opacity="0.8" />
            <path d="M110,115 Q105,100 95,105 Q105,115 110,115 Z" fill="#81c995" opacity="0.6" />
            
            <!-- Yellow Mug -->
            <rect x="145" y="100" width="22" height="20" rx="3" fill="#fbbc04" />
            <path d="M167,104 C171,104 171,116 167,116" stroke="#fbbc04" stroke-width="3" fill="none" />
            <path d="M145,105 L167,105" stroke="#fff" stroke-width="1" opacity="0.3" />
            
            <!-- Stack of Books/Notebooks -->
            <!-- Blue Book (slanted) -->
            <path d="M115,140 L195,120 L205,135 L125,155 Z" fill="#1a73e8" />
            <path d="M125,155 L205,135 L208,138 L128,158 Z" fill="#d2e3fc" />
            
            <!-- White Paper/Book (underneath) -->
            <path d="M135,150 L205,145 L210,152 L140,157 Z" fill="#fff" stroke="#dadce0" stroke-width="1.5" />
            <!-- Small grey block -->
            <rect x="175" y="150" width="20" height="12" rx="2" fill="#dadce0" transform="rotate(-5, 175, 150)" />
            
            <!-- Pink Sphere/Vase on the left -->
            <circle cx="65" cy="115" r="12" fill="#ff8bcb" opacity="0.7" />
            <rect x="63" y="100" width="4" height="5" rx="1" fill="#ff8bcb" opacity="0.7" />
          </svg>

          <h2>Add a class to get started</h2>
          <div style="margin-top: 24px; display: flex; gap: 16px; justify-content: center; align-items: center;">
            <a href="../classes/create.php" class="btn-create-class">Create class</a>
            <a href="../classes/join.php" class="btn-join-class">Join class</a>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>