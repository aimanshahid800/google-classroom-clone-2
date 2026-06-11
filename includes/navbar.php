<?php
// Navbar markup for all pages
$user = $_SESSION['user'] ?? null;
?>
<nav class="navbar card" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; margin-bottom: 24px;">
  <div class="navbar-left" style="display: flex; gap: 16px; align-items: center;">
    <button class="btn-primary" style="padding: 8px 12px; background: none; border: none; color: var(--text);" onclick="toggleSidebar()">
      <img src="<?php echo BASE_URL; ?>/icons/Hamburger-Menu-icon.png" style="width: 24px; height: 24px;">
    </button>
    <a href="<?php echo BASE_URL; ?>/home/dashboard.php" class="logo" style="display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 600; color: var(--primary); text-decoration: none;">
      <img src="<?php echo BASE_URL; ?>/icons/logo.png" style="width: 40px; height: 40px; object-fit: contain;"> Classroom
    </a>
  </div>
  <div class="navbar-actions" style="display: flex; gap: 16px; align-items: center;">
    <div class="plus-container">
      <div class="plus-trigger" onclick="togglePlusDropdown(event)">
        <img src="<?php echo BASE_URL; ?>/icons/plus-icon.svg" alt="Plus">
      </div>
      <div class="plus-dropdown" id="plusDropdown">
        <a href="<?php echo BASE_URL; ?>/classes/join.php" class="plus-item">Join class</a>
        <a href="<?php echo BASE_URL; ?>/classes/create.php" class="plus-item">Create class</a>
      </div>
    </div>
    <div class="theme-switch" onclick="toggleTheme()" aria-label="Toggle dark mode" title="Toggle dark mode">
      <div class="switch-track">
        <div class="track-icon sun">
          <img src="<?php echo BASE_URL; ?>/icons/brightness-1%20icon%20sun.svg" alt="Sun">
        </div>
        <div class="track-icon moon">
          <img src="<?php echo BASE_URL; ?>/icons/brightness-2%20icon%20moon.png" alt="Moon">
        </div>
      </div>
      <div class="switch-thumb">
        <div class="thumb-icon sun">
          <img src="<?php echo BASE_URL; ?>/icons/brightness-1%20icon%20sun.svg" alt="Sun">
        </div>
        <div class="thumb-icon moon">
          <img src="<?php echo BASE_URL; ?>/icons/brightness-2%20icon%20moon.png" alt="Moon">
        </div>
      </div>
    </div>
    <?php if ($user): ?>
      <div class="profile-container">
        <div class="profile-trigger" onclick="toggleProfileDropdown(event)">
          <img src="<?php echo BASE_URL; ?>/icons/profile-icon.svg" style="width: 32px; height: 32px; border-radius: 50%;">
        </div>
        <div class="profile-dropdown" id="profileDropdown">
          <div class="dropdown-section">
            <div class="dropdown-user-info">
              <img src="<?php echo BASE_URL; ?>/icons/profile-icon.svg" style="width: 32px; height: 32px; border-radius: 50%;">
              <div>
                <div class="dropdown-user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="dropdown-user-email"><?php echo htmlspecialchars($user['email']); ?></div>
              </div>
            </div>
          </div>
          <div class="dropdown-section" style="border-bottom: none;">
            <button class="theme-toggle" onclick="toggleTheme()">
              <span>🌙 Dark Mode</span>
              <span id="themeStatus">Off</span>
            </button>
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="dropdown-item" style="border-radius: 8px; margin-top: 8px;">
              <span>🚪 Logout</span>
            </a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</nav>

<script>
function toggleProfileDropdown(event) {
  event.stopPropagation();
  document.getElementById('profileDropdown').classList.toggle('show');
}

function togglePlusDropdown(event) {
  event.stopPropagation();
  document.getElementById('plusDropdown').classList.toggle('show');
}

window.onclick = function(event) {
  if (!event.target.closest('.profile-container')) {
    document.getElementById('profileDropdown')?.classList.remove('show');
  }
  if (!event.target.closest('.plus-container')) {
    document.getElementById('plusDropdown')?.classList.remove('show');
  }
}

function toggleTheme() {
  const currentTheme = document.documentElement.getAttribute('data-theme');
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);
  document.getElementById('themeStatus').innerText = newTheme === 'dark' ? 'On' : 'Off';
}

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const content = document.querySelector('.content');
  if (sidebar && content) {
    sidebar.classList.toggle('collapsed');
    content.classList.toggle('expanded');
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
  }
}

(function() {
  const savedTheme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);
  const statusEl = document.getElementById('themeStatus');
  if (statusEl) statusEl.innerText = savedTheme === 'dark' ? 'On' : 'Off';

  const sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
  if (sidebarCollapsed) {
    document.addEventListener('DOMContentLoaded', () => {
      const sidebar = document.querySelector('.sidebar');
      const content = document.querySelector('.content');
      if (sidebar && content) {
        sidebar.classList.add('collapsed');
        content.classList.add('expanded');
      }
    });
  }
})();
</script>
