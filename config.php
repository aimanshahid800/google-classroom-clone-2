<?php
// config.php
// Database connection and shared settings

session_start();

$dbHost = 'localhost';
$dbName = 'classroom_clone';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('A database error occurred. Please try again later.');
}

// Helper: require login
function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

/**
 * Render a user-friendly error page and terminate.
 * Use instead of bare die() for authorization or "not found" errors.
 */
function render_error_page(string $title, string $message, int $http_code = 403): void {
    http_response_code($http_code);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> | Classroom Clone</title>
        <link rel="stylesheet" href="/style.css">
        <style>
            body { background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .error-container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); text-align: center; max-width: 480px; }
            .error-container h1 { color: #c62828; margin-bottom: 12px; }
            .error-container p { color: #555; margin-bottom: 24px; }
            .error-container a { color: var(--primary, #1a73e8); text-decoration: none; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="/home/dashboard.php">&larr; Back to Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
