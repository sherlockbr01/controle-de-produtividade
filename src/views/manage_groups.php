<?php
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

use Jti30\SistemaProdutividade\Controllers\GroupController;

// Verifique se o usuário está autenticado como diretor
$authController->requireDirectorAuth();

$groupController = new GroupController($pdo, $authController);

// Obtenha todos os grupos
$allGroups = $groupController->getAllGroups();

// Definir os itens de menu para esta página
$menuItems = [
    ['url' => 'dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'create-group', 'icon' => 'fas fa-plus-circle', 'text' => 'Criar Grupo'],
    ['url' => 'manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => 'assign-user-group', 'icon' => 'fas fa-user-plus', 'text' => 'Atribuir Usuário a Grupo'],
    ['url' => 'relatorio-detalhado', 'icon' => 'fas fa-chart-bar', 'text' => 'Relatórios'],
    ['url' => 'gerenciar-ferias-afastamento', 'icon' => 'fas fa-calendar-alt', 'text' => 'Férias e Afastamentos']
];
// Definir o título da página
$pageTitle = "Gerenciar Grupos";

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/manage_groups.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

        <!-- Exibir mensagens de sucesso ou erro -->
        <div class="alert-container" style="display: flex; justify-content: center; align-items: center; min-height: 60px; margin-top: 20px;">
            <?php if (isset($_SESSION['success_message'])): ?>
                <p class="success-message" style="color:#28a745; padding:10px 20px; margin:0; border-radius:4px; font-weight:bold; background-color:rgba(40,167,69,0.1); text-align:center; display:inline-block;">
                    <?php echo $_SESSION['success_message']; ?>
                </p>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <p class="error-message" style="color:#dc3545; padding:10px 20px; margin:0; border-radius:4px; font-weight:bold; background-color:rgba(220,53,69,0.1); text-align:center; display:inline-block;">
                    <?php echo $_SESSION['error_message']; ?>
                </p>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
        </div>

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
                                <a href="<?php echo BASE_URL; ?>/visualizar-grupo-diretor?id=<?php echo htmlspecialchars($group['id']); ?>" class="btn btn-view">Ver Grupo</a>
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
    // Faz a mensagem de sucesso ou erro desaparecer após 7 segundos
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            var successMessage = document.querySelector('.success-message');
            var errorMessage = document.querySelector('.error-message');
            if (successMessage) {
                successMessage.style.display = 'none';
            }
            if (errorMessage) {
                errorMessage.style.display = 'none';
            }
        }, 7000);
    });

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
            form.action = '<?php echo BASE_URL; ?>/delete-group';
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