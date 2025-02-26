<?php
use Jti30\SistemaProdutividade\Controllers\GroupController;

// Verifique se o usuário está autenticado como diretor
$authController->requireDirectorAuth();

$groupController = new GroupController($pdo, $authController);

// Obtenha todos os grupos
$allGroups = $groupController->getAllGroups();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Grupos</title>
    <style>
        /* Reset e estilos gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #2c2f33;
            color: #ffffff;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        nav {
            width: 250px;
            background-color: #23272a;
            padding: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }

        nav ul {
            list-style-type: none;
        }

        nav a {
            display: block;
            color: #ecf0f1;
            text-decoration: none;
            margin-bottom: 15px;
            font-size: 1.1em;
            transition: color 0.3s;
        }

        nav a:hover {
            color: #7289da;
        }

        /* Conteúdo principal */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            flex-grow: 1;
            background-color: #36393f;
        }

        /* Cabeçalho */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        header h1 {
            font-size: 2em;
            color: #ffffff;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info span {
            margin-right: 15px;
            font-weight: bold;
        }

        .btn-logout {
            background-color: #e74c3c;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-logout:hover {
            background-color: #c0392b;
        }

        /* Estilos adicionais para a página de gerenciamento de grupos */
        .group-list {
            margin-top: 20px;
        }

        .group-list h2 {
            color: #ffffff;
            margin-bottom: 10px;
        }

        .group-list ul {
            list-style-type: none;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .group-list li {
            background-color: #2f3136;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 15px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: calc(33.333% - 10px);
            box-sizing: border-box;
        }

        .group-info {
            flex-grow: 1;
        }

        .group-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            align-items: center; /* Alinha os itens verticalmente */
        }

        .btn-view, .btn-delete {
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
            color: #fff;
            transition: background-color 0.3s;
            display: inline-block; /* Garante que ambos os elementos tenham o mesmo comportamento de bloco */
            line-height: 1.5; /* Ajusta a altura da linha para ser consistente */
            font-size: 14px; /* Define um tamanho de fonte consistente */
            border: none; /* Remove qualquer borda que possa afetar o tamanho */
            cursor: pointer; /* Garante que ambos pareçam clicáveis */
        }

        .btn-view {
            background-color: #3498db;
        }

        .btn-view:hover {
            background-color: #2980b9;
        }

        .btn-delete {
            background-color: #e74c3c;
        }

        .btn-delete:hover {
            background-color: #c0392b;
        }

        .user-count {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .user-count .icon {
            font-size: 1.2em;
        }

        .message {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-radius: 5px;
            font-weight: bold;
        }

        .success-message {
            background-color: #4CAF50; /* Verde */
            color: white;
        }

        .error-message {
            background-color: #f44336; /* Vermelho */
            color: white;
        }
    </style>
</head>
<body>
<nav>
    <ul>
        <li><a href="/sistema_produtividade/public/dashboard-diretor">Início</a></li>
        <li><a href="/sistema_produtividade/public/create-group">Criar Grupo</a></li>
        <li><a href="/sistema_produtividade/public/assign-user-group">Atribuir Usuário a Grupo</a></li>
        <!-- Adicione mais links conforme necessário -->
    </ul>
</nav>
<div class="main-content">
    <header>
        <h1>Gerenciar Grupos</h1>
        <div class="user-info">
            <span>Bem-vindo, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="/sistema_produtividade/public/logout" class="btn-logout">Sair</a>
        </div>
    </header>

    <!-- Mensagens de Sucesso/Erro -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="message success-message" id="success-message">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="message error-message" id="error-message">
            <?= htmlspecialchars($_SESSION['error_message']); ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="group-list">
        <h2>Lista de Grupos</h2>
        <ul>
            <?php foreach ($allGroups as $group): ?>
                <li>
                    <div class="group-info">
                        <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                        <p><?php echo htmlspecialchars($group['description']); ?></p>
                        <div class="user-count">
                            <span class="icon">👤</span>
                            <span class="count"><?php echo htmlspecialchars($group['user_count']); ?></span>
                        </div>
                    </div>
                    <div class="group-actions">
                        <a href="/sistema_produtividade/public/visualizar-grupo-diretor?id=<?php echo htmlspecialchars($group['id']); ?>" class="btn-view">Ver Grupo</a>
                        <form action="/sistema_produtividade/public/delete-group" method="post" style="display:inline;">
                            <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group['id']); ?>">
                            <button type="submit" class="btn-delete">Excluir Grupo</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
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