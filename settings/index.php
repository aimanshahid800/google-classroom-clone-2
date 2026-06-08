<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
// TODO: implement settings UI with notifications and profile options
?>

<?php $pageTitle = 'Settings | Classroom Clone'; include __DIR__ . '/../includes/header.php'; ?>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
