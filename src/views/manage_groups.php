<?php
use Jti30\SistemaProdutividade\Controllers\GroupController;

// Verifique se o usuário está autenticado como diretor
$authController->requireDirectorAuth();

$groupController = new GroupController($pdo, $authController);

// Obtenha todos os grupos
$allGroups = $groupController->getAllGroups();

// Definir os itens de menu para esta página
$menuItems = [
    ['url' => '/sistema_produtividade/public/dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => '/sistema_produtividade/public/create-group', 'icon' => 'fas fa-plus-circle', 'text' => 'Criar Grupo'],
    ['url' => '/sistema_produtividade/public/manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => '/sistema_produtividade/public/assign-user-group', 'icon' => 'fas fa-user-plus', 'text' => 'Atribuir Usuário a Grupo'],
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Grupos</title>
    <link rel="stylesheet" href="/sistema_produtividade/public/css/manage_groups.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/sidebar.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../compnents/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../compnents/header.php'; ?>

        <div class="content-wrapper">
            <h1>Gerenciar Grupos</h1>

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
                                <a href="/sistema_produtividade/public/visualizar-grupo-diretor?id=<?php echo htmlspecialchars($group['id']); ?>" class="btn btn-view">Ver Grupo</a>
                                <button class="btn btn-delete" onclick="confirmDelete(<?php echo htmlspecialchars($group['id']); ?>, '<?php echo htmlspecialchars($group['name']); ?>')">Excluir Grupo</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <h2>Confirmar Exclusão</h2>
        <p>Tem certeza que deseja excluir o grupo "<span id="groupName"></span>"?</p>
        <div class="modal-actions">
            <button id="confirmDelete" class="btn btn-delete">Sim, Excluir</button>
            <button id="cancelDelete" class="btn btn-cancel">Cancelar</button>
        </div>
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

    // Função para abrir o modal de confirmação
    function confirmDelete(groupId, groupName) {
        var modal = document.getElementById('confirmModal');
        var groupNameSpan = document.getElementById('groupName');
        var confirmButton = document.getElementById('confirmDelete');
        var cancelButton = document.getElementById('cancelDelete');

        groupNameSpan.textContent = groupName;
        modal.style.display = 'block';

        confirmButton.onclick = function() {
            // Enviar solicitação de exclusão
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '/sistema_produtividade/public/delete-group';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'group_id';
            input.value = groupId;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        };

        cancelButton.onclick = function() {
            modal.style.display = 'none';
        };

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };
    }
</script>
</body>
</html>