<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$assignment_id = $_GET['assignment_id'] ?? null;
$class_id = $_GET['class_id'] ?? null;

if (!$assignment_id || !$class_id) {
    die('Invalid request.');
}

require_class_member($pdo, $class_id, $user['id']);

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
            header('Location: /Uni-Team-Project/google-classroom-clone-2/classes/classwork.php?class_id=' . $class_id);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<?php $pageTitle = 'Submit Assignment | Classroom Clone'; include __DIR__ . '/../includes/header.php'; ?>
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
                        <span>&#x1F4DA; <?php echo htmlspecialchars($assignment['class_name']); ?></span>
                        <?php if ($assignment['due_date']): ?>
                            <span>&#x1F4C5; Due: <?php echo format_date($assignment['due_date']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-container" style="margin: 0; max-width: none;">
                    <?php if ($submission): ?>
                        <div class="submitted-info">
                            &check; You submitted this assignment on <?php echo format_date($submission['submitted_at']); ?>
                        </div>
                    <?php endif; ?>

                    <?php include __DIR__ . '/../includes/alerts.php'; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="content">Your Submission</label>
                            <textarea id="content" name="content" required placeholder="Type your answer or paste your work here..." style="min-height: 200px;"><?php echo htmlspecialchars($_POST['content'] ?? ($submission['content'] ?? '')); ?></textarea>
                        </div>

                        <div class="button-group">
                            <button type="submit" class="submit-btn">Submit Assignment</button>
                            <a href="/Uni-Team-Project/google-classroom-clone-2/classes/classwork.php?class_id=<?php echo $class_id; ?>" class="cancel-btn">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
