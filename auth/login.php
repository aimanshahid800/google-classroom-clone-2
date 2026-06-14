<?php
require_once __DIR__ . '/../config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email)) {
        $errors[] = 'Email is required.';
    }
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                header('Location: ' . BASE_URL . '/home/dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid email or password.';
            }
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
    <title>Login | Classroom Clone</title>
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
        <h1>Sign In</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div>✗ <?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autocomplete="off" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>

            <button type="submit" class="submit-btn">Sign In</button>
        </form>

        <div class="auth-link">
            Don't have an account? <a href="register.php">Create one</a>
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
