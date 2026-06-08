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
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<?php $pageTitle = 'Join Class | Classroom Clone'; include __DIR__ . '/../includes/header.php'; ?>
    <style>
        .form-group input.code-input {
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>
            
            <div class="form-container" style="max-width: 500px;">
                <h1>Join a Class</h1>
                <p style="text-align: center; color: var(--muted); margin-bottom: 30px; font-size: 14px;">Enter your class code to join</p>

                <?php include __DIR__ . '/../includes/alerts.php'; ?>

                <div class="info-box">
                    &#x1F4A1; Ask your teacher for a class code. It's usually 6 letters or numbers.
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label for="code">Class Code</label>
                        <input type="text" id="code" name="code" class="code-input" maxlength="6" placeholder="e.g., ABC123" required>
                    </div>

                    <button type="submit" class="submit-btn">Join Class</button>
                </form>

                <div class="cancel-link">
                    <a href="/Uni-Team-Project/google-classroom-clone-2/home/dashboard.php">Back to dashboard</a>
                </div>
            </div>
        </main>
    </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
