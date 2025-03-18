<?php
// Verifica se a sessão está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a constante BASE_URL está definida
if (!defined('BASE_URL')) {
    // Função para obter a URL base do projeto
    function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $dirName = dirname($scriptName);

        // Se estiver na raiz do domínio, retorna apenas o protocolo e host
        if ($dirName == '/' || $dirName == '\\') {
            return $protocol . $host;
        }

        // Remove o segmento '/public' do caminho se estiver presente
        $basePath = $protocol . $host . $dirName;
        if (strpos($basePath, '/public') !== false) {
            $basePath = substr($basePath, 0, strpos($basePath, '/public') + 7);
        }

        return $basePath;
    }

    define('BASE_URL', getBaseUrl());
}

// Verifica se $menuItems está definido, caso contrário, usa um array vazio
$menuItems = isset($menuItems) ? $menuItems : [];

// Função para processar URLs e garantir que usem BASE_URL
function processUrl($url) {
    // Se a URL já começar com http:// ou https://, não modificar
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }

    // Se for um link de âncora (#), não modificar
    if ($url === '#') {
        return $url;
    }

    // Se a URL começar com /, remover a barra inicial para evitar duplicação
    if (strpos($url, '/') === 0) {
        $url = substr($url, 1);
    }

    return BASE_URL . '/' . $url;
}
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
                        <a href="<?php echo htmlspecialchars(processUrl($subitem['url'])); ?>">
                            <i class="<?php echo htmlspecialchars($subitem['icon']); ?>"></i>
                            <span><?php echo htmlspecialchars($subitem['text']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars(processUrl($item['url'])); ?>">
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