<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$assignment_id = $_GET['assignment_id'] ?? null;
$class_id = $_GET['class_id'] ?? null;

if (!$assignment_id || !$class_id) {
    die('Invalid request.');
}

// Verify user is a member of this class
$stmt = $pdo->prepare('SELECT id FROM class_members WHERE class_id = ? AND user_id = ?');
$stmt->execute([$class_id, $user['id']]);
if (!$stmt->fetch()) {
    die('You do not have access to this class.');
}

// Get assignment details
$stmt = $pdo->prepare('SELECT a.*, c.name as class_name FROM assignments a JOIN classes c ON a.class_id = c.id WHERE a.id = ? AND a.class_id = ?');
$stmt->execute([$assignment_id, $class_id]);
$assignment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$assignment) {
    die('Assignment not found.');
}

// Get existing submission if any
$stmt = $pdo->prepare('SELECT * FROM submissions WHERE assignment_id = ? AND user_id = ?');
$stmt->execute([$assignment_id, $user['id']]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');

    // Validation
    if (empty($content)) {
        $errors[] = 'Submission content is required.';
    }

    if (empty($errors)) {
        try {
            if ($submission) {
                // Update existing submission
                $stmt = $pdo->prepare('UPDATE submissions SET content = ?, status = ?, submitted_at = NOW() WHERE id = ?');
                $stmt->execute([$content, 'handed_in', $submission['id']]);
            } else {
                // Create new submission
                $stmt = $pdo->prepare('
                    INSERT INTO submissions (assignment_id, user_id, content, status)
                    VALUES (?, ?, ?, ?)
                ');
                $stmt->execute([$assignment_id, $user['id'], $content, 'handed_in']);
            }
            $success = true;
            header('Location: ' . BASE_URL . '/classes/classwork.php?class_id=' . $class_id);
            exit;
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
    <title>Submit Assignment | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 40px auto;
        }
        .assignment-header {
            background: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .assignment-header h1 {
            margin: 0 0 12px;
            color: var(--primary);
        }
        .assignment-meta {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 12px;
        }
        .assignment-class {
            font-size: 13px;
            color: var(--muted);
        }
        .form-container {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 200px;
        }
        .form-group textarea:focus {
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
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
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
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cancel-btn:hover {
            background: #f0f4f9;
        }
        .submitted-info {
            background: #e8f5e9;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            color: #2e7d32;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <div class="container">
                <div class="assignment-header">
                    <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
                    <div class="assignment-meta">
                        <span>📚 <?php echo htmlspecialchars($assignment['class_name']); ?></span>
                        <?php if ($assignment['due_date']): ?>
                            <span>📅 Due: <?php echo date('M d, Y • H:i', strtotime($assignment['due_date'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-container">
                    <?php if ($submission): ?>
                        <div class="submitted-info">
                            ✓ You submitted this assignment on <?php echo date('M d, Y • H:i', strtotime($submission['submitted_at'])); ?>
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
                            <label for="content">Your Submission</label>
                            <textarea id="content" name="content" required placeholder="Type your answer or paste your work here..."><?php echo htmlspecialchars($_POST['content'] ?? ($submission['content'] ?? '')); ?></textarea>
                        </div>

                        <div class="button-group">
                            <button type="submit" class="submit-btn">Submit Assignment</button>
                            <a href="<?php echo BASE_URL; ?>/classes/classwork.php?class_id=<?php echo $class_id; ?>" class="cancel-btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
