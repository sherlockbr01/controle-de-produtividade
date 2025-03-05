<?php
// Verifica se a sessão está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    // Redirecionar para a página de login se não estiver logado
    header('Location: /sistema_produtividade/public/login');
    exit;
}

$userName = htmlspecialchars($_SESSION['user_name']);
?>

<header class="header">
    <h1><?php echo htmlspecialchars($pageTitle ?? 'Sistema de Produtividade'); ?></h1>
    <div class="user-info">
        <span class="user-name"><?php echo $userName; ?></span>
        <a href="/sistema_produtividade/public/logout" class="btn-logout">Sair</a>
    </div>
</header>