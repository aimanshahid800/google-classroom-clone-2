<?php
require_once 'config.php';

try {
    // 1. Delete from class_members first (foreign key constraint)
    $stmt1 = $pdo->prepare('DELETE FROM class_members WHERE class_id IN (SELECT id FROM classes WHERE name = ?)');
    $stmt1->execute(['qwer']);
    
    // 2. Delete from submissions (if any)
    $stmt2 = $pdo->prepare('DELETE FROM submissions WHERE assignment_id IN (SELECT id FROM assignments WHERE class_id IN (SELECT id FROM classes WHERE name = ?))');
    $stmt2->execute(['qwer']);
    
    // 3. Delete from assignments (if any)
    $stmt3 = $pdo->prepare('DELETE FROM assignments WHERE class_id IN (SELECT id FROM classes WHERE name = ?)');
    $stmt3->execute(['qwer']);
    
    // 4. Finally delete the class
    $stmt4 = $pdo->prepare('DELETE FROM classes WHERE name = ?');
    $stmt4->execute(['qwer']);
    
    echo "Success: Class 'qwer' and all related data deleted!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
