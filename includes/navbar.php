<?php
// Navbar markup for all pages
$user = $_SESSION['user'] ?? null;
$is_student = ($user && $user['role'] === 'student');
$is_teacher = ($user && $user['role'] === 'teacher');
?>
<nav class="navbar" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; margin-bottom: 24px;">
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
        <?php if ($is_student): ?>
          <a href="#" class="plus-item" onclick="openStudentModal('join'); return false;">Join class</a>
          <a href="#" class="plus-item" onclick="openStudentModal('create'); return false;">Create class</a>
        <?php else: ?>
          <a href="#" class="plus-item" onclick="openTeacherJoinModal(); return false;">Join class</a>
          <a href="<?php echo BASE_URL; ?>/classes/create.php" class="plus-item">Create class</a>
        <?php endif; ?>
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
          <div class="profile-pop-top">
            <span class="pop-email"><?php echo htmlspecialchars($user['email']); ?></span>
            <button type="button" class="pop-close-btn" onclick="toggleProfileDropdown(event)">&times;</button>
          </div>
          <div class="profile-pop-avatar-container">
            <img src="<?php echo BASE_URL; ?>/icons/profile.png" class="pop-avatar" onerror="this.src='<?php echo BASE_URL; ?>/icons/profile-icon.svg';" alt="Profile">
          </div>
          <h2 class="pop-greeting">Hi, <?php echo htmlspecialchars($user['name']); ?>!</h2>
          <a href="#" class="pop-manage-btn" onclick="return false;">Manage your Google Account</a>
          <div class="pop-actions-container">
            <a href="#" class="pop-action-btn" onclick="return false;">
              <img src="<?php echo BASE_URL; ?>/icons/circle-plus-icon.svg" class="pop-action-icon" alt="Add">
              Add account
            </a>
            <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="pop-action-btn">
              <img src="<?php echo BASE_URL; ?>/icons/Signout.png" class="pop-action-icon" alt="Sign out">
              Sign out
            </a>
          </div>
          <div class="pop-footer-links">
            <a href="#" onclick="return false;">Privacy Policy</a>
            &middot;
            <a href="#" onclick="return false;">Terms of Service</a>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</nav>

<!-- Global Help FAB -->
<a href="#" class="help-fab" title="Help" onclick="return false;">
  <img src="<?php echo BASE_URL; ?>/icons/help-icon.png" alt="Help">
</a>

<!-- Student Modal -->
<?php if ($is_student): ?>
<div id="studentClassModal" class="student-class-modal" style="display: none;">
  <div class="modal-content">
    <button class="modal-close" onclick="closeStudentModal()">&times;</button>
    <div id="modalCreateState" style="display: none;">
      <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 20px; font-weight: 500;">Students cannot create classes</h3>
      <p style="color: var(--muted); margin-bottom: 24px; font-size: 14px; line-height: 1.5;">Ask your teacher for a class code to join.</p>
      <button class="action-btn-join-pill" onclick="switchModalToJoin()">Join class</button>
    </div>
    <div id="modalJoinState" style="display: none;">
      <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 20px; font-weight: 500; text-align: left;">Join class</h3>
      <p style="color: var(--muted); margin-bottom: 20px; font-size: 14px; text-align: left;">Ask your teacher for the class code, then enter it here.</p>
      <form action="<?php echo BASE_URL; ?>/classes/join.php" method="POST">
        <div class="modal-form-group" style="position: relative; margin-bottom: 24px; text-align: left;">
          <label for="modal_class_code" class="modal-label">Class code</label>
          <input type="text" id="modal_class_code" name="code" required autocomplete="off" class="modal-input">
        </div>
        <div style="text-align: right; display: flex; justify-content: flex-end; gap: 12px; align-items: center;">
          <button type="button" class="modal-cancel-btn" onclick="closeStudentModal()">Cancel</button>
          <button type="submit" class="action-btn-join-pill" style="padding: 10px 24px;">Join</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Teacher Join Restriction Modal -->
<?php if ($is_teacher): ?>
<div id="teacherJoinModal" class="student-class-modal" style="display: none;">
  <div class="modal-content">
    <button class="modal-close" onclick="closeTeacherJoinModal()">&times;</button>
    <h3 style="margin-top: 0; margin-bottom: 12px; font-size: 20px; font-weight: 500;">Only students can join classes</h3>
    <p style="color: var(--muted); margin-bottom: 24px; font-size: 14px; line-height: 1.5;">As a teacher, you can only create classes.</p>
    <a href="<?php echo BASE_URL; ?>/classes/create.php" class="action-btn-join-pill">Create class</a>
  </div>
</div>
<?php endif; ?>

<style>
.student-class-modal {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(0,0,0,0.5); display: flex; align-items: center;
  justify-content: center; z-index: 10000; backdrop-filter: blur(2px);
}
.student-class-modal .modal-content {
  background: #e9eef6; border-radius: 12px; width: 100%; max-width: 420px;
  padding: 32px; position: relative; box-shadow: 0 4px 24px rgba(0,0,0,0.15);
  box-sizing: border-box; text-align: center; border: 1px solid var(--border);
}
[data-theme="dark"] .student-class-modal .modal-content {
  background: #212226; box-shadow: 0 4px 24px rgba(0,0,0,0.4);
}
.student-class-modal .modal-close {
  position: absolute; top: 16px; right: 16px; background: none;
  border: none; font-size: 24px; cursor: pointer; color: var(--muted);
}
.action-btn-join-pill {
  background: #1a73e8; color: white; border: none; padding: 12px 24px;
  font-weight: 600; font-size: 14px; border-radius: 50px; cursor: pointer;
  display: inline-block; transition: background-color 0.2s; text-decoration: none;
}
.action-btn-join-pill:hover { background: #1557b0; }
.modal-cancel-btn {
  background: transparent; color: #1a73e8; border: none;
  padding: 10px 16px; font-weight: 600; font-size: 14px; cursor: pointer;
}
.modal-cancel-btn:hover { background-color: rgba(26,115,232,0.08); border-radius: 50px; }
.modal-form-group { position: relative; margin-top: 20px; }
.modal-form-group .modal-label {
  position: absolute; left: 12px; top: 14px; font-size: 16px;
  color: var(--muted); transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
  pointer-events: none; z-index: 1;
}
.modal-form-group .modal-input {
  width: 100%; padding: 22px 12px 6px 12px; border: none;
  border-bottom: 3px solid var(--border); background: transparent;
  color: var(--text); font-size: 16px; border-radius: 0; box-sizing: border-box;
}
.modal-form-group .modal-input:focus { outline: none; border-bottom-color: #1a73e8; }
.modal-form-group.has-focus .modal-label,
.modal-form-group.has-value .modal-label {
  transform: translate(-10px, -18px) scale(0.75); color: #1a73e8;
}

.profile-container { position: relative; }
.profile-dropdown {
  position: absolute; right: 0; top: 50px; width: 300px;
  background: #e9eef6; border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.15); padding: 16px;
  z-index: 999; box-sizing: border-box; display: none;
  opacity: 0; transform: translateY(-10px);
  transition: opacity 0.2s ease, transform 0.2s ease;
}
[data-theme="dark"] .profile-dropdown {
  background: #202125; box-shadow: 0 4px 20px rgba(0,0,0,0.4); border: 1px solid #444;
}
.profile-dropdown.show { display: block; opacity: 1; transform: translateY(0); }
.profile-pop-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.pop-email { font-size: 13px; color: #5f6368; flex: 1; text-align: center; padding-left: 20px; }
[data-theme="dark"] .pop-email { color: #aaa; }
.pop-close-btn { background: none; border: none; font-size: 20px; color: #5f6368; cursor: pointer; padding: 0; line-height: 1; }
[data-theme="dark"] .pop-close-btn { color: #aaa; }
.profile-pop-avatar-container { display: flex; justify-content: center; margin-bottom: 12px; }
.pop-avatar { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #e0e0e0; object-fit: cover; }
[data-theme="dark"] .pop-avatar { border-color: #555; }
.pop-greeting { font-size: 18px; font-weight: 600; color: #202124; text-align: center; margin: 0 0 16px 0; }
[data-theme="dark"] .pop-greeting { color: #e8eaed; }
.pop-manage-btn {
  display: block; text-align: center; border: 1px solid #dadce0;
  border-radius: 50px; background: transparent; color: #1a73e8;
  font-size: 14px; font-weight: 500; padding: 10px 16px;
  margin-bottom: 20px; text-decoration: none; transition: background-color 0.2s;
}
.pop-manage-btn:hover { background-color: rgba(26,115,232,0.04); }
[data-theme="dark"] .pop-manage-btn { border-color: #444; color: #8ab4f8; }
.pop-actions-container { display: flex; gap: 8px; margin-bottom: 20px; }
.pop-action-btn {
  flex: 1; padding: 10px 8px; text-align: center; color: #202124;
  font-size: 14px; font-weight: 500; text-decoration: none;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  background: #ffffff; border: 1px solid #dadce0; border-radius: 50px;
  transition: background-color 0.2s; box-sizing: border-box;
}
.pop-action-btn:hover { background-color: #f1f3f4; }
[data-theme="dark"] .pop-action-btn { color: #e0e0e0; background: #2a2a2a; border-color: #444; }
[data-theme="dark"] .pop-action-btn:hover { background-color: #3a3a3a; }
.pop-action-icon { width: 16px; height: 16px; object-fit: contain; opacity: 0.75; }
.pop-footer-links { text-align: center; font-size: 12px; color: #5f6368; }
.pop-footer-links a { color: #5f6368; text-decoration: none; }
.pop-footer-links a:hover { text-decoration: underline; }
[data-theme="dark"] .pop-footer-links,
[data-theme="dark"] .pop-footer-links a { color: #aaa; }

.help-fab {
  position: fixed; bottom: 24px; right: 24px; width: 44px; height: 44px;
  border-radius: 50%; background: var(--surface); border: 1px solid var(--border);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15); display: flex; align-items: center;
  justify-content: center; cursor: pointer; z-index: 1100;
  transition: box-shadow 0.2s, background 0.2s; text-decoration: none;
}
.help-fab:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.2); background: var(--surface-alt); }
.help-fab img { width: 24px; height: 24px; object-fit: contain; }
[data-theme="dark"] .help-fab { background: #2a2a2a; border-color: #444; }
</style>

<script>
function toggleProfileDropdown(event) {
  event.stopPropagation();
  const dropdown = document.getElementById('profileDropdown');
  if (dropdown) dropdown.classList.toggle('show');
}

function togglePlusDropdown(event) {
  event.stopPropagation();
  document.getElementById('plusDropdown').classList.toggle('show');
}

document.addEventListener('click', function(event) {
  if (!event.target.closest('.plus-container')) {
    document.getElementById('plusDropdown')?.classList.remove('show');
  }
  if (!event.target.closest('.profile-container')) {
    document.getElementById('profileDropdown')?.classList.remove('show');
  }
});

function toggleTheme() {
  const currentTheme = document.documentElement.getAttribute('data-theme');
  const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);
}

function toggleSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const content = document.querySelector('.content');
  if (sidebar && content) {
    sidebar.classList.toggle('collapsed');
    content.classList.toggle('expanded');
    localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed') ? 'true' : 'false');
  }
}

(function() {
  const savedTheme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);
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

function openStudentModal(mode) {
  document.getElementById('plusDropdown')?.classList.remove('show');
  const modal = document.getElementById('studentClassModal');
  const createState = document.getElementById('modalCreateState');
  const joinState = document.getElementById('modalJoinState');
  if (modal) {
    modal.style.display = 'flex';
    if (mode === 'create') {
      createState.style.display = 'block';
      joinState.style.display = 'none';
    } else {
      createState.style.display = 'none';
      joinState.style.display = 'block';
      setTimeout(() => document.getElementById('modal_class_code')?.focus(), 50);
    }
  }
}

function closeStudentModal() {
  const modal = document.getElementById('studentClassModal');
  if (modal) modal.style.display = 'none';
}

function switchModalToJoin() {
  document.getElementById('modalCreateState').style.display = 'none';
  document.getElementById('modalJoinState').style.display = 'block';
  setTimeout(() => document.getElementById('modal_class_code')?.focus(), 50);
}

function openTeacherJoinModal() {
  document.getElementById('plusDropdown')?.classList.remove('show');
  const modal = document.getElementById('teacherJoinModal');
  if (modal) modal.style.display = 'flex';
}

function closeTeacherJoinModal() {
  const modal = document.getElementById('teacherJoinModal');
  if (modal) modal.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', () => {
  const codeInput = document.getElementById('modal_class_code');
  if (codeInput) {
    codeInput.addEventListener('input', () => {
      codeInput.parentElement.classList.toggle('has-value', codeInput.value.trim() !== '');
    });
    codeInput.addEventListener('focus', () => codeInput.parentElement.classList.add('has-focus'));
    codeInput.addEventListener('blur', () => {
      codeInput.parentElement.classList.remove('has-focus');
      codeInput.parentElement.classList.toggle('has-value', codeInput.value.trim() !== '');
    });
  }
});
</script>