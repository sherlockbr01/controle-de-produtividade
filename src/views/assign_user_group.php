<?php
// Verifique se o usuário está autenticado como diretor
$authController->requireDirectorAuth();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atribuir Usuário a Grupo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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

        /* Formulário */
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 600px;
        }

        form div {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
        }

        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #4f545c;
            border-radius: 4px;
            background-color: #40444b;
            color: #ffffff;
        }

        button[type="submit"] {
            background-color: #7289da;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            align-self: flex-start;
            margin-bottom: 30px; /* Adicione esta linha para criar espaço abaixo do botão */
        }

        button[type="submit"]:hover {
            background-color: #5b6eae;
        }

        /* Mensagens de sucesso e erro */
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .alert-success {
            background-color: #2ecc71;
            color: #ffffff;
        }

        .alert-danger {
            background-color: #e74c3c;
            color: #ffffff;
        }

        /* Estilos para os cartões */
        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 15px;
        }

        .card {
            background-color: #2f3136;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 20px;
            width: calc(33.333% - 20px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            color: #fff;
        }

        .card h3 {
            margin-top: 0;
            color: #fff;
        }

        .card p {
            color: #b0b0b0;
        }

        .user-count {
            display: flex;
            align-items: center;
            margin-top: 10px;
            color: #b0b0b0;
        }

        .user-count .icon {
            margin-right: 5px;
        }

        .user-count .count {
            font-weight: bold;
        }
    </style>
</head>
<body>
<nav>
    <ul>
        <li><a href="/sistema_produtividade/public/dashboard-diretor"><i class="fas fa-home icon"></i> Início</a></li>
        <li><a href="/sistema_produtividade/public/create-group"><i class="fas fa-plus-circle icon"></i> Criar Grupo</a></li>
        <li><a href="/sistema_produtividade/public/manage-groups"><i class="fas fa-users icon"></i> Gerenciar Grupos</a></li>
    </ul>
</nav>
<div class="main-content">
    <header>
        <h1>Atribuir Usuário a Grupo</h1>
        <div class="user-info">
            <span>Bem-vindo, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
            <a href="/sistema_produtividade/public/logout" class="btn-logout">Sair</a>
        </div>
    </header>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" id="success-message">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <form action="/sistema_produtividade/public/assign-user-group" method="post">
        <div>
            <label for="group">Selecione o Grupo:</label>
            <select name="group_id" id="group" required>
                <?php foreach ($allGroups as $group): ?>
                    <option value="<?= htmlspecialchars($group['id']) ?>"><?= htmlspecialchars($group['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="user">Selecione o Usuário:</label>
            <select name="user_id" id="user" required>
                <?php foreach ($allUsers as $user): ?>
                    <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Atribuir Usuário ao Grupo</button>
    </form>

    <h2>Grupos Existentes</h2>
    <div class="summary-cards">
        <?php foreach ($allGroups as $group): ?>
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