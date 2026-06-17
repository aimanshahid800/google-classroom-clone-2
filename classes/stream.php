<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) die('Class not found.');

$stmt = $pdo->prepare('SELECT cm.id FROM class_members cm WHERE cm.class_id = ? AND cm.user_id = ?');
$stmt->execute([$class_id, $user['id']]);
if (!$stmt->fetch()) die('You do not have access to this class.');

$stmt = $pdo->prepare('SELECT c.*, u.name AS teacher_name FROM classes c JOIN users u ON c.owner_id = u.id WHERE c.id = ?');
$stmt->execute([$class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

$header_gradient = generateGradient($class['name']);

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

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['archive_class']) && $user['role'] === 'teacher') {
        try {
            $stmt = $pdo->prepare('UPDATE classes SET is_archived = 1 WHERE id = ? AND owner_id = ?');
            $stmt->execute([$class_id, $user['id']]);
            header('Location: ../home/dashboard.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Error archiving class: ' . $e->getMessage();
        }
    } elseif (isset($_POST['message'])) {
        $message = trim($_POST['message']);
        if (empty($message)) {
            $errors[] = 'Announcement message is required.';
        } else {
            try {
                $stmt = $pdo->prepare('INSERT INTO announcements (class_id, user_id, message) VALUES (?, ?, ?)');
                $stmt->execute([$class_id, $user['id'], $message]);
                header('Location: stream.php?class_id=' . $class_id);
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Error posting announcement: ' . $e->getMessage();
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT a.*, u.name AS author_name FROM announcements a JOIN users u ON a.user_id = u.id WHERE a.class_id = ? ORDER BY a.created_at DESC');
$stmt->execute([$class_id]);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT a.*, u.name AS teacher_name, (SELECT COUNT(*) FROM comments WHERE assignment_id = a.id) as comment_count FROM assignments a JOIN classes c ON a.class_id = c.id JOIN users u ON c.owner_id = u.id WHERE a.class_id = ? ORDER BY a.created_at DESC');
$stmt->execute([$class_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$is_student = ($user['role'] === 'student');
$stream_empty = empty($announcements) && empty($assignments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($class['name']); ?> | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .class-header {
            background: <?php echo $header_gradient; ?>;
            color: white;
            padding: 80px 24px 24px;
            border-radius: 12px;
            margin: 0 auto 24px;
            max-width: 1000px;
        }
        .class-header h1 { margin: 0; font-size: 32px; font-weight: 600; }
        .class-meta { display: flex; gap: 10px; margin-top: 10px; font-size: 14px; flex-wrap: wrap; opacity: 0.9; }

        .tabs { display: flex; gap: 0; border-bottom: 1px solid var(--border); margin-bottom: 24px; }
        .tab { padding: 10px 16px; margin-bottom: -1px; border-bottom: 2px solid transparent; cursor: pointer; color: #5f6368; text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 4px 4px 0 0; transition: color 0.2s, background 0.2s; }
        .tab:hover { color: #202124; background: #f1f3f4; }
        .tab.active { color: #1a73e8; border-bottom: 2px solid #1a73e8; background: transparent; }
        [data-theme="dark"] .tab { color: #9aa0a6; }
        [data-theme="dark"] .tab:hover { color: #e8eaed; background: #3a3a3a; }
        [data-theme="dark"] .tab.active { color: #8ab4f8; border-bottom-color: #8ab4f8; }

        .stream-container { max-width: 1000px; margin: 0 auto; }

        .new-announcement-btn { display: inline-flex; align-items: center; gap: 8px; background: #e8f0fe; color: #1a73e8; border: 1px solid #d2e3fc; border-radius: 50px; padding: 10px 24px; font-size: 14px; font-weight: 500; cursor: pointer; margin-bottom: 20px; transition: all 0.2s; font-family: inherit; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .new-announcement-btn:hover { background: #d2e3fc; box-shadow: 0 2px 4px rgba(0,0,0,0.15); transform: translateY(-1px); }
        .new-announcement-btn img { width: 18px; height: 18px; opacity: 0.85; }
        [data-theme="dark"] .new-announcement-btn { background: #173149; color: #8ab4f8; border-color: #173149; }
        [data-theme="dark"] .new-announcement-btn:hover { background: #1e3f5a; }

        .stream-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; text-align: center; }
        .stream-empty-state img { width: 300px; opacity: 0.85; margin-bottom: 24px; }
        .stream-empty-title { font-size: 18px; font-weight: 600; color: #5f6368; margin-bottom: 8px; }
        .stream-empty-sub { font-size: 14px; color: #80868b; max-width: 360px; line-height: 1.6; }
        [data-theme="dark"] .stream-empty-title { color: #9aa0a6; }
        [data-theme="dark"] .stream-empty-sub { color: #80868b; }

        .ann-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.35); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .ann-overlay.open { display: flex; }
        .ann-modal { background: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); width: 100%; max-width: 600px; margin: 16px; overflow: hidden; animation: modalSlideIn 0.2s ease-out; }
        @keyframes modalSlideIn { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: translateY(0); } }
        [data-theme="dark"] .ann-modal { background: #2a2a2a; }
        .ann-modal-header { padding: 16px 20px 12px; font-size: 16px; font-weight: 600; color: #202124; border-bottom: 1px solid #dadce0; }
        [data-theme="dark"] .ann-modal-header { color: #e0e0e0; border-color: #444; }
        .ann-modal-body { padding: 16px 20px 0; }
        .ann-textarea { width: 100%; min-height: 90px; border: none; border-bottom: 1px solid #dadce0; background: transparent; resize: none; font-family: inherit; font-size: 14px; color: #1a73e8; outline: none; padding: 0 0 8px; box-sizing: border-box; transition: border-color 0.2s; }
        .ann-textarea::placeholder { color: #1a73e8; }
        .ann-textarea:focus { border-bottom-color: #1a73e8; }
        .ann-textarea.has-text { color: #202124; }
        [data-theme="dark"] .ann-textarea { color: #8ab4f8; border-color: #444; }
        [data-theme="dark"] .ann-textarea::placeholder { color: #8ab4f8; }
        [data-theme="dark"] .ann-textarea.has-text { color: #e0e0e0; }
        .ann-toolbar { display: flex; align-items: center; gap: 4px; padding: 8px 0 6px; }
        .ann-tool-btn { background: none; border: none; cursor: pointer; padding: 6px; border-radius: 4px; color: #5f6368; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; transition: color 0.15s, background 0.15s; min-width: 28px; height: 28px; font-family: inherit; }
        .ann-tool-btn:hover { color: #202124; background: #f1f3f4; }
        [data-theme="dark"] .ann-tool-btn { color: #9aa0a6; }
        [data-theme="dark"] .ann-tool-btn:hover { color: #e0e0e0; background: #3a3a3a; }
        .ann-toolbar-divider { height: 1px; background: #1a73e8; }
        [data-theme="dark"] .ann-toolbar-divider { background: #8ab4f8; }
        .ann-modal-footer { display: flex; align-items: center; padding: 12px 20px 16px; gap: 12px; }
        .ann-attach-btns { display: flex; gap: 4px; flex: 1; }
        .ann-attach-btn { background: none; border: none; cursor: pointer; padding: 6px; border-radius: 4px; display: flex; align-items: center; justify-content: center; transition: background 0.15s; }
        .ann-attach-btn:hover { background: #f1f3f4; }
        .ann-attach-btn img { width: 20px; height: 20px; opacity: 0.65; }
        [data-theme="dark"] .ann-attach-btn:hover { background: #3a3a3a; }
        .ann-footer-actions { display: flex; align-items: center; gap: 8px; }
        .ann-cancel-btn { background: none; border: none; color: #1a73e8; font-size: 14px; font-weight: 500; cursor: pointer; padding: 8px 12px; border-radius: 4px; transition: background 0.15s; font-family: inherit; }
        .ann-cancel-btn:hover { background: rgba(26,115,232,0.08); }
        [data-theme="dark"] .ann-cancel-btn { color: #8ab4f8; }
        .ann-post-btn { background: #e0e0e0; color: #aaa; border: none; border-radius: 50px; padding: 9px 24px; font-size: 14px; font-weight: 600; cursor: not-allowed; transition: background 0.2s, color 0.2s; font-family: inherit; }
        .ann-post-btn.active { background: #1a73e8; color: #fff; cursor: pointer; }
        .ann-post-btn.active:hover { background: #1557b0; }
        [data-theme="dark"] .ann-post-btn.active { background: #8ab4f8; color: #202124; }

        .ann-card { background: #f1f3f4; border-radius: 8px; padding: 16px; margin-bottom: 12px; transition: background 0.2s; position: relative; }
        .ann-card:hover { background: #e8eaed; }
        [data-theme="dark"] .ann-card { background: #2a2a2a; }
        [data-theme="dark"] .ann-card:hover { background: #333; }
        .ann-card-header { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
        .ann-avatar-circle { width: 40px; height: 40px; border-radius: 50%; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; }
        .ann-card-meta { flex: 1; }
        .ann-card-author { font-weight: 700; font-size: 14px; color: #202124; }
        [data-theme="dark"] .ann-card-author { color: #e0e0e0; }
        .ann-card-time { font-size: 12px; color: #5f6368; margin-top: 1px; }
        [data-theme="dark"] .ann-card-time { color: #9aa0a6; }
        .ann-three-dot { background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 50%; color: #5f6368; font-size: 20px; line-height: 1; transition: background 0.15s; display: flex; align-items: center; }
        .ann-three-dot:hover { background: #e0e0e0; }
        [data-theme="dark"] .ann-three-dot { color: #9aa0a6; }
        [data-theme="dark"] .ann-three-dot:hover { background: #444; }
        .ann-card-body { font-size: 14px; color: #202124; line-height: 1.6; white-space: pre-wrap; margin-left: 52px; }
        [data-theme="dark"] .ann-card-body { color: #e0e0e0; }

        .ann-comment-pill-btn { display: inline-flex; align-items: center; gap: 6px; background: transparent; border: none; color: #1a73e8; font-size: 13px; font-weight: 500; cursor: pointer; padding: 6px 10px; border-radius: 50px; margin-top: 8px; transition: background 0.2s; font-family: inherit; }
        .ann-comment-pill-btn:hover { background: #c2e7ff; }
        [data-theme="dark"] .ann-comment-pill-btn { color: #8ab4f8; }
        [data-theme="dark"] .ann-comment-pill-btn:hover { background: #173149; }
        .ann-comment-pill-icon { width: 16px; height: 16px; opacity: 0.8; }

        .ann-inline-comment-section { margin-top: 8px; padding-top: 4px; }
        .ann-inline-comment-item { display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-start; }
        .ann-inline-comment-body { flex: 1; }
        .ann-inline-comment-author { font-weight: 600; font-size: 13px; color: #202124; margin-right: 6px; }
        .ann-inline-comment-date { font-size: 12px; color: #5f6368; }
        .ann-inline-comment-text { font-size: 13px; color: #202124; margin-top: 2px; }
        [data-theme="dark"] .ann-inline-comment-author { color: #e0e0e0; }
        [data-theme="dark"] .ann-inline-comment-date { color: #9aa0a6; }
        [data-theme="dark"] .ann-inline-comment-text { color: #e0e0e0; }

        .ann-inline-input-row { display: flex; align-items: flex-start; gap: 10px; margin-top: 8px; }
        .ann-comment-pill-wrapper { flex: 1; border: 1.5px solid #dadce0; border-radius: 24px; padding: 8px 12px; display: flex; flex-direction: column; gap: 6px; transition: border-color 0.2s, border-radius 0.2s; background: transparent; }
        .ann-comment-pill-wrapper.expanded { border-radius: 12px; border-color: #1a73e8; }
        [data-theme="dark"] .ann-comment-pill-wrapper { border-color: #555; }
        [data-theme="dark"] .ann-comment-pill-wrapper.expanded { border-color: #8ab4f8; }
        .ann-comment-pill-input { width: 100%; border: none; background: transparent; resize: none; font-family: inherit; font-size: 13px; color: #202124; outline: none; line-height: 1.5; box-sizing: border-box; }
        [data-theme="dark"] .ann-comment-pill-input { color: #e0e0e0; }
        .ann-comment-pill-toolbar { display: flex; align-items: center; gap: 2px; }
        .ann-pill-send-btn { align-self: flex-end; background: none; border: none; cursor: not-allowed; padding: 4px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .ann-pill-send-btn:not([disabled]) { cursor: pointer; }
        .ann-pill-send-btn:not([disabled]):hover { background: #c2e7ff; }
        [data-theme="dark"] .ann-pill-send-btn:not([disabled]):hover { background: #173149; }

        .upcoming-widget, .class-code-widget { width: 220px; flex-shrink: 0; background: #fff; border-radius: 8px; border: 1px solid #dadce0; padding: 16px; display: flex; flex-direction: column; }
        [data-theme="dark"] .upcoming-widget, [data-theme="dark"] .class-code-widget { background: #2a2a2a; border-color: #444; }
        .upcoming-title, .code-widget-title { font-weight: 600; font-size: 20px; color: #202124; margin-bottom: 8px; }
        [data-theme="dark"] .upcoming-title, [data-theme="dark"] .code-widget-title { color: #e0e0e0; }
        .upcoming-empty { font-size: 13px; color: #5f6368; margin-bottom: 12px; }
        [data-theme="dark"] .upcoming-empty { color: #9aa0a6; }
        .view-all-link { display: inline-block; color: #1a73e8; font-size: 13px; font-weight: 500; text-decoration: none; padding: 4px 12px; border-radius: 50px; transition: background 0.2s; align-self: flex-end; margin-top: auto; }
        .view-all-link:hover { background: #173149; }
        [data-theme="dark"] .view-all-link { color: #8ab4f8; }
        .code-display-row { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; }
        .code-text { font-family: 'Courier New', monospace; font-weight: 700; font-size: 18px; color: #1a73e8; }
        [data-theme="dark"] .code-text { color: #8ab4f8; }
        .copy-btn { background: none; border: none; cursor: pointer; padding: 4px; border-radius: 4px; transition: background 0.2s; display: flex; align-items: center; justify-content: center; }
        .copy-btn:hover { background: #e8eaed; }
        .copy-btn img { width: 20px; height: 20px; opacity: 0.7; }
        [data-theme="dark"] .copy-btn:hover { background: #4a4f52; }

        .asgn-icon { width: 24px; height: 24px; opacity: 0.7; flex-shrink: 0; }
        .asgn-info { flex: 1; }
        .asgn-title { font-size: 14px; color: #202124; }
        .asgn-date { font-size: 12px; color: #5f6368; margin-top: 2px; }
        [data-theme="dark"] .asgn-title { color: #e0e0e0; }
        [data-theme="dark"] .asgn-date { color: #9aa0a6; }

        .dot-menu { position: fixed; background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.18); width: 160px; z-index: 9999; overflow: hidden; }
        [data-theme="dark"] .dot-menu { background: #2a2a2a; }
        .dot-menu-item { padding: 11px 18px; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 10px; color: #202124; transition: background 0.15s; }
        .dot-menu-item:hover { background: #f1f3f4; }
        [data-theme="dark"] .dot-menu-item { color: #e0e0e0; }
        [data-theme="dark"] .dot-menu-item:hover { background: #3a3a3a; }
        .dot-menu-item.danger { color: #d93025; }

        .asgn-comment-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.35); z-index: 3000; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .asgn-comment-modal-overlay.open { display: flex; }
        .asgn-comment-modal { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); width: 100%; max-width: 560px; margin: 16px; display: flex; flex-direction: column; max-height: 80vh; overflow: hidden; }
        [data-theme="dark"] .asgn-comment-modal { background: #2a2a2a; }
        .asgn-comment-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; font-size: 16px; font-weight: 600; color: #202124; border-bottom: 1px solid #dadce0; }
        [data-theme="dark"] .asgn-comment-modal-header { color: #e0e0e0; border-color: #444; }
        .asgn-modal-close-btn { background: none; border: none; font-size: 18px; cursor: pointer; color: #5f6368; padding: 4px 8px; border-radius: 50%; }
        .asgn-modal-close-btn:hover { background: #f1f3f4; }
        [data-theme="dark"] .asgn-modal-close-btn { color: #9aa0a6; }
        .asgn-comment-modal-body { flex: 1; overflow-y: auto; padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; }
        .asgn-comment-modal-footer { display: flex; align-items: center; gap: 10px; padding: 12px 20px; border-top: 1px solid #dadce0; }
        [data-theme="dark"] .asgn-comment-modal-footer { border-color: #444; }
        .asgn-modal-input { flex: 1; border: 1.5px solid #dadce0; border-radius: 24px; padding: 10px 16px; font-size: 13px; font-family: inherit; background: transparent; color: #202124; outline: none; transition: border-color 0.2s; }
        .asgn-modal-input:focus { border-color: #1a73e8; }
        [data-theme="dark"] .asgn-modal-input { border-color: #555; color: #e0e0e0; }
        .asgn-modal-send-btn { background: none; border: none; cursor: not-allowed; padding: 6px; border-radius: 50%; display: flex; align-items: center; }
        .asgn-modal-send-btn:not([disabled]) { cursor: pointer; }
        .asgn-modal-send-btn:not([disabled]):hover { background: #c2e7ff; }
        [data-theme="dark"] .asgn-modal-send-btn:not([disabled]):hover { background: #173149; }

        .alert { padding: 12px; background: #ffebee; color: #c62828; border-radius: 8px; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="page-shell">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <main class="content">
            <?php include __DIR__ . '/../includes/navbar.php'; ?>

            <div class="tabs">
                <a href="stream.php?class_id=<?php echo $class_id; ?>" class="tab active">Stream</a>
                <a href="classwork.php?class_id=<?php echo $class_id; ?>" class="tab">Classwork</a>
                <a href="people.php?class_id=<?php echo $class_id; ?>" class="tab">People</a>
            </div>

            <div class="class-header">
                <h1><?php echo htmlspecialchars($class['name']); ?></h1>
                <div class="class-meta">
                    <?php if (!empty($class['section'])): ?>
                        <span>Section: <?php echo htmlspecialchars($class['section']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="stream-container">
                <?php if (!empty($errors)): ?>
                    <div class="alert">
                        <?php foreach ($errors as $error): ?>
                            <div>✗ <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="display:flex; gap:24px; align-items:flex-start;">

                    <!-- LEFT: Widgets -->
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        <div class="upcoming-widget">
                            <div class="upcoming-title">Upcoming</div>
                            <?php if (empty($assignments)): ?>
                                <div class="upcoming-empty">Woohoo, no work due soon!</div>
                            <?php else: ?>
                                <div class="upcoming-empty">You have pending work</div>
                            <?php endif; ?>
                            <a href="../todo/index.php" class="view-all-link">View all</a>
                        </div>
                        <?php if (!$is_student): ?>
                            <div class="class-code-widget">
                                <div class="code-widget-title">Class Code</div>
                                <div class="code-display-row">
                                    <span class="code-text"><?php echo htmlspecialchars($class['code']); ?></span>
                                    <button class="copy-btn" onclick="copyClassCode('<?php echo htmlspecialchars($class['code']); ?>')" title="Copy Code">
                                        <img src="<?php echo BASE_URL; ?>/icons/Copy.png" alt="Copy">
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- RIGHT: Stream -->
                    <div style="flex:1;">
                        <?php if (!$is_student): ?>
                            <button class="new-announcement-btn" onclick="openAnnModal()">
                                <img src="<?php echo BASE_URL; ?>/icons/announcement icon.png" alt="">
                                New Announcement
                            </button>
                        <?php endif; ?>

                        <?php if ($stream_empty): ?>
                            <!-- EMPTY STATE -->
                            <div class="stream-empty-state">
                                <img src="<?php echo BASE_URL; ?>/icons/noannyet.png" alt="No announcements">
                                <?php if ($is_student): ?>
                                    <div class="stream-empty-title">This is where you'll see updates for this class</div>
                                    <div class="stream-empty-sub">Use the stream to connect with your class and check for announcements</div>
                                <?php else: ?>
                                    <div class="stream-empty-title">This is where you can talk to your class</div>
                                    <div class="stream-empty-sub">Use the stream to share announcements, post assignments and respond to student questions</div>
                                <?php endif; ?>
                            </div>

                        <?php else: ?>

                            <!-- Assignment cards -->
                            <?php foreach ($assignments as $asgn): ?>
                                <div class="ann-card" style="padding:0; overflow:hidden;" data-asgn-id="<?php echo $asgn['id']; ?>">
                                    <div onclick="window.location='classwork.php?class_id=<?php echo $class_id; ?>'"
                                         style="display:flex; align-items:center; gap:12px; padding:14px 16px; cursor:pointer;">
                                        <img src="<?php echo BASE_URL; ?>/icons/Clipboard.png" class="asgn-icon" alt="">
                                        <div class="asgn-info" style="flex:1;">
                                            <div class="asgn-title"><?php echo htmlspecialchars($asgn['teacher_name']); ?> posted a new assignment: <?php echo htmlspecialchars($asgn['title']); ?></div>
                                            <div class="asgn-date"><?php echo date('d M', strtotime($asgn['created_at'])); ?></div>
                                        </div>
                                        <button class="ann-three-dot" onclick="event.stopPropagation(); toggleDotMenu(event, 'asgn', <?php echo $asgn['id']; ?>, '<?php echo $user['role']; ?>')">
                                            <img src="<?php echo BASE_URL; ?>/icons/3dots-more-icon.png" style="width:18px;height:18px;opacity:0.7;" alt="">
                                        </button>
                                    </div>
                                    <div style="border-top:1px solid #dadce0; margin:0 16px;"></div>
                                    <div style="padding:6px 16px 10px;">
                                        <button class="ann-comment-pill-btn" onclick="event.stopPropagation(); openAsgnCommentModal(<?php echo $asgn['id']; ?>)">
                                            <img src="<?php echo BASE_URL; ?>/icons/comment-icon.png" class="ann-comment-pill-icon" alt="">
                                            <?php echo $asgn['comment_count']; ?> class comment<?php echo $asgn['comment_count'] != 1 ? 's' : ''; ?>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Announcement cards -->
                            <?php foreach ($announcements as $ann):
                                $stmtC = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE announcement_id = ?');
                                $stmtC->execute([$ann['id']]);
                                $comment_count = $stmtC->fetchColumn();

                                $stmtL = $pdo->prepare('SELECT c.*, u.name AS commenter_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.announcement_id = ? ORDER BY c.created_at ASC');
                                $stmtL->execute([$ann['id']]);
                                $ann_comments = $stmtL->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                                <div class="ann-card" data-ann-id="<?php echo $ann['id']; ?>">
                                    <div class="ann-card-header">
                                        <div class="ann-avatar-circle" style="background: <?php echo avatarColor($ann['author_name']); ?>">
                                            <?php echo strtoupper(substr($ann['author_name'], 0, 1)); ?>
                                        </div>
                                        <div class="ann-card-meta">
                                            <div class="ann-card-author"><?php echo htmlspecialchars($ann['author_name']); ?></div>
                                            <div class="ann-card-time"><?php echo date('M d, Y \a\t H:i', strtotime($ann['created_at'])); ?></div>
                                        </div>
                                        <button class="ann-three-dot" onclick="toggleDotMenu(event, 'ann', <?php echo $ann['id']; ?>, '<?php echo $user['role']; ?>')">
                                            <img src="<?php echo BASE_URL; ?>/icons/3dots-more-icon.png" style="width:18px;height:18px;opacity:0.7;" alt="">
                                        </button>
                                    </div>
                                    <div class="ann-card-body"><?php echo htmlspecialchars($ann['message']); ?></div>
                                    <div style="border-top:1px solid #dadce0; margin-top:12px;"></div>
                                    <button class="ann-comment-pill-btn" onclick="toggleAnnComment(<?php echo $ann['id']; ?>)">
                                        <img src="<?php echo BASE_URL; ?>/icons/comment-icon.png" class="ann-comment-pill-icon" alt="">
                                        <span id="ann-count-<?php echo $ann['id']; ?>"><?php echo $comment_count; ?></span> class comment<?php echo $comment_count != 1 ? 's' : ''; ?>
                                    </button>
                                    <div class="ann-inline-comment-section" id="ann-inline-<?php echo $ann['id']; ?>" style="display:none;">
                                        <?php foreach ($ann_comments as $c): ?>
                                            <div class="ann-inline-comment-item">
                                                <div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;background:<?php echo avatarColor($c['commenter_name']); ?>">
                                                    <?php echo strtoupper(substr($c['commenter_name'], 0, 1)); ?>
                                                </div>
                                                <div class="ann-inline-comment-body">
                                                    <span class="ann-inline-comment-author"><?php echo htmlspecialchars($c['commenter_name']); ?></span>
                                                    <span class="ann-inline-comment-date"><?php echo date('d M', strtotime($c['created_at'])); ?></span>
                                                    <div class="ann-inline-comment-text"><?php echo htmlspecialchars($c['message']); ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="ann-inline-input-row">
                                            <div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;background:<?php echo avatarColor($user['name']); ?>">
                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                            </div>
                                            <div class="ann-comment-pill-wrapper" id="ann-pill-<?php echo $ann['id']; ?>">
                                                <textarea class="ann-comment-pill-input" id="ann-ctextarea-<?php echo $ann['id']; ?>" placeholder="Add class comment..." rows="1" onclick="expandAnnComment(<?php echo $ann['id']; ?>)"></textarea>
                                                <div class="ann-comment-pill-toolbar" id="ann-toolbar-<?php echo $ann['id']; ?>" style="display:none;">
                                                    <button type="button" class="ann-tool-btn" onclick="applyAnnFmt(<?php echo $ann['id']; ?>,'bold')"><b>B</b></button>
                                                    <button type="button" class="ann-tool-btn" onclick="applyAnnFmt(<?php echo $ann['id']; ?>,'italic')"><i>I</i></button>
                                                    <button type="button" class="ann-tool-btn" onclick="applyAnnFmt(<?php echo $ann['id']; ?>,'underline')"><u>U</u></button>
                                                    <button type="button" class="ann-tool-btn" onclick="applyAnnFmt(<?php echo $ann['id']; ?>,'list')">☰</button>
                                                    <button type="button" class="ann-tool-btn" onclick="applyAnnFmt(<?php echo $ann['id']; ?>,'clear')">✕</button>
                                                </div>
                                                <button class="ann-pill-send-btn" id="ann-csend-<?php echo $ann['id']; ?>" onclick="submitAnnComment(<?php echo $ann['id']; ?>)" disabled>
                                                    <img src="<?php echo BASE_URL; ?>/icons/send-icon.png" alt="Send" style="width:18px;height:18px;opacity:0.4;" id="ann-send-img-<?php echo $ann['id']; ?>">
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- New Announcement Modal -->
    <div class="ann-overlay" id="annOverlay" onclick="handleOverlayClick(event)">
        <div class="ann-modal" id="annModal">
            <div class="ann-modal-header">Post</div>
            <form method="POST" id="annForm">
                <div class="ann-modal-body">
                    <textarea class="ann-textarea" name="message" id="annTextarea" placeholder="Announce something to your class" required></textarea>
                    <div class="ann-toolbar">
                        <button type="button" class="ann-tool-btn" onclick="applyFmt('bold')"><b>B</b></button>
                        <button type="button" class="ann-tool-btn" onclick="applyFmt('italic')"><i>I</i></button>
                        <button type="button" class="ann-tool-btn" onclick="applyFmt('underline')"><u>U</u></button>
                        <button type="button" class="ann-tool-btn" onclick="applyFmt('list')">☰</button>
                        <button type="button" class="ann-tool-btn" onclick="applyFmt('clear')">✕</button>
                    </div>
                    <div class="ann-toolbar-divider"></div>
                </div>
                <div class="ann-modal-footer">
                    <div class="ann-attach-btns">
                        <button type="button" class="ann-attach-btn"><img src="<?php echo BASE_URL; ?>/icons/folder icon.svg" alt="Folder"></button>
                        <button type="button" class="ann-attach-btn"><img src="<?php echo BASE_URL; ?>/icons/Clipboard.png" alt="File"></button>
                        <button type="button" class="ann-attach-btn"><img src="<?php echo BASE_URL; ?>/icons/googles-apps-icon.svg" alt="Drive"></button>
                    </div>
                    <div class="ann-footer-actions">
                        <button type="button" class="ann-cancel-btn" onclick="closeAnnModal()">Cancel</button>
                        <button type="submit" class="ann-post-btn" id="annPostBtn" disabled>Post</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assignment Comment Modal -->
    <div class="asgn-comment-modal-overlay" id="asgnCommentOverlay" onclick="handleAsgnModalOverlay(event)">
        <div class="asgn-comment-modal">
            <div class="asgn-comment-modal-header">
                <span>Class comments</span>
                <button class="asgn-modal-close-btn" onclick="closeAsgnCommentModal()">✕</button>
            </div>
            <div class="asgn-comment-modal-body" id="asgnModalBody"></div>
            <div class="asgn-comment-modal-footer">
                <div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;background:<?php echo avatarColor($user['name']); ?>">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <input type="text" class="asgn-modal-input" id="asgnModalInput" placeholder="Add class comment...">
                <button class="asgn-modal-send-btn" id="asgnModalSend" onclick="submitAsgnComment()" disabled>
                    <img src="<?php echo BASE_URL; ?>/icons/send-icon.png" alt="Send" style="width:18px;height:18px;opacity:0.4;" id="asgnModalSendImg">
                </button>
            </div>
        </div>
    </div>

    <script>
    function openAnnModal() { document.getElementById('annOverlay').classList.add('open'); setTimeout(() => document.getElementById('annTextarea').focus(), 80); }
    function closeAnnModal() {
        document.getElementById('annOverlay').classList.remove('open');
        const ta = document.getElementById('annTextarea');
        ta.value = ''; ta.classList.remove('has-text');
        const btn = document.getElementById('annPostBtn');
        btn.disabled = true; btn.classList.remove('active');
    }
    function handleOverlayClick(e) { if (e.target === document.getElementById('annOverlay')) closeAnnModal(); }

    document.getElementById('annTextarea').addEventListener('input', function() {
        const btn = document.getElementById('annPostBtn'), hasVal = this.value.trim().length > 0;
        btn.disabled = !hasVal; btn.classList.toggle('active', hasVal); this.classList.toggle('has-text', hasVal);
    });

    function applyFmt(cmd) {
        const ta = document.getElementById('annTextarea'), start = ta.selectionStart, end = ta.selectionEnd, sel = ta.value.substring(start, end);
        let wrap = '';
        if (cmd==='bold') wrap='**'; else if (cmd==='italic') wrap='_'; else if (cmd==='underline') wrap='__';
        else if (cmd==='list') { ta.value=ta.value.substring(0,start)+'\n• '+sel+ta.value.substring(end); ta.focus(); ta.dispatchEvent(new Event('input')); return; }
        else if (cmd==='clear') { ta.value=ta.value.replace(/[*_~`]/g,''); ta.focus(); ta.dispatchEvent(new Event('input')); return; }
        if (wrap) { ta.value=ta.value.substring(0,start)+wrap+sel+wrap+ta.value.substring(end); ta.selectionStart=start+wrap.length; ta.selectionEnd=end+wrap.length; }
        ta.focus(); ta.dispatchEvent(new Event('input'));
    }

    function copyClassCode(code) { navigator.clipboard.writeText(code).then(() => alert('Class code copied!')).catch(() => {}); }

    function toggleAnnComment(id) {
        const s = document.getElementById('ann-inline-'+id), h = s.style.display === 'none';
        s.style.display = h ? 'block' : 'none';
        if (h) setTimeout(() => document.getElementById('ann-ctextarea-'+id).focus(), 80);
    }

    function expandAnnComment(id) {
        document.getElementById('ann-pill-'+id).classList.add('expanded');
        document.getElementById('ann-toolbar-'+id).style.display = 'flex';
    }

    function applyAnnFmt(id, cmd) {
        const ta = document.getElementById('ann-ctextarea-'+id), start = ta.selectionStart, end = ta.selectionEnd, sel = ta.value.substring(start, end);
        let wrap = '';
        if (cmd==='bold') wrap='**'; else if (cmd==='italic') wrap='_'; else if (cmd==='underline') wrap='__';
        else if (cmd==='list') { ta.value=ta.value.substring(0,start)+'\n• '+sel+ta.value.substring(end); ta.dispatchEvent(new Event('input')); return; }
        else if (cmd==='clear') { ta.value=ta.value.replace(/[*_~`]/g,''); ta.dispatchEvent(new Event('input')); return; }
        if (wrap) ta.value=ta.value.substring(0,start)+wrap+sel+wrap+ta.value.substring(end);
        ta.focus(); ta.dispatchEvent(new Event('input'));
    }

    function submitAnnComment(annId) {
        const ta = document.getElementById('ann-ctextarea-'+annId), msg = ta.value.trim();
        if (!msg) return;
        fetch('add_comment.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'entity_id='+annId+'&entity_type=announcement&message='+encodeURIComponent(msg)})
        .then(r => r.json()).then(data => {
            if (data.success) {
                ta.value = '';
                const btn = document.getElementById('ann-csend-'+annId), img = document.getElementById('ann-send-img-'+annId);
                btn.disabled = true; if (img) img.style.opacity = '0.4';
                const section = document.getElementById('ann-inline-'+annId), inputRow = section.querySelector('.ann-inline-input-row');
                const div = document.createElement('div'); div.className = 'ann-inline-comment-item';
                div.innerHTML = `<div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;background:<?php echo avatarColor($user['name']); ?>"><?php echo strtoupper(substr($user['name'],0,1)); ?></div><div class="ann-inline-comment-body"><span class="ann-inline-comment-author"><?php echo htmlspecialchars($user['name']); ?></span><span class="ann-inline-comment-date">Just now</span><div class="ann-inline-comment-text">${msg}</div></div>`;
                section.insertBefore(div, inputRow);
                const countEl = document.getElementById('ann-count-'+annId);
                if (countEl) countEl.textContent = parseInt(countEl.textContent||0) + 1;
            }
        });
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('ann-comment-pill-input')) {
            const id = e.target.id.replace('ann-ctextarea-',''), btn = document.getElementById('ann-csend-'+id), img = document.getElementById('ann-send-img-'+id), hasVal = e.target.value.trim().length > 0;
            btn.disabled = !hasVal; if (img) img.style.opacity = hasVal ? '1' : '0.4';
        }
        if (e.target.id === 'asgnModalInput') {
            const hasVal = e.target.value.trim().length > 0;
            document.getElementById('asgnModalSend').disabled = !hasVal;
            document.getElementById('asgnModalSendImg').style.opacity = hasVal ? '1' : '0.4';
        }
    });

    document.addEventListener('click', function(e) {
        document.querySelectorAll('.ann-comment-pill-wrapper').forEach(wrapper => {
            const id = wrapper.id.replace('ann-pill-',''), ta = document.getElementById('ann-ctextarea-'+id);
            if (!wrapper.contains(e.target) && ta && !ta.value.trim()) {
                wrapper.classList.remove('expanded');
                const toolbar = document.getElementById('ann-toolbar-'+id);
                if (toolbar) toolbar.style.display = 'none';
            }
        });
    });

    let currentAsgnId = null;
    function openAsgnCommentModal(asgnId) {
        currentAsgnId = asgnId;
        document.getElementById('asgnCommentOverlay').classList.add('open');
        document.getElementById('asgnModalBody').innerHTML = '<p style="color:#5f6368;font-size:13px;">Loading...</p>';
        document.getElementById('asgnModalInput').value = '';
        document.getElementById('asgnModalSend').disabled = true;
        document.getElementById('asgnModalSendImg').style.opacity = '0.4';
        fetch('get_comments.php?entity_id='+asgnId+'&entity_type=assignment').then(r => r.json()).then(data => {
            const body = document.getElementById('asgnModalBody');
            if (!data.comments || data.comments.length === 0) {
                body.innerHTML = '<p style="color:#5f6368;font-size:13px;">No comments yet.</p>';
            } else {
                body.innerHTML = '';
                data.comments.forEach(c => {
                    const div = document.createElement('div'); div.className = 'ann-inline-comment-item';
                    div.innerHTML = `<div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;background:#1e88e5">${c.name.charAt(0).toUpperCase()}</div><div class="ann-inline-comment-body"><span class="ann-inline-comment-author">${c.name}</span><span class="ann-inline-comment-date">${c.created_at}</span><div class="ann-inline-comment-text">${c.message}</div></div>`;
                    body.appendChild(div);
                });
            }
            setTimeout(() => document.getElementById('asgnModalInput').focus(), 80);
        });
    }

    function submitAsgnComment() {
        const input = document.getElementById('asgnModalInput'), msg = input.value.trim();
        if (!msg || !currentAsgnId) return;
        fetch('add_comment.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'entity_id='+currentAsgnId+'&entity_type=assignment&message='+encodeURIComponent(msg)})
        .then(r => r.json()).then(data => {
            if (data.success) {
                input.value = ''; document.getElementById('asgnModalSend').disabled = true; document.getElementById('asgnModalSendImg').style.opacity = '0.4';
                const body = document.getElementById('asgnModalBody'), noMsg = body.querySelector('p'); if (noMsg) noMsg.remove();
                const div = document.createElement('div'); div.className = 'ann-inline-comment-item';
                div.innerHTML = `<div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;background:<?php echo avatarColor($user['name']); ?>"><?php echo strtoupper(substr($user['name'],0,1)); ?></div><div class="ann-inline-comment-body"><span class="ann-inline-comment-author"><?php echo htmlspecialchars($user['name']); ?></span><span class="ann-inline-comment-date">Just now</span><div class="ann-inline-comment-text">${msg}</div></div>`;
                body.appendChild(div);
            }
        });
    }

    function closeAsgnCommentModal() { document.getElementById('asgnCommentOverlay').classList.remove('open'); currentAsgnId = null; }
    function handleAsgnModalOverlay(e) { if (e.target === document.getElementById('asgnCommentOverlay')) closeAsgnCommentModal(); }

    let activeDotMenu = null;
    function toggleDotMenu(e, type, id, role) {
        e.stopPropagation(); e.preventDefault();
        if (activeDotMenu) { activeDotMenu.remove(); activeDotMenu = null; return; }
        const btn = e.currentTarget, rect = btn.getBoundingClientRect(), menu = document.createElement('div');
        menu.className = 'dot-menu'; menu.style.top = (rect.bottom+4)+'px'; menu.style.left = (rect.left-130)+'px';
        if (type === 'ann') {
            menu.innerHTML = role==='teacher' ? `<div class="dot-menu-item" onclick="deleteAnn(${id})">Delete</div><div class="dot-menu-item danger" onclick="reportAbuse('ann',${id})">Report abuse</div>` : `<div class="dot-menu-item danger" onclick="reportAbuse('ann',${id})">Report abuse</div>`;
        } else {
            menu.innerHTML = role==='teacher' ? `<div class="dot-menu-item" onclick="deleteAsgn(${id})">Delete</div><div class="dot-menu-item danger" onclick="reportAbuse('asgn',${id})">Report abuse</div>` : `<div class="dot-menu-item danger" onclick="reportAbuse('asgn',${id})">Report abuse</div>`;
        }
        document.body.appendChild(menu); activeDotMenu = menu;
    }

    function deleteAnn(id) {
        fetch('delete_announcement.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'announcement_id='+id})
        .then(r => r.json()).then(data => {
            if (data.success) { if (activeDotMenu) { activeDotMenu.remove(); activeDotMenu = null; } const card = document.querySelector(`[data-ann-id="${id}"]`); if (card) card.remove(); }
            else alert('Could not delete.');
        });
    }

    function deleteAsgn(id) {
        fetch('../assignments/delete_assignment.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'assignment_id='+id})
        .then(r => r.json()).then(data => {
            if (data.success) { if (activeDotMenu) { activeDotMenu.remove(); activeDotMenu = null; } const card = document.querySelector(`[data-asgn-id="${id}"]`); if (card) card.remove(); }
            else alert('Could not delete.');
        });
    }

    function reportAbuse(type, id) { alert('Report submitted.'); if (activeDotMenu) { activeDotMenu.remove(); activeDotMenu = null; } }
    document.addEventListener('click', function() { if (activeDotMenu) { activeDotMenu.remove(); activeDotMenu = null; } });
    </script>
</body>
</html>