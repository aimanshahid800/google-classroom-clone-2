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

    // Generate random 6-char code
    $code = strtoupper(substr(md5(uniqid()), 0, 6));

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
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<?php $pageTitle = 'Create Class | Classroom Clone'; include __DIR__ . '/../includes/header.php'; ?>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>
            
            <div class="form-container" style="max-width: 500px;">
                <h1>Create a Class</h1>

                <?php include __DIR__ . '/../includes/alerts.php'; ?>

                <form method="POST">
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
