<?php
// Sidebar markup for all pages — dynamically loads enrolled classes
$user = current_user();
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
      <img src="<?php echo BASE_URL; ?>/icons/Home-icon.svg" class="sidebar-icon"> <span>Home</span>
    </a>
      <a href="<?php echo BASE_URL; ?>/calendar/index.php" class="sidebar-link <?php echo ($current_dir === 'calendar') ? 'active' : ''; ?>">
        <img src="<?php echo BASE_URL; ?>/icons/calender-icon.svg" class="sidebar-icon"> <span>Calendar</span>
      </a>
    </div>


  <?php if (!empty($sidebar_classes)): ?>
  <div class="sidebar-section enrolled-section">
    <div class="sidebar-dropdown-trigger" onclick="toggleEnrolled()">
      <div style="display: flex; align-items: center; gap: 8px;">
        <img src="<?php echo BASE_URL; ?>/icons/enrolled-icon.svg" style="width: 22px; height: 24px;">
        <span class="sidebar-heading">Enrolled</span>
      </div>
      <img src="<?php echo BASE_URL; ?>/icons/down.png" id="enrolled-arrow" class="dropdown-arrow" style="width: 12px; height: 12px;">
    </div>
    <div id="enrolled-list" class="sidebar-dropdown-content">
      <?php if ($user['role'] === 'student'): ?>
      <a href="<?php echo BASE_URL; ?>/todo/index.php" class="sidebar-link <?php echo ($current_dir === 'todo') ? 'active' : ''; ?>">
        <img src="<?php echo BASE_URL; ?>/icons/to-do-list-icon.svg" class="sidebar-icon"> <span>To do</span>
      </a>
      <?php endif; ?>
          <?php foreach ($sidebar_classes as $sc): ?>
            <a href="<?php echo BASE_URL; ?>/classes/stream.php?class_id=<?php echo $sc['id']; ?>" class="sidebar-link sidebar-class-link">
              <div class="class-initial-circle" style="background: <?php echo generateColor($sc['name']); ?>;">
                <?php echo strtoupper(substr($sc['name'], 0, 1)); ?>
              </div>
              <span><?php echo htmlspecialchars($sc['name']); ?></span>
            </a>
          <?php endforeach; ?>

    </div>
  </div>
  <?php endif; ?>

  <div class="sidebar-section">
    <a href="<?php echo BASE_URL; ?>/home/archived_classes.php" class="sidebar-link <?php echo ($current_page === 'archived_classes.php') ? 'active' : ''; ?>">
      <img src="<?php echo BASE_URL; ?>/icons/archive-icon.svg" class="sidebar-icon"> <span>Archived classes</span>
    </a>
    <a href="<?php echo BASE_URL; ?>/settings/index.php" class="sidebar-link <?php echo ($current_dir === 'settings') ? 'active' : ''; ?>">
      <img src="<?php echo BASE_URL; ?>/icons/setting-icon.svg" class="sidebar-icon"> <span>Settings</span>
    </a>
  </div>
</aside>

<script>
function toggleEnrolled() {
  const list = document.getElementById('enrolled-list');
  const arrow = document.getElementById('enrolled-arrow');
  
  if (list.classList.contains('hidden')) {
    list.classList.remove('hidden');
    arrow.style.transform = 'rotate(0deg)';
  } else {
    list.classList.add('hidden');
    arrow.style.transform = 'rotate(-90deg)';
  }
}
</script>

<style>
  .sidebar-section {
    margin-bottom: 20px;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--border);
  }
  .sidebar-section:last-child {
    border-bottom: none;
  }
  .sidebar-heading {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 14px;
    font-weight: 500;
    color: black;
    text-transform: none;
    letter-spacing: 0.5px;
    margin: 0;
    padding: 0;
  }
  [data-theme="dark"] .sidebar-heading {
    color: white;
  }
  .sidebar-dropdown-trigger {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    padding: 0 12px 0 28px;
    height: 40px;
    margin-bottom: 12px;
    transition: all 0.2s;
    border-radius: 0 24px 24px 0;
    margin-right: 12px;
  }
  .sidebar-dropdown-trigger:hover {
    background: var(--surface-alt);
  }
  .sidebar-dropdown-trigger.active-trigger {
    background: var(--primary-light);
    color: var(--primary);
    font-weight: 600;
  }
  [data-theme="dark"] .sidebar-dropdown-trigger.active-trigger {
    background: #173149;
    color: var(--primary);
  }
  .dropdown-arrow {
    font-size: 16px;
    transition: transform 0.3s ease;
  }
  .sidebar-dropdown-content {
    display: block;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
  }
  .sidebar-dropdown-content.hidden {
    display: none;
  }
  .sidebar-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 12px 12px 28px;
    border-radius: 0 24px 24px 0;
    margin-right: 12px;
    color: var(--text);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
  }
  .class-initial-circle {
    width: 20px;
    height: 20px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 600;
    flex-shrink: 0;
  }
  .sidebar-link:hover {
    background: var(--surface-alt);
    color: var(--primary);
  }
  .sidebar-link.active {
    background: var(--primary-light);
    color: var(--primary);
    font-weight: 600;
  }
  .sidebar-icon {
    width: 20px;
    height: 20px;
    text-align: center;
  }
  .sidebar-class-link {
    font-size: 13px;
    padding: 8px 12px 8px 28px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Collapsed sidebar styles */
  .sidebar.collapsed .sidebar-link span,
  .sidebar.collapsed .enrolled-section {
    display: none !important;
  }
</style>
