<?php
// Shared class tabs — expects $class_id and $activeTab ('stream'|'classwork'|'people').
?>
<div class="tabs">
    <a href="stream.php?class_id=<?php echo $class_id; ?>" class="tab <?php echo ($activeTab === 'stream') ? 'active' : ''; ?>">Stream</a>
    <a href="classwork.php?class_id=<?php echo $class_id; ?>" class="tab <?php echo ($activeTab === 'classwork') ? 'active' : ''; ?>">Classwork</a>
    <a href="people.php?class_id=<?php echo $class_id; ?>" class="tab <?php echo ($activeTab === 'people') ? 'active' : ''; ?>">People</a>
</div>
