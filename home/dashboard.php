<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

// Fetch all enrolled classes for the current user
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.section, c.subject, c.code, c.owner_id, u.name AS teacher_name
    FROM classes c
    JOIN class_members cm ON c.id = cm.class_id
    JOIN users u ON c.owner_id = u.id
    WHERE cm.user_id = ?
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
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      cursor: pointer;
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
    }
    .class-banner h3 {
      margin: 0;
      font-size: 20px;
      font-weight: 600;
    }
    .class-info {
      padding: 20px;
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
    .class-actions {
      display: flex;
      gap: 8px;
      margin-top: 12px;
    }
    .class-action-link {
      flex: 1;
      padding: 8px;
      background: var(--surface-alt);
      border: none;
      border-radius: 6px;
      color: var(--primary);
      text-decoration: none;
      font-size: 12px;
      font-weight: 600;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
    }
    .class-action-link:hover {
      background: var(--primary);
      color: white;
    }
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--muted);
    }
    .empty-state-icon {
      font-size: 64px;
      margin-bottom: 20px;
    }
    .empty-state h2 {
      margin: 0 0 12px;
      color: var(--text);
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
        <div class="header-actions">
          <a href="../classes/create.php" class="action-btn">+ Create class</a>
          <a href="../classes/join.php" class="action-btn action-btn-outline">+ Join class</a>
        </div>
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
        <div class="empty-state">
          <div class="empty-state-icon">📚</div>
          <h2>No classes yet</h2>
          <p>Create a new class or join an existing one to get started.</p>
          <div style="margin-top: 24px;">
            <a href="../classes/create.php" class="action-btn" style="margin-right: 12px;">Create a class</a>
            <a href="../classes/join.php" class="action-btn action-btn-outline">Join a class</a>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
