<?php
// Sidebar markup for all pages — dynamically loads enrolled classes
$sidebar_classes = [];
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('
        SELECT c.id, c.name
        FROM classes c
        JOIN class_members cm ON c.id = cm.class_id
        WHERE cm.user_id = ?
        ORDER BY c.name ASC
    ');
    $stmt->execute([$_SESSION['user_id']]);
    $sidebar_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="sidebar">
  <div class="sidebar-section">
    <a href="<?php echo BASE_URL; ?>/home/dashboard.php" class="sidebar-link <?php echo ($current_dir === 'home') ? 'active' : ''; ?>">
      <span class="sidebar-icon">&#127968;</span> Home
    </a>
    <a href="<?php echo BASE_URL; ?>/calendar/index.php" class="sidebar-link <?php echo ($current_dir === 'calendar') ? 'active' : ''; ?>">
      <span class="sidebar-icon">&#128197;</span> Calendar
    </a>
    <a href="<?php echo BASE_URL; ?>/todo/index.php" class="sidebar-link <?php echo ($current_dir === 'todo') ? 'active' : ''; ?>">
      <span class="sidebar-icon">&#128203;</span> To do
    </a>
  </div>

  <?php if (!empty($sidebar_classes)): ?>
  <div class="sidebar-section">
    <h3 class="sidebar-heading">Enrolled</h3>
    <?php foreach ($sidebar_classes as $sc): ?>
      <a href="<?php echo BASE_URL; ?>/classes/stream.php?class_id=<?php echo $sc['id']; ?>" class="sidebar-link sidebar-class-link">
        <span class="sidebar-icon">&#128218;</span>
        <?php echo htmlspecialchars($sc['name']); ?>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="sidebar-section">
    <a href="<?php echo BASE_URL; ?>/settings/index.php" class="sidebar-link <?php echo ($current_dir === 'settings') ? 'active' : ''; ?>">
      <span class="sidebar-icon">&#9881;</span> Settings
    </a>
  </div>
</aside>

<style>
  .sidebar-section {
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
  }
  .sidebar-section:last-child {
    border-bottom: none;
  }
  .sidebar-heading {
    font-size: 12px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 12px;
    padding: 0 12px;
  }
  .sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 8px;
    color: var(--text);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
  }
  .sidebar-link:hover {
    background: var(--surface-alt);
    color: var(--primary);
  }
  .sidebar-link.active {
    background: #e8f0fe;
    color: var(--primary);
    font-weight: 600;
  }
  .sidebar-icon {
    font-size: 16px;
    width: 20px;
    text-align: center;
  }
  .sidebar-class-link {
    font-size: 13px;
    padding: 8px 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
</style>
