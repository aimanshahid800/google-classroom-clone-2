<?php
require_once __DIR__ . '/../config.php';
require_login();

$user = current_user();
// TODO: implement To Do page with Assigned / Missing / Done tabs
?>

<?php $pageTitle = 'To Do | Classroom Clone'; include __DIR__ . '/../includes/header.php'; ?>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
