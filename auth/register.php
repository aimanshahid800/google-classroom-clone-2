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
            $errors[] = 'Database error: ' . $e->getMessage();
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
            $errors[] = 'Database error: ' . $e->getMessage();
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');

        :root {
            --primary: #1a73e8;
        }

        body.light-theme {
            --bg-color: #ffffff;
            --card-bg: #f8fafd;
            --text-color: #1f1f1f;
            --muted: #5f6368;
            --border-color: #444444;
            --focus-color: #1a73e8;
            --card-border: #e0e0e0;
        }

        body.dark-theme {
            --bg-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #e0e0e0;
            --muted: #aaa9a9;
            --border-color: #444444;
            --focus-color: #1a73e8;
            --card-border: #2a2a2a;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .auth-container {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 12px;
            border: 1px solid var(--card-border);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .auth-container h1 {
            text-align: center;
            color: var(--text-color);
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 500;
        }

        .auth-container h1::before {
            content: "";
            display: block;
            width: 120px;
            height: 40px;
            margin: 0 auto 16px auto;
            background: url('../icons/google logo.png') no-repeat center;
            background-size: contain;
        }

        .form-group {
            position: relative;
            margin-bottom: 24px;
        }

        .form-group label {
            position: absolute;
            left: 12px;
            top: 14px;
            font-size: 16px;
            color: var(--muted);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
            z-index: 1;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 22px 12px 6px 12px;
            border: none;
            border-bottom: 3px solid var(--border-color);
            background: transparent;
            color: var(--text-color);
            font-size: 16px;
            border-radius: 0;
            box-sizing: border-box;
            transition: border-color 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-bottom-color: var(--focus-color);
        }

        /* Floating label states */
        .form-group.has-focus label,
        .form-group.has-value label,
        .form-group:has(input:autofill) label,
        .form-group:has(input:-webkit-autofill) label {
            transform: translate(-10px, -18px) scale(0.75);
            color: var(--focus-color);
        }

        .form-group.has-value:not(.has-focus) label,
        .form-group:has(input:autofill):not(.has-focus) label,
        .form-group:has(input:-webkit-autofill):not(.has-focus) label {
            color: var(--muted);
        }

        /* Prevent autofill styling override */
        .form-group input:-webkit-autofill,
        .form-group input:-webkit-autofill:hover, 
        .form-group input:-webkit-autofill:focus, 
        .form-group input:-webkit-autofill:active {
            -webkit-background-clip: text;
            -webkit-text-fill-color: var(--text-color) !important;
            transition: background-color 5000s ease-in-out 0s;
            box-shadow: inset 0 0 20px 20px transparent;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(239, 83, 80, 0.15);
            color: #ef5350;
            border: 1px solid rgba(239, 83, 80, 0.3);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.15);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 24px;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        .submit-btn:hover {
            background: #1557b0;
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        .auth-link {
            text-align: center;
            margin-top: 24px;
            color: var(--muted);
            font-size: 14px;
        }

        .auth-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .auth-link a:hover {
            text-decoration: underline;
        }

        /* Theme Toggle styles */
        .theme-toggle {
            position: fixed;
            bottom: 24px;
            right: 24px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            padding: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        .theme-toggle img {
            width: 24px;
            height: 24px;
            object-fit: contain;
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
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required autocomplete="off" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autocomplete="off" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
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
                <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6" autocomplete="new-password">
            </div>

            <button type="submit" class="submit-btn">Register</button>
        </form>

        <div class="auth-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const theme = localStorage.getItem('theme') || 'dark';
        document.body.className = theme + '-theme';

        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'theme-toggle';
        toggleBtn.setAttribute('aria-label', 'Toggle theme');
        toggleBtn.innerHTML = `
            <img class="sun-icon" src="../icons/brightness-1 icon sun.svg" style="display: ${theme === 'dark' ? 'block' : 'none'}" alt="Sun Icon">
            <img class="moon-icon" src="../icons/brightness-2 icon moon.png" style="display: ${theme === 'light' ? 'block' : 'none'}" alt="Moon Icon">
        `;
        document.body.appendChild(toggleBtn);

        toggleBtn.addEventListener('click', () => {
            const isDark = document.body.classList.contains('dark-theme');
            const newTheme = isDark ? 'light' : 'dark';
            document.body.className = newTheme + '-theme';
            localStorage.setItem('theme', newTheme);

            toggleBtn.querySelector('.sun-icon').style.display = newTheme === 'dark' ? 'block' : 'none';
            toggleBtn.querySelector('.moon-icon').style.display = newTheme === 'light' ? 'block' : 'none';
        });

        const inputs = document.querySelectorAll('.form-group input, .form-group select');
        inputs.forEach(input => {
            const checkValue = () => {
                if (input.value.trim() !== '') {
                    input.parentElement.classList.add('has-value');
                } else {
                    input.parentElement.classList.remove('has-value');
                }
            };

            input.addEventListener('input', checkValue);
            input.addEventListener('change', checkValue);
            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('has-focus');
                checkValue();
            });
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('has-focus');
            });

            checkValue();
            if (document.activeElement === input) {
                input.parentElement.classList.add('has-focus');
            }
        });
    });
    </script>
</body>
</html>
