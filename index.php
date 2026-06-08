<?php
require_once __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

header('Location: ' . BASE_URL . '/home/dashboard.php');
exit;
