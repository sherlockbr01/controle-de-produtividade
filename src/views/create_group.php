<?php
use Jti30\SistemaProdutividade\Controllers\GroupController;

// Verifique se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: /sistema_produtividade/public/login');
    exit;
}

$groupController = new GroupController($pdo, $authController);

// Obtenha todos os grupos
$groups = $groupController->getAllGroups();

// Processar a submissão do formulário para criar um grupo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (mantenha o código existente para processamento do formulário)
}

// Definir os itens de menu para esta página
$menuItems = [
    ['url' => '/sistema_produtividade/public/dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => '/sistema_produtividade/public/manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => '/sistema_produtividade/public/assign-user-group', 'icon' => 'fas fa-user-plus', 'text' => 'Atribuir Usuário a Grupo'],
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Novo Grupo</title>
    <link rel="stylesheet" href="/sistema_produtividade/public/css/create_group.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/sidebar.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../compnents/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../compnents/header.php'; ?>

        <main>
            <h1>Criar Novo Grupo</h1>

            <!-- Exibir mensagens de sucesso ou erro -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div id="success-message" class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div id="error-message" class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <form action="/sistema_produtividade/public/create-group" method="post">
                <div class="form-group">
                    <label for="name">Nome do Grupo:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Descrição:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <button type="submit" class="button-submit">Criar Grupo</button>
            </form>

            <!-- Exibir grupos existentes em cartões -->
            <h2>Grupos Existentes</h2>
            <div class="summary-cards">
                <?php foreach ($groups as $group): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                        <p><?php echo htmlspecialchars($group['description']); ?></p>
                        <div class="user-count">
                            <span class="icon">👤</span>
                            <span class="count"><?php echo htmlspecialchars($group['user_count']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>
<script>
    // Faz a mensagem de sucesso ou erro desaparecer após 5 segundos
    setTimeout(function() {
        var successMessage = document.getElementById('success-message');
        var errorMessage = document.getElementById('error-message');
        if (successMessage) {
            successMessage.style.display = 'none';
        }
        if (errorMessage) {
            errorMessage.style.display = 'none';
        }
    }, 5000);
</script>
</body>
</html>