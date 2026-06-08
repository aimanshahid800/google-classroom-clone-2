<?php
// Shared HTML <head> — set $pageTitle before including this file.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Classroom Clone'); ?></title>
    <link rel="stylesheet" href="<?php echo $stylePath ?? '../style.css'; ?>">
