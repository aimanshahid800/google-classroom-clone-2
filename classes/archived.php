<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();

// Fetch all archived classes for the current user
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.section, c.subject, c.code, c.owner_id, u.name AS teacher_name
    FROM classes c
    JOIN class_members cm ON c.id = cm.class_id
    JOIN users u ON c.owner_id = u.id
    WHERE cm.user_id = ? AND c.is_archived = 1
    ORDER BY c.created_at DESC
');
$stmt->execute([$user['id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Unarchive
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unarchive_class'])) {
    $class_id = $_POST['class_id'];
    try {
        $stmt = $pdo->prepare('UPDATE classes SET is_archived = 0 WHERE id = ? AND owner_id = ?');
        $stmt->execute([$class_id, $user['id']]);
        header('Location: archived.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Error unarchiving class: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Classes | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 40px auto;
        }
        .archived-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .archived-header h1 {
            margin: 0;
            font-size: 32px;
        }
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        .class-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            opacity: 0.8;
        }
        .class-banner {
            height: 120px;
            background: #cfd8dc;
            display: flex;
            align-items: flex-end;
            padding: 20px;
            color: #546e7a;
        }
        .class-banner h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .class-info {
            padding: 20px;
        }
        .class-teacher {
            font-size: 14px;
            color: var(--text);
            margin-bottom: 12px;
        }
        .unarchive-btn {
            width: 100%;
            padding: 8px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }
        .empty-state-icon {
            font-size: 64px;
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
                <div class="archived-header">
                    <h1>Archived Classes</h1>
                    <a href="../home/dashboard.php" class="action-btn" style="text-decoration: none; font-size: 14px;">Back to Dashboard</a>
                </div>

                <?php if (!empty($classes)): ?>
                    <div class="classes-grid">
                        <?php foreach ($classes as $class): ?>
                            <div class="class-card">
                                <div class="class-banner">
                                    <h3><?php echo htmlspecialchars($class['name']); ?></h3>
                                </div>
                                <div class="class-info">
                                    <div class="class-teacher">👨‍🏫 <?php echo htmlspecialchars($class['teacher_name']); ?></div>
                                    <?php if ($class['owner_id'] === $user['id']): ?>
                                        <form method="POST">
                                            <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                            <button type="submit" name="unarchive_class" class="unarchive-btn">Unarchive</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h2>No archived classes</h2>
                        <p>Classes you archive will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
