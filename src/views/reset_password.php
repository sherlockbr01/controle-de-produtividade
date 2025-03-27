<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a constante BASE_URL está definida
if (!defined('BASE_URL')) {
    // Defina BASE_URL aqui se necessário
    define('BASE_URL', 'http://localhost/seu-projeto'); // Ajuste conforme necessário
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sistema de Produtividade</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/auth.css">
</head>
<body>
<div class="auth-container">
    <h1 class="auth-title">Redefinir Senha</h1>
    <?php if (isset($_SESSION['reset_instruction'])): ?>
        <p class="info-message" style="color: #0056b3;">
            <?php echo $_SESSION['reset_instruction']; ?>
        </p>
        <?php unset($_SESSION['reset_instruction']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['reset_message'])): ?>
        <p class="<?php echo strpos($_SESSION['reset_message'], 'sucesso') !== false ? 'success' : 'error'; ?>">
            <?php echo $_SESSION['reset_message']; ?>
        </p>
        <?php unset($_SESSION['reset_message']); ?>
    <?php endif; ?>
    <div class="form-container">
        <form action="<?php echo BASE_URL; ?>/reset-password" method="post" class="form-auth" id="resetPasswordForm">
            <div class="form-group">
                <label for="reset_code">Código de Recuperação</label>
                <input type="text" name="reset_code" id="reset_code" placeholder="Digite o código recebido por e-mail" required>
            </div>
            <div class="form-group">
                <label for="new_password">Nova Senha</label>
                <input type="password" name="new_password" id="new_password" placeholder="Digite sua nova senha" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar Nova Senha</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirme sua nova senha" required>
            </div>
            <div id="password-strength"></div>
            <button type="submit" class="btn">Redefinir Senha</button>
        </form>
        <p class="auth-link">Lembrou sua senha? <a href="<?php echo BASE_URL; ?>/login">Faça login</a></p>
    </div>
</div>

<script>
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        var newPassword = document.getElementById('new_password').value;
        var confirmPassword = document.getElementById('confirm_password').value;

        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('As senhas não coincidem. Por favor, tente novamente.');
        }
    });

    document.getElementById('new_password').addEventListener('input', function() {
        var password = this.value;
        var strength = 0;
        if (password.match(/[a-z]+/)) strength += 1;
        if (password.match(/[A-Z]+/)) strength += 1;
        if (password.match(/[0-9]+/)) strength += 1;
        if (password.match(/[$@#&!]+/)) strength += 1;

        var strengthDiv = document.getElementById('password-strength');
        if (strength < 2) {
            strengthDiv.innerHTML = 'Senha fraca';
            strengthDiv.style.color = 'red';
        } else if (strength < 4) {
            strengthDiv.innerHTML = 'Senha média';
            strengthDiv.style.color = 'orange';
        } else {
            strengthDiv.innerHTML = 'Senha forte';
            strengthDiv.style.color = 'green';
        }
    });
</script>

</body>
</html>