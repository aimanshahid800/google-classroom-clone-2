<?php
// Shared class page header — expects $class array to be set.
?>
<div class="class-header">
    <h1><?php echo htmlspecialchars($class['name']); ?></h1>
    <div class="class-meta">
        <?php if (!empty($class['section'])): ?>
            <span>Section: <?php echo htmlspecialchars($class['section']); ?></span>
        <?php endif; ?>
        <?php if (!empty($classMeta)): ?>
            <?php foreach ($classMeta as $item): ?>
                <span><?php echo $item; ?></span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
