<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    render_error_page('Class Not Found', 'No class was specified.', 404);
}

// Verify user is teacher in this class
try {
    $stmt = $pdo->prepare('
        SELECT cm.id FROM class_members cm
        WHERE cm.class_id = ? AND cm.user_id = ? AND cm.role = "teacher"
    ');
    $stmt->execute([$class_id, $user['id']]);
    if (!$stmt->fetch()) {
        render_error_page('Access Denied', 'Only teachers can create assignments.');
    }
} catch (PDOException $e) {
    error_log('Assignment create teacher check failed: ' . $e->getMessage());
    render_error_page('Error', 'Something went wrong. Please try again later.', 500);
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    $due_date = $_POST['due_date'] ?? '';

    // Validation
    if (empty($title)) {
        $errors[] = 'Assignment title is required.';
    }
    if (!empty($due_date)) {
        // Validate date format
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $due_date);
        if (!$date) {
            $errors[] = 'Invalid date format.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO assignments (class_id, title, topic, due_date)
                VALUES (?, ?, ?, ?)
            ');
            $stmt->execute([$class_id, $title, $topic, $due_date ?: null]);
            $success = true;
            header('Location: /Uni-Team-Project/google-classroom-clone-2/classes/classwork.php?class_id=' . $class_id);
            exit;
        } catch (PDOException $e) {
            error_log('Assignment creation failed: ' . $e->getMessage());
            $errors[] = 'Failed to create assignment. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-container {
            max-width: 600px;
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
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
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
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .submit-btn {
            flex: 1;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .submit-btn:hover {
            background: #1765cc;
        }
        .cancel-btn {
            flex: 1;
            padding: 12px;
            background: var(--surface-alt);
            color: var(--primary);
            border: 2px solid var(--primary);
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .cancel-btn:hover {
            background: #f0f4f9;
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>
            
            <div class="form-container">
                <h1>Create Assignment</h1>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div>✗ <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" placeholder="e.g., Web Design Project">
                    </div>

                    <div class="form-group">
                        <label for="topic">Topic</label>
                        <input type="text" id="topic" name="topic" value="<?php echo htmlspecialchars($_POST['topic'] ?? ''); ?>" placeholder="e.g., HTML & CSS">
                    </div>

                    <div class="form-group">
                        <label for="due_date">Due Date & Time</label>
                        <input type="datetime-local" id="due_date" name="due_date" value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>">
                    </div>

                    <div class="button-group">
                        <button type="submit" class="submit-btn">Create Assignment</button>
                        <a href="/Uni-Team-Project/google-classroom-clone-2/classes/classwork.php?class_id=<?php echo $class_id; ?>" class="cancel-btn" style="text-align: center; display: flex; align-items: center; justify-content: center; text-decoration: none;">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
