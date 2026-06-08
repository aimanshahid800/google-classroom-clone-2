<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die('Class not found.');
}

require_teacher($pdo, $class_id, $user['id'], 'Only teachers can create assignments.');

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
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<?php $pageTitle = 'Create Assignment | Classroom Clone'; include __DIR__ . '/../includes/header.php'; ?>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>
            
            <div class="form-container">
                <h1>Create Assignment</h1>

                <?php include __DIR__ . '/../includes/alerts.php'; ?>

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
                        <a href="/Uni-Team-Project/google-classroom-clone-2/classes/classwork.php?class_id=<?php echo $class_id; ?>" class="cancel-btn">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
