<?php
// Navbar markup for all pages
$user = $_SESSION['user'] ?? null;
?>
<nav class="navbar card" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; margin-bottom: 24px;">
  <div class="navbar-left" style="display: flex; gap: 16px; align-items: center;">
    <button class="btn-primary" style="padding: 8px 12px;">☰</button>
    <div class="logo" style="font-size: 18px; font-weight: 600; color: var(--primary);">📚 Classroom</div>
  </div>
  <div class="navbar-actions" style="display: flex; gap: 12px; align-items: center;">
    <button class="btn-primary">+ Create</button>
    <?php if ($user): ?>
      <span style="color: var(--muted); font-size: 14px;">
        <?php echo htmlspecialchars($user['name']); ?>
      </span>
      <a href="/Uni-Team-Project/google-classroom-clone-2/auth/logout.php" style="color: var(--primary); text-decoration: none; font-weight: 500; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;">
        Logout
      </a>
    <?php endif; ?>
  </div>
</nav>
