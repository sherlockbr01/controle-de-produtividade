<?php
// Verifica se $menuItems está definido, caso contrário, usa um array vazio
$menuItems = isset($menuItems) ? $menuItems : [];
?>

<div class="sidebar">
    <?php foreach ($menuItems as $item): ?>
        <a href="<?php echo htmlspecialchars($item['url']); ?>">
            <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i> <?php echo htmlspecialchars($item['text']); ?>
        </a>
    <?php endforeach; ?>
</div>