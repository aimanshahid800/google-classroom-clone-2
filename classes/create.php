<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
if ($user['role'] !== 'teacher') {
    die('Only teachers can create classes.');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $room = trim($_POST['room'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = 'Class name is required.';
    }

    // Generate cryptographically secure 6-char code
    $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

    if (empty($errors)) {
        try {
            // Insert class
            $stmt = $pdo->prepare('
                INSERT INTO classes (name, section, subject, room, code, owner_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$name, $section, $subject, $room, $code, $user['id']]);
            $class_id = $pdo->lastInsertId();

            // Add teacher as class member
            $stmt = $pdo->prepare('
                INSERT INTO class_members (class_id, user_id, role)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$class_id, $user['id'], 'teacher']);

            $success = true;
            header('Location: stream.php?class_id=' . $class_id);
            exit;
        } catch (PDOException $e) {
            error_log('Class creation error: ' . $e->getMessage());
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
    <title>Create Class | Classroom Clone</title>
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
            margin-bottom: 30px;
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
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
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
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>
            
            <div class="form-container">
                <h1>Create a Class</h1>

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
                        <label for="name">Class Name *</label>
                        <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="section">Section</label>
                        <input type="text" id="section" name="section" value="<?php echo htmlspecialchars($_POST['section'] ?? ''); ?>" placeholder="e.g., (23-27) web">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" placeholder="e.g., Web Engineering">
                    </div>

                    <div class="form-group">
                        <label for="room">Room</label>
                        <input type="text" id="room" name="room" value="<?php echo htmlspecialchars($_POST['room'] ?? ''); ?>" placeholder="e.g., Room 301">
                    </div>

                    <button type="submit" class="submit-btn">Create Class</button>
                </form>

                <div class="cancel-link">
                    <a href="/Uni-Team-Project/google-classroom-clone-2/home/dashboard.php">Cancel</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
