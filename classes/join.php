<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));

    // Validation
    if (empty($code)) {
        $errors[] = 'Class code is required.';
    }

    if (empty($errors)) {
        try {
            // Find class by code
            $stmt = $pdo->prepare('SELECT id, name FROM classes WHERE code = ?');
            $stmt->execute([$code]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$class) {
                $errors[] = 'Invalid class code.';
            } else {
                // Check if user is already in class
                $stmt = $pdo->prepare('SELECT id FROM class_members WHERE class_id = ? AND user_id = ?');
                $stmt->execute([$class['id'], $user['id']]);
                if ($stmt->fetch()) {
                    $errors[] = 'You are already in this class.';
                } else {
                    // Add user to class as student
                    $stmt = $pdo->prepare('INSERT INTO class_members (class_id, user_id, role) VALUES (?, ?, ?)');
                    $stmt->execute([$class['id'], $user['id'], 'student']);
                    $success = true;
                    header('Location: stream.php?class_id=' . $class['id']);
                    exit;
                }
            }
        } catch (PDOException $e) {
            error_log('Join class failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Class | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-container {
            max-width: 500px;
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 40px auto;
        }
        .form-container h1 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 12px;
        }
        .form-container p {
            text-align: center;
            color: var(--muted);
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text);
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
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
        .cancel-link {
            text-align: center;
            margin-top: 16px;
        }
        .cancel-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        .info-box {
            background: var(--surface-alt);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--muted);
            border-left: 4px solid var(--primary);
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>
            
            <div class="form-container">
                <h1>Join a Class</h1>
                <p>Enter your class code to join</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div>✗ <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="info-box">
                    💡 Ask your teacher for a class code. It's usually 6 letters or numbers.
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label for="code">Class Code</label>
                        <input type="text" id="code" name="code" maxlength="6" placeholder="e.g., ABC123" required>
                    </div>

                    <button type="submit" class="submit-btn">Join Class</button>
                </form>

                <div class="cancel-link">
                    <a href="/Uni-Team-Project/google-classroom-clone-2/home/dashboard.php">Back to dashboard</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
