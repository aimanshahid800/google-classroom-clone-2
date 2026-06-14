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
            background: #e9eef6;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 40px auto;
        }
        [data-theme="dark"] .form-container {
            background: #202125;
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
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            background: white;
            color: black;
        }
        [data-theme="dark"] .form-group input, [data-theme="dark"] .form-group textarea {
            background: #11110f;
            color: white;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }
        .form-group textarea {
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
            transition: background 0.2s;
        }
        .submit-btn:hover {
            background: #1765cc;
        }
        [data-theme="dark"] .submit-btn {
            background: #173149;
        }
        .cancel-btn {
            width: 100%;
            padding: 12px;
            background: transparent;
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
            margin-top: 12px;
            transition: all 0.2s;
        }
        .cancel-btn:hover {
            background: #f0f4f9;
        }
        [data-theme="dark"] .cancel-btn {
            color: #8ab4f8;
            border-color: #8ab4f8;
        }
        .file-upload-area {
            border: 2px dashed var(--border);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: border-color 0.2s;
        }
        .file-upload-area:hover {
            border-color: var(--primary);
        }
        .file-upload-area input[type="file"] {
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--muted);
        }
        .file-upload-area input[type="file"]::file-selector-button {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            margin-right: 10px;
        }
        [data-theme="dark"] .file-upload-area input[type="file"]::file-selector-button {
            background: #3c4043;
            color: #e8eaed;
            border-color: #5f6368;
        }
        .file-hint {
            font-size: 12px;
            color: var(--muted);
            margin: 8px 0 0;
        }
    </style>
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <div style="padding: 20px 24px 0; text-align: left;">
                <a href="<?php echo BASE_URL; ?>/home/dashboard.php" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: var(--text); font-size: 14px; font-weight: 500; transition: opacity 0.2s;" class="back-link">
                    <img src="<?php echo BASE_URL; ?>/icons/goback.png" style="width:16px; height:16px; opacity:0.8;" alt="">
                    Go back to dashboard
                </a>
            </div>

            <div class="form-container">
                <h1>Create a Class</h1>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div>✗ <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
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
                    <a href="<?php echo BASE_URL; ?>/home/dashboard.php" class="cancel-btn">Cancel</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
