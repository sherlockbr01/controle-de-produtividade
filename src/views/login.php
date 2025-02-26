<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    // Redirecionar para o dashboard apropriado com base no tipo de usuário
    if ($_SESSION['user_type'] === 'servidor') {
        header('Location: /sistema_produtividade/public/dashboard-servidor');
    } elseif ($_SESSION['user_type'] === 'diretor') {
        header('Location: /sistema_produtividade/public/dashboard-diretor');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Produtividade</title>
    <link rel="stylesheet" href="/sistema_produtividade/public/css/auth.css">
</head>
<body>

<div class="intro-container">
    <h2 class="intro-title">Bem-vindo ao Sistema de Produtividade</h2>
    <p class="intro-text">Aumente sua eficiência e alcance seus objetivos.</p>
</div>

<div class="auth-container">
    <?php if (isset($_SESSION['register_success'])): ?>
        <div class="success-message" id="success-message">
            <?php echo htmlspecialchars($_SESSION['register_success']); ?>
        </div>
        <?php unset($_SESSION['register_success']); ?>
    <?php endif; ?>
    <h1 class="auth-title">Login</h1>
    <?php if (isset($_SESSION['login_error'])): ?>
        <p class="error"><?php echo htmlspecialchars($_SESSION['login_error']); ?></p>
        <?php unset($_SESSION['login_error']); ?>
    <?php endif; ?>
    <div class="form-container">
        <form action="/sistema_produtividade/public/login" method="post" class="form-auth">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" name="password" id="password" placeholder="Senha" required>
            </div>
            <button type="submit" class="btn">Entrar</button>
        </form>
    </div>
    <p class="auth-link">Não tem uma conta? <a href="/sistema_produtividade/public/register">Registre-se</a></p>
</div>

<script>
    // Faz a mensagem de sucesso desaparecer após 5 segundos
    setTimeout(function() {
        var successMessage = document.getElementById('success-message');
        if (successMessage) {
            successMessage.style.display = 'none';
        }
    }, 5000);
</script>

</body>
</html>