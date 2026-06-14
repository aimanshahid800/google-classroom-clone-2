<?php
require_once 'config.php';

try {
    // Delete members of 'qwer' class first due to foreign key constraints
    $stmt = $pdo->prepare('DELETE FROM class_members WHERE class_id IN (SELECT id FROM classes WHERE name = "qwer")');
    $stmt->execute();
    
    // Delete the class itself
    $stmt = $pdo->prepare('DELETE FROM classes WHERE name = "qwer"');
    $stmt->execute();
    
    echo "Class 'qwer' and its members deleted successfully!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
