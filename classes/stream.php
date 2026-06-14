<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
$class_id = $_GET['class_id'] ?? null;

if (!$class_id) {
    die('Class not found.');
}

// Verify user is a member of this class
$stmt = $pdo->prepare('
    SELECT cm.id FROM class_members cm
    WHERE cm.class_id = ? AND cm.user_id = ?
');
$stmt->execute([$class_id, $user['id']]);
if (!$stmt->fetch()) {
    die('You do not have access to this class.');
}

// Get class info
$stmt = $pdo->prepare('
    SELECT c.*, u.name AS teacher_name
    FROM classes c
    JOIN users u ON c.owner_id = u.id
    WHERE c.id = ?
');
$stmt->execute([$class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle new announcement or archive class
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
                $stmt = $pdo->prepare('
                    INSERT INTO announcements (class_id, user_id, message)
                    VALUES (?, ?, ?)
                ');
                $stmt->execute([$class_id, $user['id'], $message]);
                header('Location: stream.php?class_id=' . $class_id);
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Error posting announcement: ' . $e->getMessage();
            }
        }
    }
}

// Get announcements
$stmt = $pdo->prepare('
    SELECT a.*, u.name AS author_name
    FROM announcements a
    JOIN users u ON a.user_id = u.id
    WHERE a.class_id = ?
    ORDER BY a.created_at DESC
');
$stmt->execute([$class_id]);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get assignments for the stream and upcoming widget
$stmt = $pdo->prepare('
    SELECT a.*, u.name AS teacher_name, 
    (SELECT COUNT(*) FROM comments WHERE assignment_id = a.id) as comment_count
    FROM assignments a
    JOIN classes c ON a.class_id = c.id
    JOIN users u ON c.owner_id = u.id
    WHERE a.class_id = ?
    ORDER BY a.created_at DESC
');
$stmt->execute([$class_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$is_student = ($user['role'] === 'student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($class['name']); ?> | Classroom Clone</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* ========== CLASS HEADER ========== */
        .class-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 24px;
            border-radius: 12px;
            margin: 0 auto 24px;
            max-width: 1000px;
        }

        .class-header h1 { margin: 0; font-size: 32px; }
        .class-meta {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            font-size: 14px;
            flex-wrap: wrap;
        }

        /* ========== TABS ========== */
        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }
        .tab {
            padding: 10px 16px;
            margin-bottom: -1px;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            color: #5f6368;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 4px 4px 0 0;
            transition: color 0.2s, background 0.2s;
        }
        .tab:hover { color: #202124; background: #f1f3f4; }
        .tab.active { color: #1a73e8; border-bottom: 2px solid #1a73e8; background: transparent; }
        [data-theme="dark"] .tab { color: #9aa0a6; }
        [data-theme="dark"] .tab:hover { color: #e8eaed; background: #3a3a3a; }
        [data-theme="dark"] .tab.active { color: #8ab4f8; border-bottom-color: #8ab4f8; }

        /* ========== STREAM CONTAINER ========== */
        .stream-container { 
            max-width: 1000px; 
            margin: 0 auto; 
        }



        /* ========== NEW ANNOUNCEMENT BUTTON ========== */
        .new-announcement-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #e8f0fe;
            color: #1a73e8;
            border: 1px solid #d2e3fc;
            border-radius: 50px;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 20px;
            transition: all 0.2s;
            font-family: inherit;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .new-announcement-btn:hover { 
            background: #d2e3fc; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            transform: translateY(-1px);
        }
        .new-announcement-btn:active { 
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            transform: translateY(0);
        }
        .new-announcement-btn img { width: 18px; height: 18px; opacity: 0.85; }
        [data-theme="dark"] .new-announcement-btn { background: #3c4043; color: #8ab4f8; border-color: #5f6368; }
        [data-theme="dark"] .new-announcement-btn:hover { background: #4a4f52; }

        /* ========== ANNOUNCEMENT MODAL ========== */
        .ann-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.35);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }
        .ann-overlay.open { display: flex; }

        .ann-modal {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 600px;
            margin: 16px;
            overflow: hidden;
            animation: modalSlideIn 0.2s ease-out;
        }
        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        [data-theme="dark"] .ann-modal { background: #2a2a2a; }

        .ann-modal-header {
            padding: 16px 20px 12px;
            font-size: 16px;
            font-weight: 600;
            color: #202124;
            border-bottom: 1px solid #dadce0;
        }
        [data-theme="dark"] .ann-modal-header { color: #e0e0e0; border-color: #444; }

        .ann-modal-body { padding: 16px 20px 0; }

        .ann-textarea {
            width: 100%;
            min-height: 90px;
            border: none;
            border-bottom: 1px solid #dadce0;
            background: transparent;
            resize: none;
            font-family: inherit;
            font-size: 14px;
            color: #1a73e8;
            outline: none;
            padding: 0 0 8px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .ann-textarea::placeholder { color: #1a73e8; }
        .ann-textarea:focus { border-bottom-color: #1a73e8; }
        .ann-textarea.has-text { color: #202124; }
        [data-theme="dark"] .ann-textarea { color: #8ab4f8; border-color: #444; }
        [data-theme="dark"] .ann-textarea::placeholder { color: #8ab4f8; }
        [data-theme="dark"] .ann-textarea.has-text { color: #e0e0e0; }
        [data-theme="dark"] .ann-textarea:focus { border-bottom-color: #8ab4f8; }

        .ann-toolbar {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 8px 0 6px;
        }
        .ann-tool-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            border-radius: 4px;
            color: #5f6368;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s, background 0.15s;
            min-width: 28px;
            height: 28px;
            font-family: inherit;
        }
        .ann-tool-btn:hover { color: #202124; background: #f1f3f4; }
        [data-theme="dark"] .ann-tool-btn { color: #9aa0a6; }
        [data-theme="dark"] .ann-tool-btn:hover { color: #e0e0e0; background: #3a3a3a; }

        .ann-toolbar-divider { height: 1px; background: #1a73e8; }
        [data-theme="dark"] .ann-toolbar-divider { background: #8ab4f8; }

        .ann-modal-footer {
            display: flex;
            align-items: center;
            padding: 12px 20px 16px;
            gap: 12px;
        }
        .ann-attach-btns { display: flex; gap: 4px; flex: 1; }
        .ann-attach-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s;
        }
        .ann-attach-btn:hover { background: #f1f3f4; }
        .ann-attach-btn img { width: 20px; height: 20px; opacity: 0.65; }
        [data-theme="dark"] .ann-attach-btn:hover { background: #3a3a3a; }

        .ann-footer-actions { display: flex; align-items: center; gap: 8px; }

        .ann-cancel-btn {
            background: none;
            border: none;
            color: #1a73e8;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background 0.15s;
            font-family: inherit;
        }
        .ann-cancel-btn:hover { background: rgba(26,115,232,0.08); }
        [data-theme="dark"] .ann-cancel-btn { color: #8ab4f8; }

        .ann-post-btn {
            background: #e0e0e0;
            color: #aaa;
            border: none;
            border-radius: 50px;
            padding: 9px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: not-allowed;
            transition: background 0.2s, color 0.2s;
            font-family: inherit;
        }
        .ann-post-btn.active { background: #1a73e8; color: #fff; cursor: pointer; }
        .ann-post-btn.active:hover { background: #1557b0; }
        [data-theme="dark"] .ann-post-btn.active { background: #8ab4f8; color: #202124; }

        /* ========== ANNOUNCEMENT CARDS ========== */
        .ann-card {
            background: #f1f3f4;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            transition: background 0.2s;
            position: relative;
        }
        .ann-card:hover { background: #e8eaed; }
        [data-theme="dark"] .ann-card { background: #2a2a2a; }
        [data-theme="dark"] .ann-card:hover { background: #333; }

        .ann-card-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }
        .ann-avatar-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1a73e8;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            flex-shrink: 0;
        }
        .ann-card-meta { flex: 1; }
        .ann-card-author { font-weight: 700; font-size: 14px; color: #202124; }
        [data-theme="dark"] .ann-card-author { color: #e0e0e0; }
        .ann-card-time { font-size: 12px; color: #5f6368; margin-top: 1px; }
        [data-theme="dark"] .ann-card-time { color: #9aa0a6; }

        .ann-three-dot {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 50%;
            color: #5f6368;
            font-size: 20px;
            line-height: 1;
            transition: background 0.15s;
            display: flex;
            align-items: center;
        }
        .ann-three-dot:hover { background: #e0e0e0; }
        [data-theme="dark"] .ann-three-dot { color: #9aa0a6; }
        [data-theme="dark"] .ann-three-dot:hover { background: #444; }

        .ann-card-body {
            font-size: 14px;
            color: #202124;
            line-height: 1.6;
            white-space: pre-wrap;
            margin-left: 52px;
        }
        [data-theme="dark"] .ann-card-body { color: #e0e0e0; }

        .ann-add-comment {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            margin-left: 52px;
            color: #1a73e8;
            font-size: 14px;
            cursor: pointer;
            border-top: 1px solid #dadce0;
            padding-top: 10px;
            transition: color 0.15s;
            text-decoration: none;
        }
        .ann-add-comment:hover { color: #1557b0; }
        [data-theme="dark"] .ann-add-comment { color: #8ab4f8; border-color: #444; }
        .ann-add-comment img { width: 18px; height: 18px; opacity: 0.75; }

        /* ========== VIEW ALL LINK ========== */
        .view-all-link {
            display: inline-block;
            color: #1a73e8;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            padding: 4px 12px;
            border-radius: 50px;
            transition: background 0.2s;
            align-self: flex-end;
            margin-top: auto;
        }
        .view-all-link:hover { background: #e8f0fe; }
        [data-theme="dark"] .view-all-link { color: #8ab4f8; }
        [data-theme="dark"] .view-all-link:hover { background: rgba(138,180,248,0.1); }

        /* ========== UPCOMING WIDGET ========== */
        .upcoming-widget, .class-code-widget {
            width: 220px;
            flex-shrink: 0;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #dadce0;
            padding: 16px;
            display: flex;
            flex-direction: column;
        }
        .upcoming-title, .code-widget-title { font-weight: 600; font-size: 20px; color: #202124; margin-bottom: 8px; }
        .upcoming-empty { font-size: 13px; color: #5f6368; margin-bottom: 12px; }
        [data-theme="dark"] .upcoming-widget, [data-theme="dark"] .class-code-widget { background: #2a2a2a; border-color: #444; }
        [data-theme="dark"] .upcoming-title, [data-theme="dark"] .code-widget-title { color: #e0e0e0; }
        [data-theme="dark"] .upcoming-empty { color: #9aa0a6; }

        .code-display-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
        }
        .code-text {
            font-family: 'Courier New', Courier, monospace;
            font-weight: 700;
            font-size: 18px;
            color: #1a73e8;
        }
        .copy-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .copy-btn:hover { background: #e8eaed; }
        .copy-btn img { width: 20px; height: 20px; opacity: 0.7; }
        [data-theme="dark"] .code-text { color: #8ab4f8; }
        [data-theme="dark"] .copy-btn:hover { background: #4a4f52; }

        /* ========== ASSIGNMENT STREAM CARDS ========== */
        .assignment-stream-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f1f3f4;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 12px;
            text-decoration: none;
            color: inherit;
            transition: background 0.2s;
            cursor: pointer;
        }
        .assignment-stream-card:hover { background: #e8eaed; }
        [data-theme="dark"] .assignment-stream-card { background: #2a2a2a; }
        [data-theme="dark"] .assignment-stream-card:hover { background: #333; }
        .asgn-icon { width: 24px; height: 24px; opacity: 0.7; flex-shrink: 0; }
        .asgn-info { flex: 1; }
        .asgn-title { font-size: 14px; color: #202124; }
        .asgn-date { font-size: 12px; color: #5f6368; margin-top: 2px; }
        [data-theme="dark"] .asgn-title { color: #e0e0e0; }
        [data-theme="dark"] .asgn-date { color: #9aa0a6; }

        /* ========== COMMENT SECTION ========== */
        .comment-textarea {
          width: 100%;
          border: none;
          border-bottom: 2px solid #dadce0;
          background: transparent;
          resize: none;
          font-family: inherit;
          font-size: 13px;
          outline: none;
          padding: 4px 0;
          color: #202124;
          box-sizing: border-box;
          transition: border-color 0.2s;
        }
        .comment-textarea:focus { border-bottom-color: #1a73e8; }
        [data-theme="dark"] .comment-textarea { color: #e0e0e0; border-color: #444; }
        [data-theme="dark"] .comment-textarea:focus { border-bottom-color: #8ab4f8; }
        .comment-toolbar {
          display: flex;
          align-items: center;
          gap: 4px;
          padding-top: 6px;
        }
        .comment-send-btn {
          margin-left: auto;
          background: none;
          border: none;
          color: #dadce0;
          font-size: 16px;
          cursor: not-allowed;
          transition: color 0.2s;
        }
        .comment-send-btn:not([disabled]) { color: #1a73e8; cursor: pointer; }
        [data-theme="dark"] .comment-send-btn:not([disabled]) { color: #8ab4f8; }

        /* ========== EXISTING COMMENTS DISPLAY ========== */
        .comments-list {
          margin-left: 52px;
          margin-top: 12px;
          display: flex;
          flex-direction: column;
          gap: 12px;
        }
        .comment-item {
          display: flex;
          gap: 8px;
          align-items: flex-start;
        }
        .comment-item-avatar {
          width: 32px;
          height: 32px;
          border-radius: 50%;
          background: #1a73e8;
          color: white;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 12px;
          font-weight: 600;
          flex-shrink: 0;
        }
        .comment-item-content {
          background: #f1f3f4;
          padding: 8px 12px;
          border-radius: 18px;
          font-size: 13px;
          color: #202124;
          max-width: 80%;
        }
        [data-theme="dark"] .comment-item-content { background: #3c4043; color: #e0e0e0; }

        /* ========== MISC ========== */
        .empty-message {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }
        .alert {
            padding: 12px;
            background: #ffebee;
            color: #c62828;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        /* announcement pill button */
        .ann-comment-pill-btn {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          background: transparent;
          border: none;
          color: #1a73e8;
          font-size: 13px;
          font-weight: 500;
          cursor: pointer;
          padding: 6px 10px;
          border-radius: 50px;
          margin-top: 8px;
          transition: background 0.2s;
          font-family: inherit;
        }
        .ann-comment-pill-btn:hover { background: #c2e7ff; }
        [data-theme="dark"] .ann-comment-pill-btn { color: #8ab4f8; }
        [data-theme="dark"] .ann-comment-pill-btn:hover { background: #173149; }
        .ann-comment-pill-icon { width:16px; height:16px; opacity:0.8; }

        /* inline comment section */
        .ann-inline-comment-section { margin-top:8px; padding-top:4px; }
        .ann-inline-comment-item { display:flex; gap:10px; margin-bottom:10px; align-items:flex-start; }
        .ann-inline-comment-body { flex:1; }
        .ann-inline-comment-author { font-weight:600; font-size:13px; color:#202124; margin-right:6px; }
        .ann-inline-comment-date { font-size:12px; color:#5f6368; }
        .ann-inline-comment-text { font-size:13px; color:#202124; margin-top:2px; }
        [data-theme="dark"] .ann-inline-comment-author { color:#e0e0e0; }
        [data-theme="dark"] .ann-inline-comment-date { color:#9aa0a6; }
        [data-theme="dark"] .ann-inline-comment-text { color:#e0e0e0; }

        /* pill input wrapper */
        .ann-inline-input-row { display:flex; align-items:flex-start; gap:10px; margin-top:8px; }
        .ann-comment-pill-wrapper {
          flex:1; border:1.5px solid #dadce0; border-radius:24px;
          padding:8px 12px; display:flex; flex-direction:column; gap:6px;
          transition:border-color 0.2s, border-radius 0.2s; background:transparent;
        }
        .ann-comment-pill-wrapper.expanded { border-radius:12px; border-color:#1a73e8; }
        [data-theme="dark"] .ann-comment-pill-wrapper { border-color:#555; }
        [data-theme="dark"] .ann-comment-pill-wrapper.expanded { border-color:#8ab4f8; }
        .ann-comment-pill-input {
          width:100%; border:none; background:transparent; resize:none;
          font-family:inherit; font-size:13px; color:#202124; outline:none; line-height:1.5; box-sizing:border-box;
        }
        [data-theme="dark"] .ann-comment-pill-input { color:#e0e0e0; }
        .ann-comment-pill-toolbar { display:flex; align-items:center; gap:2px; }
        .ann-pill-send-btn {
          align-self:flex-end; background:none; border:none; cursor:not-allowed;
          padding:4px; border-radius:50%; display:flex; align-items:center; justify-content:center;
        }
        .ann-pill-send-btn:not([disabled]) { cursor:pointer; }
        .ann-pill-send-btn:not([disabled]):hover { background:#c2e7ff; }
        [data-theme="dark"] .ann-pill-send-btn:not([disabled]):hover { background:#173149; }

        /* new announcement button dark mode */
        [data-theme="dark"] .new-announcement-btn { background:#173149; color:#8ab4f8; border-color:#173149; }
        [data-theme="dark"] .new-announcement-btn:hover { background:#1e3f5a; }

        /* assignment comment modal */
        .asgn-comment-modal-overlay {
          display:none; position:fixed; inset:0;
          background:rgba(0,0,0,0.35); z-index:3000;
          align-items:center; justify-content:center; backdrop-filter:blur(2px);
        }
        .asgn-comment-modal-overlay.open { display:flex; }
        .asgn-comment-modal {
          background:#fff; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.2);
          width:100%; max-width:560px; margin:16px; display:flex; flex-direction:column;
          max-height:80vh; overflow:hidden;
        }
        [data-theme="dark"] .asgn-comment-modal { background:#2a2a2a; }
        .asgn-comment-modal-header {
          display:flex; align-items:center; justify-content:space-between;
          padding:16px 20px; font-size:16px; font-weight:600; color:#202124;
          border-bottom:1px solid #dadce0;
        }
        [data-theme="dark"] .asgn-comment-modal-header { color:#e0e0e0; border-color:#444; }
        .asgn-modal-close-btn {
          background:none; border:none; font-size:18px; cursor:pointer;
          color:#5f6368; padding:4px 8px; border-radius:50%;
        }
        .asgn-modal-close-btn:hover { background:#f1f3f4; }
        [data-theme="dark"] .asgn-modal-close-btn { color:#9aa0a6; }
        [data-theme="dark"] .asgn-modal-close-btn:hover { background:#3a3a3a; }
        .asgn-comment-modal-body { flex:1; overflow-y:auto; padding:16px 20px; display:flex; flex-direction:column; gap:12px; }
        .asgn-comment-modal-footer {
          display:flex; align-items:center; gap:10px;
          padding:12px 20px; border-top:1px solid #dadce0;
        }
        [data-theme="dark"] .asgn-comment-modal-footer { border-color:#444; }
        .asgn-modal-input {
          flex:1; border:1.5px solid #dadce0; border-radius:24px;
          padding:10px 16px; font-size:13px; font-family:inherit;
          background:transparent; color:#202124; outline:none;
          transition:border-color 0.2s;
        }
        .asgn-modal-input:focus { border-color:#1a73e8; }
        [data-theme="dark"] .asgn-modal-input { border-color:#555; color:#e0e0e0; }
        [data-theme="dark"] .asgn-modal-input:focus { border-color:#8ab4f8; }
        .asgn-modal-send-btn {
          background:none; border:none; cursor:not-allowed;
          padding:6px; border-radius:50%; display:flex; align-items:center;
        }
        .asgn-modal-send-btn:not([disabled]) { cursor:pointer; }
        .asgn-modal-send-btn:not([disabled]):hover { background:#c2e7ff; }
        [data-theme="dark"] .asgn-modal-send-btn:not([disabled]):hover { background:#173149; }
        .dot-menu {
  position:absolute; right:8px; top:40px;
  background:#fff; border-radius:8px;
  box-shadow:0 2px 12px rgba(0,0,0,0.18);
  width:160px; z-index:999; overflow:hidden;
}
[data-theme="dark"] .dot-menu { background:#2a2a2a; }
.dot-menu-item {
  padding:11px 18px; font-size:14px; cursor:pointer;
  display:flex; align-items:center; gap:10px;
  color:#202124; transition:background 0.15s;
}
.dot-menu-item:hover { background:#f1f3f4; }
[data-theme="dark"] .dot-menu-item { color:#e0e0e0; }
[data-theme="dark"] .dot-menu-item:hover { background:#3a3a3a; }
.dot-menu-item.danger { color:#d93025; }
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
            <?php if ($class['section']): ?>
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
                    <!-- LEFT: Upcoming box & Class Code -->
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        <div class="upcoming-widget">
                            <div class="upcoming-title">Upcoming</div>
                            <?php if (empty($assignments)): ?>
                                <div class="upcoming-empty">Woohoo, no work due in soon!</div>
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

                    <!-- RIGHT: stream content -->
                    <div style="flex:1;">
                        <?php if (!$is_student): ?>
                        <button class="new-announcement-btn" onclick="openAnnModal()">
                            <img src="<?php echo BASE_URL; ?>/icons/announcement icon.png" alt="">
                            New Announcement
                        </button>
                        <?php endif; ?>

                       

                        <?php foreach ($assignments as $asgn): ?>
<div class="ann-card" style="padding:0; overflow:hidden;" data-asgn-id="<?php echo $asgn['id']; ?>">
    <!-- clickable top part — classwork pe jaata hai -->
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
    <!-- horizontal divider -->
    <div style="border-top:1px solid #dadce0; margin:0 16px;"></div>
    <!-- comment button — alag, non-clickable from card -->
    <div style="padding:6px 16px 10px;">
      <button class="ann-comment-pill-btn" onclick="event.stopPropagation(); openAsgnCommentModal(<?php echo $asgn['id']; ?>)">
        <img src="<?php echo BASE_URL; ?>/icons/comment-icon.png" class="ann-comment-pill-icon" alt="">
        0 class comments
      </button>
    </div>
  </div>
<?php endforeach; ?>

 <!-- Announcement post cards -->
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $ann): 
                                // Fetch comment count
                                $stmtC = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE announcement_id = ?');
                                $stmtC->execute([$ann['id']]);
                                $comment_count = $stmtC->fetchColumn();

                                // Fetch latest 2 comments
                               $stmtL = $pdo->prepare('SELECT c.*, u.name AS commenter_name FROM comments c JOIN users u ON c.user_id = u.id WHERE c.announcement_id = ? ORDER BY c.created_at ASC');
                                $stmtL->execute([$ann['id']]);
                                $ann_comments = $stmtL->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                                <div class="ann-card" data-ann-id="<?php echo $ann['id']; ?>">
                                    <div class="ann-card-header">
                                        <div class="ann-avatar-circle"><?php echo strtoupper(substr($ann['author_name'], 0, 1)); ?></div>
                                        <div class="ann-card-meta">
                                            <div class="ann-card-author"><?php echo htmlspecialchars($ann['author_name']); ?></div>
                                            <div class="ann-card-time"><?php echo date('M d, Y \a\t H:i', strtotime($ann['created_at'])); ?></div>
                                        </div>
                                        <button class="ann-three-dot" title="More options" onclick="toggleDotMenu(event, 'ann', <?php echo $ann['id']; ?>, '<?php echo $user['role']; ?>')">
  <img src="<?php echo BASE_URL; ?>/icons/3dots-more-icon.png" style="width:18px;height:18px;opacity:0.7;" alt="">
</button>
                                    </div>
                                     <div class="ann-card-body"><?php echo htmlspecialchars($ann['message']); ?></div>
                                     
                                     <!-- divider line -->
                                     <div style="border-top:1px solid #dadce0; margin-top:12px;"></div>

                                     <!-- comment count pill button -->
                                     <button class="ann-comment-pill-btn" onclick="toggleAnnComment(<?php echo $ann['id']; ?>)">
                                       <img src="<?php echo BASE_URL; ?>/icons/comment-icon.png" class="ann-comment-pill-icon" alt="">
                                       <?php echo $comment_count; ?> class comment<?php echo $comment_count != 1 ? 's' : ''; ?>
                                     </button>

                                     <!-- inline expand section -->
                                     <div class="ann-inline-comment-section" id="ann-inline-<?php echo $ann['id']; ?>" style="display:none;">

                                       <!-- show latest 2 comments -->
                                       <?php foreach(array_reverse($ann_comments) as $c): ?>
                                       <div class="ann-inline-comment-item">
                                         <div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;">
                                           <?php echo strtoupper(substr($c['commenter_name'],0,1)); ?>
                                         </div>
                                         <div class="ann-inline-comment-body">
                                           <span class="ann-inline-comment-author"><?php echo htmlspecialchars($c['commenter_name']); ?></span>
                                           <span class="ann-inline-comment-date"><?php echo date('d M', strtotime($c['created_at'])); ?></span>
                                           <div class="ann-inline-comment-text"><?php echo htmlspecialchars($c['message']); ?></div>
                                         </div>
                                       </div>
                                       <?php endforeach; ?>

                                       <!-- add comment input -->
                                       <div class="ann-inline-input-row">
                                         <div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;">
                                           <?php echo strtoupper(substr($user['name'],0,1)); ?>
                                         </div>
                                         <div class="ann-comment-pill-wrapper" id="ann-pill-<?php echo $ann['id']; ?>">
                                           <textarea class="ann-comment-pill-input" id="ann-ctextarea-<?php echo $ann['id']; ?>"
                                                   placeholder="Add class comment..." rows="1"
                                                   onclick="expandAnnComment(<?php echo $ann['id']; ?>)"></textarea>
                                           <div class="ann-comment-pill-toolbar" id="ann-toolbar-<?php echo $ann['id']; ?>" style="display:none;">
                                             <button type="button" class="ann-tool-btn" onclick="applyAnnFmt(<?php echo $ann['id']; ?>,'bold')"><b>B</b></button>
                                             <button type="button" class="ann-tool-btn" onclick="applyAnnFmt(<?php echo $ann['id']; ?>,'italic')"><i>I</i></button>
                                             <button type="button" class="ann-tool-btn" onclick="applyAnnFmt(<?php echo $ann['id']; ?>,'underline')"><u>U</u></button>
                                             <button type="button" class="ann-tool-btn" onclick="applyAnnFmt(<?php echo $ann['id']; ?>,'list')">☰</button>
                                             <button type="button" class="ann-tool-btn" onclick="applyAnnFmt(<?php echo $ann['id']; ?>,'clear')">✕</button>
                                           </div>
                                           <button class="ann-pill-send-btn" id="ann-csend-<?php echo $ann['id']; ?>"
                                                   onclick="submitAnnComment(<?php echo $ann['id']; ?>)" disabled>
                                             <img src="<?php echo BASE_URL; ?>/icons/send-icon.png" alt="Send"
                                                  style="width:18px;height:18px;opacity:0.4;" id="ann-send-img-<?php echo $ann['id']; ?>">
                                           </button>
                                         </div>
                                       </div>
                                     </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-message">
                                <p>📢 No announcements yet</p>
                            </div>
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
                    <textarea
                        class="ann-textarea"
                        name="message"
                        id="annTextarea"
                        placeholder="Announce something to your class"
                        required
                    ></textarea>
                    <div class="ann-toolbar">
                        <button type="button" class="ann-tool-btn" title="Bold"      onclick="applyFmt('bold')"><b>B</b></button>
                        <button type="button" class="ann-tool-btn" title="Italic"    onclick="applyFmt('italic')"><i>I</i></button>
                        <button type="button" class="ann-tool-btn" title="Underline" onclick="applyFmt('underline')"><u>U</u></button>
                        <button type="button" class="ann-tool-btn" title="List"      onclick="applyFmt('list')">☰</button>
                        <button type="button" class="ann-tool-btn" title="Clear"     onclick="applyFmt('clear')">✕</button>
                    </div>
                    <div class="ann-toolbar-divider"></div>
                </div>
                <div class="ann-modal-footer">
                    <div class="ann-attach-btns">
                        <button type="button" class="ann-attach-btn" title="Attach folder">
                            <img src="<?php echo BASE_URL; ?>/icons/folder icon.svg" alt="Folder">
                        </button>
                        <button type="button" class="ann-attach-btn" title="Attach file">
                            <img src="<?php echo BASE_URL; ?>/icons/Clipboard.png" alt="File">
                        </button>
                        <button type="button" class="ann-attach-btn" title="Google Apps">
                            <img src="<?php echo BASE_URL; ?>/icons/googles-apps-icon.svg" alt="Drive">
                        </button>
                    </div>
                    <div class="ann-footer-actions">
                        <button type="button" class="ann-cancel-btn" onclick="closeAnnModal()">Cancel</button>
                        <button type="submit" class="ann-post-btn" id="annPostBtn" disabled>Post</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    function copyClassCode(code) {
        navigator.clipboard.writeText(code).then(() => {
            alert('Class code copied to clipboard!');
        }).catch(err => {
            console.error('Error copying code: ', err);
        });
    }
        document.getElementById('annOverlay').classList.add('open');
        setTimeout(() => document.getElementById('annTextarea').focus(), 80);
    }

    function closeAnnModal() {
        document.getElementById('annOverlay').classList.remove('open');
        const ta = document.getElementById('annTextarea');
        ta.value = '';
        ta.classList.remove('has-text');
        const btn = document.getElementById('annPostBtn');
        btn.disabled = true;
        btn.classList.remove('active');
    }

    function handleOverlayClick(e) {
        if (e.target === document.getElementById('annOverlay')) closeAnnModal();
    }

    document.getElementById('annTextarea').addEventListener('input', function() {
        const btn = document.getElementById('annPostBtn');
        const hasVal = this.value.trim().length > 0;
        btn.disabled = !hasVal;
        btn.classList.toggle('active', hasVal);
        this.classList.toggle('has-text', hasVal);
    });

    function applyFmt(cmd) {
        const ta = document.getElementById('annTextarea');
        const start = ta.selectionStart;
        const end   = ta.selectionEnd;
        const sel   = ta.value.substring(start, end);
        let wrap = '';
        if      (cmd === 'bold')      wrap = '**';
        else if (cmd === 'italic')    wrap = '_';
        else if (cmd === 'underline') wrap = '__';
        else if (cmd === 'list') {
            ta.value = ta.value.substring(0, start) + '\n• ' + sel + ta.value.substring(end);
            ta.focus();
            ta.dispatchEvent(new Event('input'));
            return;
        } else if (cmd === 'clear') {
            ta.value = ta.value.replace(/[*_~`]/g, '');
            ta.focus();
            ta.dispatchEvent(new Event('input'));
            return;
        }
        if (wrap) {
            ta.value = ta.value.substring(0, start) + wrap + sel + wrap + ta.value.substring(end);
            ta.selectionStart = start + wrap.length;
            ta.selectionEnd   = end   + wrap.length;
        }
        ta.focus();
        ta.dispatchEvent(new Event('input'));
    }

    let currentAsgnId = null;

    function toggleAnnComment(id) {
      const section = document.getElementById('ann-inline-' + id);
      const isHidden = section.style.display === 'none';
      section.style.display = isHidden ? 'block' : 'none';
      if (isHidden) setTimeout(() => document.getElementById('ann-ctextarea-' + id).focus(), 80);
    }

    function expandAnnComment(id) {
      document.getElementById('ann-pill-' + id).classList.add('expanded');
      document.getElementById('ann-toolbar-' + id).style.display = 'flex';
    }

    document.addEventListener('input', function(e) {
      if (e.target.classList.contains('ann-comment-pill-input')) {
        const id = e.target.id.replace('ann-ctextarea-', '');
        const btn = document.getElementById('ann-csend-' + id);
        const img = document.getElementById('ann-send-img-' + id);
        const hasVal = e.target.value.trim().length > 0;
        btn.disabled = !hasVal;
        if (img) img.style.opacity = hasVal ? '1' : '0.4';
      }
      if (e.target.id === 'asgnModalInput') {
        const hasVal = e.target.value.trim().length > 0;
        document.getElementById('asgnModalSend').disabled = !hasVal;
        document.getElementById('asgnModalSendImg').style.opacity = hasVal ? '1' : '0.4';
      }
    });

    document.addEventListener('click', function(e) {
      document.querySelectorAll('.ann-comment-pill-wrapper').forEach(wrapper => {
        const id = wrapper.id.replace('ann-pill-', '');
        const ta = document.getElementById('ann-ctextarea-' + id);
        if (!wrapper.contains(e.target) && ta && !ta.value.trim()) {
          wrapper.classList.remove('expanded');
          const toolbar = document.getElementById('ann-toolbar-' + id);
          if (toolbar) toolbar.style.display = 'none';
        }
      });
    });

    function applyAnnFmt(id, cmd) {
      const ta = document.getElementById('ann-ctextarea-' + id);
      const start = ta.selectionStart, end = ta.selectionEnd;
      const sel = ta.value.substring(start, end);
      let wrap = '';
      if (cmd === 'bold') wrap = '**';
      else if (cmd === 'italic') wrap = '_';
      else if (cmd === 'underline') wrap = '__';
      else if (cmd === 'list') { ta.value = ta.value.substring(0,start)+'\n• '+sel+ta.value.substring(end); ta.dispatchEvent(new Event('input')); return; }
      else if (cmd === 'clear') { ta.value = ta.value.replace(/[*_~`]/g,''); ta.dispatchEvent(new Event('input')); return; }
      if (wrap) ta.value = ta.value.substring(0,start)+wrap+sel+wrap+ta.value.substring(end);
      ta.focus(); ta.dispatchEvent(new Event('input'));
    }

    function submitAnnComment(annId) {
  const ta = document.getElementById('ann-ctextarea-' + annId);
  const msg = ta.value.trim();
  if (!msg) return;

  fetch('add_comment.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'entity_id=' + annId + '&entity_type=announcement&message=' + encodeURIComponent(msg)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // clear input
      ta.value = '';
      const btn = document.getElementById('ann-csend-' + annId);
      const img = document.getElementById('ann-send-img-' + annId);
      btn.disabled = true;
      if (img) img.style.opacity = '0.4';

      // comment wahan insert karo, section band mat karo
      const section = document.getElementById('ann-inline-' + annId);
      const inputRow = section.querySelector('.ann-inline-input-row');
      const div = document.createElement('div');
      div.className = 'ann-inline-comment-item';
      div.innerHTML = `
        <div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;">
          <?php echo strtoupper(substr($user['name'],0,1)); ?>
        </div>
        <div class="ann-inline-comment-body">
          <span class="ann-inline-comment-author"><?php echo htmlspecialchars($user['name']); ?></span>
          <span class="ann-inline-comment-date">Just now</span>
          <div class="ann-inline-comment-text">${msg}</div>
        </div>`;
     section.insertBefore(div, inputRow);

      // count update karo
      const pillBtn = document.querySelector(`[data-ann-id="${annId}"] .ann-comment-pill-btn`);
      if (pillBtn) {
        const cur = parseInt(pillBtn.textContent.trim()) || 0;
        const newCount = cur + 1;
        pillBtn.innerHTML = `<img src="${pillBtn.querySelector('img').src}" class="ann-comment-pill-icon" alt=""> ${newCount} class comment${newCount !== 1 ? 's' : ''}`;
      }
    }
  });
}
    function openAsgnCommentModal(asgnId) {
  currentAsgnId = asgnId;
  document.getElementById('asgnCommentOverlay').classList.add('open');
  document.getElementById('asgnModalBody').innerHTML = '<p style="color:#5f6368;font-size:13px;">Loading...</p>';
  document.getElementById('asgnModalInput').value = '';
  document.getElementById('asgnModalSend').disabled = true;
  document.getElementById('asgnModalSendImg').style.opacity = '0.4';

  // DB se comments fetch karo
  fetch('get_comments.php?entity_id=' + asgnId + '&entity_type=assignment')
  .then(r => r.json())
  .then(data => {
    const body = document.getElementById('asgnModalBody');
    if (!data.comments || data.comments.length === 0) {
      body.innerHTML = '<p style="color:#5f6368;font-size:13px;">No comments yet.</p>';
    } else {
      body.innerHTML = '';
      data.comments.forEach(c => {
        const div = document.createElement('div');
        div.className = 'ann-inline-comment-item';
        div.innerHTML = `
          <div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;">
            ${c.name.charAt(0).toUpperCase()}
          </div>
          <div class="ann-inline-comment-body">
            <span class="ann-inline-comment-author">${c.name}</span>
            <span class="ann-inline-comment-date">${c.created_at}</span>
            <div class="ann-inline-comment-text">${c.message}</div>
          </div>`;
        body.appendChild(div);
      });
    }
    setTimeout(() => document.getElementById('asgnModalInput').focus(), 80);
  });
}
    function submitAsgnComment() {
  const input = document.getElementById('asgnModalInput');
  const msg = input.value.trim();
  if (!msg || !currentAsgnId) return;
  
  fetch('add_comment.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'entity_id=' + currentAsgnId + '&entity_type=assignment&message=' + encodeURIComponent(msg)
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      input.value = '';
      document.getElementById('asgnModalSend').disabled = true;
      document.getElementById('asgnModalSendImg').style.opacity = '0.4';
      
      const body = document.getElementById('asgnModalBody');
      const noComments = body.querySelector('p');
      if (noComments) noComments.remove();
      
      const div = document.createElement('div');
      div.className = 'ann-inline-comment-item';
      div.innerHTML = `
        <div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;">
          <?php echo strtoupper(substr($user['name'],0,1)); ?>
        </div>
        <div class="ann-inline-comment-body">
          <span class="ann-inline-comment-author"><?php echo htmlspecialchars($user['name']); ?></span>
          <span class="ann-inline-comment-date">Just now</span>
          <div class="ann-inline-comment-text">${msg}</div>
        </div>`;
      body.appendChild(div);
    }
  });
}
let activeDotMenu = null;

function toggleDotMenu(e, type, id, role) {
  e.stopPropagation();
  e.preventDefault();

  if (activeDotMenu) { 
    activeDotMenu.remove(); 
    activeDotMenu = null; 
    return; 
  }

  const btn = e.currentTarget;
  const rect = btn.getBoundingClientRect();
  
  const menu = document.createElement('div');
  menu.className = 'dot-menu';
  menu.style.position = 'fixed';
  menu.style.zIndex = '9999';
  menu.style.top = (rect.bottom + 4) + 'px';
  menu.style.left = (rect.left - 130) + 'px';

  if (type === 'ann') {
    if (role === 'teacher') {
      menu.innerHTML = `
        <div class="dot-menu-item" onclick="deleteAnn(${id})">Delete</div>
        <div class="dot-menu-item danger" onclick="reportAbuse('ann',${id})">Report abuse</div>`;
    } else {
      menu.innerHTML = `
        <div class="dot-menu-item danger" onclick="reportAbuse('ann',${id})">Report abuse</div>`;
    }
  } else {
    if (role === 'teacher') {
      menu.innerHTML = `
        <div class="dot-menu-item" onclick="deleteAsgn(${id})">Delete</div>
        <div class="dot-menu-item danger" onclick="reportAbuse('asgn',${id})">Report abuse</div>`;
    } else {
      menu.innerHTML = `
        <div class="dot-menu-item danger" onclick="reportAbuse('asgn',${id})">Report abuse</div>`;
    }
  }

  document.body.appendChild(menu);
  activeDotMenu = menu;
}
function deleteAnn(id) {
  fetch('delete_announcement.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'announcement_id=' + id
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      // page se card remove karo bina reload ke
      if (activeDotMenu) { activeDotMenu.remove(); activeDotMenu = null; }
      const card = document.querySelector(`[data-ann-id="${id}"]`);
      if (card) card.remove();
    } else {
      alert('Could not delete.');
    }
  });
}
function deleteAsgn(id) {
  fetch('../assignments/delete_assignment.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'assignment_id=' + id
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      if (activeDotMenu) { activeDotMenu.remove(); activeDotMenu = null; }
      const card = document.querySelector(`[data-asgn-id="${id}"]`);
      if (card) card.remove();
    } else {
      alert('Could not delete.');
    }
  });
}
function reportAbuse(type, id) {
  alert('Report submitted.');
  if (activeDotMenu) { activeDotMenu.remove(); activeDotMenu = null; }
}

// bahar click karne pe band ho
document.addEventListener('click', function() {
  if (activeDotMenu) { activeDotMenu.remove(); activeDotMenu = null; }
});
function handleAsgnModalOverlay(e) {
  if (e.target === document.getElementById('asgnCommentOverlay')) closeAsgnCommentModal();
}

function closeAsgnCommentModal() {
  document.getElementById('asgnCommentOverlay').classList.remove('open');
  currentAsgnId = null;
}
    </script>
    <!-- Assignment Comment Modal -->
    <div class="asgn-comment-modal-overlay" id="asgnCommentOverlay" onclick="handleAsgnModalOverlay(event)">
      <div class="asgn-comment-modal">
        <div class="asgn-comment-modal-header">
          <span>Class comments</span>
          <button class="asgn-modal-close-btn" onclick="closeAsgnCommentModal()">✕</button>
        </div>
        <div class="asgn-comment-modal-body" id="asgnModalBody">
          <!-- comments load here -->
        </div>
        <div class="asgn-comment-modal-footer">
          <div class="ann-avatar-circle" style="width:32px;height:32px;font-size:12px;flex-shrink:0;">
            <?php echo strtoupper(substr($user['name'],0,1)); ?>
          </div>
          <input type="text" class="asgn-modal-input" id="asgnModalInput" placeholder="Add class comment...">
          <button class="asgn-modal-send-btn" id="asgnModalSend" onclick="submitAsgnComment()" disabled>
            <img src="<?php echo BASE_URL; ?>/icons/send-icon.png" alt="Send" style="width:18px;height:18px;opacity:0.4;" id="asgnModalSendImg">
          </button>
        </div>
      </div>
    </div>
</body>
</html>
