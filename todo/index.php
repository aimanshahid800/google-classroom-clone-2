<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
// TODO: implement To Do page with Assigned / Missing / Done tabs
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>To Do | Classroom Clone</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <div class="page-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="content">
      <?php include __DIR__ . '/../includes/navbar.php'; ?>
      <h1>To Do</h1>
      <p>Assigned / Missing / Done tabs will be added here.</p>
    </main>
  </div>
</body>
</html>
