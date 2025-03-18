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

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    // Redirecionar para a página de login se não estiver logado
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$userName = htmlspecialchars($_SESSION['user_name']);
?>

<header class="header">
    <h1><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Sistema de Produtividade'; ?></h1>
    <div class="user-info">
        <span class="user-name"><?php echo $userName; ?></span>
        <a href="<?php echo BASE_URL; ?>/logout" class="btn-logout">Sair</a>
    </div>
</header>