<?php
// Verifica se $menuItems está definido, caso contrário, usa um array vazio
$menuItems = isset($menuItems) ? $menuItems : [];
?>

<div class="sidebar">
    <?php foreach ($menuItems as $item): ?>
        <?php if (isset($item['submenu'])): ?>
            <div class="has-submenu">
                <a href="#" class="toggle-submenu">
                    <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                    <span><?php echo htmlspecialchars($item['text']); ?></span>
                    <i class="fas fa-chevron-down submenu-icon"></i>
                </a>
                <div class="submenu">
                    <?php foreach ($item['submenu'] as $subitem): ?>
                        <a href="<?php echo htmlspecialchars($subitem['url']); ?>">
                            <i class="<?php echo htmlspecialchars($subitem['icon']); ?>"></i>
                            <span><?php echo htmlspecialchars($subitem['text']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars($item['url']); ?>">
                <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                <span><?php echo htmlspecialchars($item['text']); ?></span>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var submenuToggles = document.querySelectorAll('.toggle-submenu');
        submenuToggles.forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var parent = this.closest('.has-submenu');
                parent.classList.toggle('active');
            });
        });
    });
</script>