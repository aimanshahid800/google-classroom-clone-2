<?php
require_once __DIR__ . '/../config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = $_POST['role'] ?? 'student';

    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!in_array($role, ['student', 'teacher'])) {
        $errors[] = 'Invalid role selected.';
    }

    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered.';
            }
        } catch (PDOException $e) {
            error_log('Registration check error: ' . $e->getMessage());
            $errors[] = 'A system error occurred. Please try again later.';
        }
    }

    // Insert user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hashed_password, $role]);
            $success = true;
            // Redirect to login after 2 seconds
            echo '<meta http-equiv="refresh" content="2; url=login.php">';
        } catch (PDOException $e) {
            error_log('Registration insert error: ' . $e->getMessage());
            $errors[] = 'A system error occurred. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .auth-container h1 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 30px;
            font-size: 28px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--text);
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
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
        .submit-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }
        .submit-btn:hover {
            background: #1765cc;
        }
        .auth-link {
            text-align: center;
            margin-top: 20px;
            color: var(--muted);
            font-size: 14px;
        }
        .auth-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .auth-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1>Create Account</h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✓ Registration successful! Redirecting to login...
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div>✗ <?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrf_input(); ?>
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="role">I am a...</label>
                <select id="role" name="role" required>
                    <option value="student" <?php echo ($_POST['role'] ?? 'student') === 'student' ? 'selected' : ''; ?>>Student</option>
                    <option value="teacher" <?php echo ($_POST['role'] ?? '') === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
            </div>

            <button type="submit" class="submit-btn">Register</button>
        </form>

        <div class="auth-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>
</body>
</html>
