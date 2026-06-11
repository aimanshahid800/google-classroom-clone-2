<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$errors = [];
$success = false;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        $errors[] = 'Name is required.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
            $stmt->execute([$name, $user['id']]);
            $_SESSION['user']['name'] = $name;
            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password)) {
        $errors[] = 'Current password is required.';
    }
    if (empty($new_password) || strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }
    if ($new_password !== $confirm_password) {
        $errors[] = 'New passwords do not match.';
    }

    if (empty($errors)) {
        // Verify current password
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($current_password, $row['password'])) {
            $errors[] = 'Current password is incorrect.';
        } else {
            try {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->execute([$hashed, $user['id']]);
                $success = true;
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Refresh user data
$stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .settings-header {
            margin-bottom: 32px;
        }
        .settings-header h1 {
            margin: 0;
            font-size: 28px;
        }
        .settings-section {
            background: var(--surface);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
        }
        .settings-section h2 {
            margin: 0 0 20px;
            font-size: 18px;
            color: var(--text);
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }
        .profile-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }
        .profile-avatar {
            width: 72px;
            height: 72px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: 600;
        }
        .profile-details h3 {
            margin: 0 0 4px;
            font-size: 18px;
        }
        .profile-details p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }
        .profile-role {
            display: inline-block;
            padding: 4px 10px;
            background: var(--surface-alt);
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            color: var(--primary);
            margin-top: 8px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--text);
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            max-width: 400px;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }
        .form-group input:disabled {
            background: var(--surface-alt);
            color: var(--muted);
        }
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #66bb6a;
        }
        .save-btn {
            padding: 10px 24px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            margin-top: 8px;
        }
        .save-btn:hover {
            background: #1765cc;
        }
        .notification-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-label {
            font-size: 14px;
            color: var(--text);
        }
        .notification-desc {
            font-size: 12px;
            color: var(--muted);
            margin-top: 4px;
        }
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        .toggle-switch input:checked + .toggle-slider {
            background-color: var(--primary);
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }
        .account-info {
            margin-top: 16px;
            font-size: 13px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <div class="settings-header">
                <h1>Settings</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">Changes saved successfully.</div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Profile Section -->
            <div class="settings-section">
                <h2>Profile</h2>
                <div class="profile-info">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($profile['name'], 0, 1)); ?>
                    </div>
                    <div class="profile-details">
                        <h3><?php echo htmlspecialchars($profile['name']); ?></h3>
                        <p><?php echo htmlspecialchars($profile['email']); ?></p>
                        <span class="profile-role"><?php echo ucfirst($profile['role']); ?></span>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label for="name">Display Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($profile['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($profile['email']); ?>" disabled>
                    </div>
                    <button type="submit" class="save-btn">Save Changes</button>
                </form>
                <div class="account-info">
                    Member since: <?php echo (new DateTime($profile['created_at']))->format('F j, Y'); ?>
                </div>
            </div>

            <!-- Change Password Section -->
            <div class="settings-section">
                <h2>Change Password</h2>
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="save-btn">Update Password</button>
                </form>
            </div>

            <!-- Notifications Section -->
            <div class="settings-section">
                <h2>Notifications</h2>
                <div class="notification-item">
                    <div>
                        <div class="notification-label">Email notifications</div>
                        <div class="notification-desc">Receive email for class updates and announcements</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="notification-item">
                    <div>
                        <div class="notification-label">Comments on your posts</div>
                        <div class="notification-desc">Get notified when someone comments on your posts</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="notification-item">
                    <div>
                        <div class="notification-label">Comments that mention you</div>
                        <div class="notification-desc">Get notified when you are mentioned in comments</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="notification-item">
                    <div>
                        <div class="notification-label">Private comments on your work</div>
                        <div class="notification-desc">Get notified about private teacher comments</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="notification-item">
                    <div>
                        <div class="notification-label">Assignment due date reminders</div>
                        <div class="notification-desc">Receive reminders before assignments are due</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
