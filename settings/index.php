<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
// TODO: implement settings UI with notifications and profile options
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Settings | Classroom Clone</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <div class="page-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
      <?php include __DIR__ . '/../includes/navbar.php'; ?>
      <h1>Settings</h1>
      <p>Profile and notification settings will be added here.</p>
    </main>
  </div>
</body>
</html>
