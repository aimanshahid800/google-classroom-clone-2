<?php
require_once __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /Uni-Team-Project/google-classroom-clone-2/auth/login.php');
    exit;
}

header('Location: /Uni-Team-Project/google-classroom-clone-2/home/dashboard.php');
exit;
