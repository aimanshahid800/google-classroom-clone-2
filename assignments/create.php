<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die('Class not found.');
}

// Verify user is teacher in this class
$stmt = $pdo->prepare('
    SELECT cm.id FROM class_members cm
    WHERE cm.class_id = ? AND cm.user_id = ? AND cm.role = "teacher"
');
$stmt->execute([$class_id, $user['id']]);
if (!$stmt->fetch()) {
    die('Only teachers can create assignments.');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
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
            // Handle file upload
            $resource_path = null;
            if (!empty($_FILES['assignment_file']['name']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION));
                $allowed_exts = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar'];
                
                if (in_array($ext, $allowed_exts)) {
                    $upload_dir = __DIR__ . '/../uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $unique_name = 'resource_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $upload_dir . $unique_name)) {
                        $resource_path = 'uploads/' . $unique_name;
                    }
                }
            }

            $stmt = $pdo->prepare('
                INSERT INTO assignments (class_id, title, description, resource_path, topic, due_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$class_id, $title, $description ?: null, $resource_path, $topic, $due_date ?: null]);
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
    <title>Create Assignment | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .form-container {
            max-width: 600px;
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
        [data-theme="dark"] .form-container h1 {
            color: white;
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
            background: white;
            color: black;
        }
        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group textarea {
            background: #11110f;
            color: white;
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
            transition: background 0.2s;
        }
        .submit-btn:hover {
            background: #1765cc;
        }
        [data-theme="dark"] .submit-btn {
            background: #173149;
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
            transition: all 0.2s;
        }
        .cancel-btn:hover {
            background: #f0f4f9;
        }
        [data-theme="dark"] .cancel-btn {
            background: transparent;
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
        .back-link {
            color: black;
        }
        [data-theme="dark"] .back-link {
            color: white;
        }
    </style>
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <div style="max-width: 800px; padding: 20px 0; text-align: left;">
                <a href="<?php echo BASE_URL; ?>/classes/classwork.php?class_id=<?php echo $class_id; ?>" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 14px; font-weight: 500; transition: opacity 0.2s;" class="back-link">
                    <img src="<?php echo BASE_URL; ?>/icons/goback.png" style="width:16px; height:16px; opacity:0.8;" alt="">
                    Go back to classwork
                </a>
            </div>

            <div class="form-container">

                <h1>Create Assignment</h1>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div>✗ <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" placeholder="e.g., Web Design Project">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Instructions (optional)</label>
                        <textarea id="description" name="description" rows="4" placeholder="Add instructions for your students..." style="width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-family: inherit; resize: vertical;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="topic">Topic</label>
                        <input type="text" id="topic" name="topic" value="<?php echo htmlspecialchars($_POST['topic'] ?? ''); ?>" placeholder="e.g., HTML & CSS">
                    </div>
                    
                     <div class="form-group">
                         <label for="due_date">Due Date & Time</label>
                         <input type="datetime-local" id="due_date" name="due_date" value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>">
                     </div>

                    <div class="form-group">
                        <label for="assignment_file">Assignment File/Resource (optional)</label>
                        <div class="file-upload-area">
                            <input type="file" id="assignment_file" name="assignment_file" accept=".pdf,.doc,.docx,.txt,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar">
                            <p class="file-hint">Max 10MB. Allowed: PDF, DOC, DOCX, TXT, PPT, PPTX, XLS, XLSX, JPG, PNG, GIF, ZIP, RAR</p>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="submit-btn">Create Assignment</button>
                        <a href="<?php echo BASE_URL; ?>/classes/classwork.php?class_id=<?php echo $class_id; ?>" class="cancel-btn" style="text-align: center; display: flex; align-items: center; justify-content: center; text-decoration: none;">Cancel</a>
                    </div>
                </form>

            </div>
        </main>
    </div>
</body>
</html>
