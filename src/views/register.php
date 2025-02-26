<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    // Redirecionar para o dashboard apropriado se o usuário já estiver logado
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
    <title>Registro - Sistema de Produtividade</title>
    <link rel="stylesheet" href="/sistema_produtividade/public/css/auth.css">
</head>
<body>
<div class="auth-container">
    <h1 class="auth-title">Registro</h1>
    <?php if (isset($_SESSION['register_error'])): ?>
        <p class="error"><?php echo htmlspecialchars($_SESSION['register_error']); ?></p>
        <?php unset($_SESSION['register_error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['register_success'])): ?>
        <p class="success"><?php echo htmlspecialchars($_SESSION['register_success']); ?></p>
        <?php unset($_SESSION['register_success']); ?>
    <?php endif; ?>
    <div class="form-container">
        <form action="/sistema_produtividade/public/register" method="post" class="form-auth">
            <div class="form-group">
                <label for="name">Nome:</label>
                <input type="text" id="name" name="name" placeholder="Nome" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" placeholder="Senha" required>
            </div>
            <div class="form-group">
                <label for="user_type">Tipo de Usuário:</label>
                <select id="user_type" name="user_type">
                    <option value="servidor">Servidor</option>
                    <option value="diretor">Diretor</option>
                </select>
            </div>
            <button type="submit" class="btn">Registrar</button>
        </form>
    </div>
    <p class="auth-link">Já tem uma conta? <a href="/sistema_produtividade/public/login">Faça login</a></p>
</div>
</body>
</html>