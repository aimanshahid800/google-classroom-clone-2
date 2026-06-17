<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die('Class not found.');
}

// Verify user is a member of this class
$stmt = $pdo->prepare('SELECT id FROM class_members WHERE class_id = ? AND user_id = ?');
$stmt->execute([$class_id, $user['id']]);
if (!$stmt->fetch()) {
    die('You do not have access to this class.');
}

// Get class info
$stmt = $pdo->prepare('SELECT c.*, u.name AS teacher_name FROM classes c JOIN users u ON c.owner_id = u.id WHERE c.id = ?');
$stmt->execute([$class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

// Get class members
$stmt = $pdo->prepare('SELECT u.id, u.name, u.email, cm.role FROM class_members cm JOIN users u ON cm.user_id = u.id WHERE cm.class_id = ? ORDER BY cm.role DESC, u.name ASC');
$stmt->execute([$class_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

function avatarColor($name) {
    $colors = [
        '#e53935', '#d81b60', '#8e24aa', '#5e35b1',
        '#1e88e5', '#00897b', '#43a047', '#f4511e',
        '#6d4c41', '#00acc1', '#3949ab', '#039be5'
    ];
    $hash = 0;
    for ($i = 0; $i < strlen($name); $i++) {
        $hash = ord($name[$i]) + (($hash << 5) - $hash);
    }
    return $colors[abs($hash) % count($colors)];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>People | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .tabs {
            display: flex;
            gap: 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .tab {
            padding: 12px 0;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            color: var(--muted);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .tab:hover { color: var(--text); }
        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .section-title {
            font-weight: 600;
            font-size: 32px;
            color: var(--text);
            margin-top: 28px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--border);
        }
        .member {
            background: transparent;
            padding: 8px 0;
            border-radius: 0;
            margin-bottom: 12px;
            box-shadow: none;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .member-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
            flex-shrink: 0;
        }
        .member-info { flex: 1; }
        .member-name {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }
        .member-email {
            font-size: 13px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <div class="tabs">
                <a href="stream.php?class_id=<?php echo $class_id; ?>" class="tab">Stream</a>
                <a href="classwork.php?class_id=<?php echo $class_id; ?>" class="tab">Classwork</a>
                <a href="people.php?class_id=<?php echo $class_id; ?>" class="tab active">People</a>
            </div>

            <div style="max-width: 600px; margin: 0 auto;">
                <?php
                $teachers = array_filter($members, function($m) { return $m['role'] === 'teacher'; });
                $students = array_filter($members, function($m) { return $m['role'] === 'student'; });
                ?>

                <?php if (!empty($teachers)): ?>
                    <div class="section-title">
                        <img src="<?php echo BASE_URL; ?>/icons/teacher.png" style="width:18px; height:18px; vertical-align:middle; margin-right:8px;"> Teachers
                    </div>
                    <?php foreach ($teachers as $teacher): ?>
                        <div class="member">
                            <div class="member-avatar" style="background: <?php echo avatarColor($teacher['name']); ?>">
                                <?php echo strtoupper(substr($teacher['name'], 0, 1)); ?>
                            </div>
                            <div class="member-info">
                                <div class="member-name"><?php echo htmlspecialchars($teacher['name']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($students)): ?>
                    <div class="section-title">
                        <img src="<?php echo BASE_URL; ?>/icons/student.png" style="width:18px; height:18px; vertical-align:middle; margin-right:8px;"> Students
                    </div>
                    <?php foreach ($students as $student): ?>
                        <div class="member">
                            <div class="member-avatar" style="background: <?php echo avatarColor($student['name']); ?>">
                                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                            </div>
                            <div class="member-info">
                                <div class="member-name"><?php echo htmlspecialchars($student['name']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
