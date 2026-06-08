<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die('Class not found.');
}

require_class_member($pdo, $class_id, $user['id']);
$class = get_class_info($pdo, $class_id);

// Get class members
$stmt = $pdo->prepare('SELECT u.id, u.name, u.email, cm.role FROM class_members cm JOIN users u ON cm.user_id = u.id WHERE cm.class_id = ? ORDER BY cm.role DESC, u.name ASC');
$stmt->execute([$class_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $pageTitle = 'People | Classroom Clone'; include __DIR__ . '/../includes/header.php'; ?>
    <style>
        .member {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .member-avatar {
            width: 48px;
            height: 48px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }
        .member-info {
            flex: 1;
        }
        .member-name {
            font-weight: 600;
            color: var(--text);
            margin-bottom: 4px;
        }
        .member-email {
            font-size: 13px;
            color: var(--muted);
        }
        .member-role {
            font-size: 12px;
            padding: 4px 8px;
            background: var(--surface-alt);
            border-radius: 4px;
            color: var(--primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <?php
            $classMeta = ['Total Members: ' . count($members)];
            include __DIR__ . '/../includes/class_header.php';
            $activeTab = 'people';
            include __DIR__ . '/../includes/class_tabs.php';
            ?>

            <div style="max-width: 600px;">
                <?php
                $teachers = array_filter($members, function($m) { return $m['role'] === 'teacher'; });
                $students = array_filter($members, function($m) { return $m['role'] === 'student'; });
                ?>

                <?php if (!empty($teachers)): ?>
                    <div class="section-title">&#x1F468;&#x200D;&#x1F3EB; Teachers</div>
                    <?php foreach ($teachers as $teacher): ?>
                        <div class="member">
                            <div class="member-avatar">
                                <?php echo strtoupper(substr($teacher['name'], 0, 1)); ?>
                            </div>
                            <div class="member-info">
                                <div class="member-name"><?php echo htmlspecialchars($teacher['name']); ?></div>
                                <div class="member-email"><?php echo htmlspecialchars($teacher['email']); ?></div>
                            </div>
                            <div class="member-role">Teacher</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($students)): ?>
                    <div class="section-title">&#x1F465; Students</div>
                    <?php foreach ($students as $student): ?>
                        <div class="member">
                            <div class="member-avatar">
                                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                            </div>
                            <div class="member-info">
                                <div class="member-name"><?php echo htmlspecialchars($student['name']); ?></div>
                                <div class="member-email"><?php echo htmlspecialchars($student['email']); ?></div>
                            </div>
                            <div class="member-role">Student</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
