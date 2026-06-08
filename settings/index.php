<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$user_id = (int) $user['id'];

// Ensure the settings table exists (safe for already-imported databases)
$pdo->exec('
    CREATE TABLE IF NOT EXISTS user_settings (
        user_id INT PRIMARY KEY,
        email_notifications TINYINT(1) NOT NULL DEFAULT 1,
        comment_notifications TINYINT(1) NOT NULL DEFAULT 1,
        due_date_reminders TINYINT(1) NOT NULL DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
');

$profile_errors = [];
$profile_success = false;
$notif_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';

    if ($form === 'profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($name === '') {
            $profile_errors[] = 'Name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $profile_errors[] = 'A valid email is required.';
        }

        // Email must be unique (excluding the current user)
        if (empty($profile_errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $profile_errors[] = 'That email is already in use by another account.';
            }
        }

        // Password change is optional
        $change_password = $new_password !== '' || $confirm_password !== '';
        if ($change_password) {
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || !password_verify($current_password, $row['password'])) {
                $profile_errors[] = 'Current password is incorrect.';
            }
            if (strlen($new_password) < 6) {
                $profile_errors[] = 'New password must be at least 6 characters.';
            }
            if ($new_password !== $confirm_password) {
                $profile_errors[] = 'New passwords do not match.';
            }
        }

        if (empty($profile_errors)) {
            try {
                if ($change_password) {
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?');
                    $stmt->execute([$name, $email, $hashed, $user_id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                    $stmt->execute([$name, $email, $user_id]);
                }
                // Keep the session in sync
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                $user = current_user();
                $profile_success = true;
            } catch (PDOException $e) {
                $profile_errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif ($form === 'notifications') {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $comment_notifications = isset($_POST['comment_notifications']) ? 1 : 0;
        $due_date_reminders = isset($_POST['due_date_reminders']) ? 1 : 0;

        $stmt = $pdo->prepare('
            INSERT INTO user_settings (user_id, email_notifications, comment_notifications, due_date_reminders)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                email_notifications = VALUES(email_notifications),
                comment_notifications = VALUES(comment_notifications),
                due_date_reminders = VALUES(due_date_reminders)
        ');
        $stmt->execute([$user_id, $email_notifications, $comment_notifications, $due_date_reminders]);
        $notif_success = true;
    }
}

// Load current notification preferences (defaults to enabled)
$stmt = $pdo->prepare('SELECT email_notifications, comment_notifications, due_date_reminders FROM user_settings WHERE user_id = ?');
$stmt->execute([$user_id]);
$prefs = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'email_notifications' => 1,
    'comment_notifications' => 1,
    'due_date_reminders' => 1,
];

$initials = strtoupper(substr($user['name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings | Classroom Clone</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    .settings-wrap { max-width: 720px; }
    .settings-wrap h1 { font-size: 28px; margin: 0 0 24px; }
    .panel {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 24px;
      margin-bottom: 24px;
    }
    .panel h2 { font-size: 18px; margin: 0 0 4px; }
    .panel .subtitle { color: var(--muted); font-size: 14px; margin: 0 0 20px; }
    .profile-id { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
    .avatar {
      width: 56px; height: 56px; border-radius: 50%;
      background: var(--primary); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px; font-weight: 600;
    }
    .role-pill {
      display: inline-block; margin-top: 4px;
      font-size: 12px; font-weight: 600; text-transform: capitalize;
      background: var(--surface-alt); border: 1px solid var(--border);
      border-radius: 999px; padding: 2px 10px; color: var(--muted);
    }
    .form-row { margin-bottom: 16px; }
    .form-row label { display: block; font-weight: 500; margin-bottom: 6px; }
    .form-row input {
      width: 100%; padding: 12px 14px;
      border: 1px solid var(--border); border-radius: 10px;
      background: var(--surface-alt); font-size: 14px;
    }
    .form-row input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26,115,232,0.1); }
    .divider { border: none; border-top: 1px solid var(--border); margin: 24px 0; }
    .pw-note { font-size: 13px; color: var(--muted); margin: 0 0 16px; }
    .toggle-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 14px 0; border-bottom: 1px solid var(--border);
    }
    .toggle-row:last-of-type { border-bottom: none; }
    .toggle-row .label { font-weight: 500; }
    .toggle-row .desc { font-size: 13px; color: var(--muted); margin-top: 2px; }
    .save-btn {
      padding: 12px 24px; background: var(--primary); color: #fff;
      border: none; border-radius: 999px; font-weight: 600; font-size: 14px;
    }
    .save-btn:hover { background: #1765cc; }
    .alert { padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; }
    .alert-error { background: #fce8e6; color: #c5221f; border: 1px solid #f5b5b0; }
    .alert-success { background: #e6f4ea; color: #137333; border: 1px solid #a8d5b5; }
    .switch { position: relative; display: inline-block; width: 44px; height: 24px; flex: none; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
      position: absolute; cursor: pointer; inset: 0;
      background: var(--border); border-radius: 999px; transition: 0.2s;
    }
    .slider:before {
      content: ""; position: absolute; height: 18px; width: 18px; left: 3px; top: 3px;
      background: #fff; border-radius: 50%; transition: 0.2s;
    }
    .switch input:checked + .slider { background: var(--primary); }
    .switch input:checked + .slider:before { transform: translateX(20px); }
  </style>
</head>
<body>
  <div class="page-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
      <?php include __DIR__ . '/../includes/navbar.php'; ?>

      <div class="settings-wrap">
        <h1>Settings</h1>

        <!-- Profile -->
        <div class="panel">
          <h2>Profile</h2>
          <p class="subtitle">Update your account details.</p>

          <div class="profile-id">
            <div class="avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div>
              <div style="font-weight:600;"><?php echo htmlspecialchars($user['name']); ?></div>
              <span class="role-pill"><?php echo htmlspecialchars($user['role']); ?></span>
            </div>
          </div>

          <?php if ($profile_success): ?>
            <div class="alert alert-success">Profile updated successfully.</div>
          <?php endif; ?>
          <?php if (!empty($profile_errors)): ?>
            <div class="alert alert-error">
              <?php foreach ($profile_errors as $e): ?>
                <div><?php echo htmlspecialchars($e); ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form method="post">
            <input type="hidden" name="form" value="profile">
            <div class="form-row">
              <label for="name">Full name</label>
              <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="form-row">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <hr class="divider">
            <p class="pw-note">Leave the password fields blank to keep your current password.</p>

            <div class="form-row">
              <label for="current_password">Current password</label>
              <input type="password" id="current_password" name="current_password" autocomplete="current-password">
            </div>
            <div class="form-row">
              <label for="new_password">New password</label>
              <input type="password" id="new_password" name="new_password" autocomplete="new-password">
            </div>
            <div class="form-row">
              <label for="confirm_password">Confirm new password</label>
              <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
            </div>

            <button type="submit" class="save-btn">Save changes</button>
          </form>
        </div>

        <!-- Notifications -->
        <div class="panel">
          <h2>Notifications</h2>
          <p class="subtitle">Choose what you get notified about.</p>

          <?php if ($notif_success): ?>
            <div class="alert alert-success">Notification preferences saved.</div>
          <?php endif; ?>

          <form method="post">
            <input type="hidden" name="form" value="notifications">
            <div class="toggle-row">
              <div>
                <div class="label">Email notifications</div>
                <div class="desc">Receive class updates by email.</div>
              </div>
              <label class="switch">
                <input type="checkbox" name="email_notifications" <?php echo $prefs['email_notifications'] ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>
            <div class="toggle-row">
              <div>
                <div class="label">Comment notifications</div>
                <div class="desc">Get notified about comments on your work.</div>
              </div>
              <label class="switch">
                <input type="checkbox" name="comment_notifications" <?php echo $prefs['comment_notifications'] ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>
            <div class="toggle-row">
              <div>
                <div class="label">Due date reminders</div>
                <div class="desc">Remind me before assignments are due.</div>
              </div>
              <label class="switch">
                <input type="checkbox" name="due_date_reminders" <?php echo $prefs['due_date_reminders'] ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </label>
            </div>

            <div style="margin-top:20px;">
              <button type="submit" class="save-btn">Save preferences</button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
