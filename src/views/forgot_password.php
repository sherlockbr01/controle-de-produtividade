<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a constante BASE_URL está definida
if (!defined('BASE_URL')) {
    // Defina BASE_URL aqui se necessário
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Sistema de Produtividade</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/auth.css">
</head>
<body>
<div class="auth-container">
    <h1 class="auth-title">Recuperar Senha</h1>
    <?php if (isset($_SESSION['reset_message'])): ?>
        <p class="<?php echo strpos($_SESSION['reset_message'], 'enviado') !== false ? 'success' : 'error'; ?>">
            <?php echo $_SESSION['reset_message']; ?>
        </p>
        <?php unset($_SESSION['reset_message']); ?>
    <?php endif; ?>
    <div class="form-container">
        <form action="<?php echo BASE_URL; ?>/request-password-reset" method="post" class="form-auth">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Seu email" required>
            </div>
            <button type="submit" class="btn">Enviar Link de Recuperação</button>
        </form>
    </div>
    <p class="auth-link">Lembrou sua senha? <a href="<?php echo BASE_URL; ?>/login">Faça login</a></p>
</div>
</body>
</html>