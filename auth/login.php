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
                header('Location: /Uni-Team-Project/google-classroom-clone-2/home/dashboard.php');
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
        .form-group input {
            width: 100%;
            padding: 12px;
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
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="submit-btn">Sign In</button>
        </form>

        <div class="auth-link">
            Don't have an account? <a href="register.php">Create one</a>
        </div>
    </div>
</body>
</html>
