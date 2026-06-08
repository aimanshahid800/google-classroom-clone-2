<?php
require_once __DIR__ . '/config.php';

try {
    $stmt = $pdo->query('SELECT 1');
    if ($stmt) {
        echo "Database connection OK.\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
