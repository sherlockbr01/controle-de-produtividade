<?php
use Jti30\SistemaProdutividade\Controllers\DiretorController;
use Jti30\SistemaProdutividade\Controllers\AuthController;

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

require_once __DIR__ . '/../../vendor/autoload.php';

$authController = new AuthController($pdo);
$authController->requireDirectorAuth();

$diretorController = new DiretorController($pdo, $authController);

$groupId = $_GET['id'] ?? null;

if (!$groupId) {
    die("ID do grupo não fornecido.");
}

$groupData = $diretorController->getGroupDetails($groupId);

$totalPoints = 0;
$totalProcesses = 0;
if ($groupData && isset($groupData['users'])) {
    foreach ($groupData['users'] as $user) {
        $totalPoints += $user['points'] ?? 0;
        $totalProcesses += $user['completed_processes'] ?? 0;
    }
}

$pageTitle = "Visualizar Grupo";
$userName = $_SESSION['user_name'] ?? '';

// Define os itens do menu
$menuItems = [
    ['url' => 'dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => 'create-group', 'icon' => 'fas fa-plus-circle', 'text' => 'Criar Grupo'],
    ['url' => 'assign-user-group', 'icon' => 'fas fa-user-plus', 'text' => 'Atribuir Usuário a Grupo'],
    ['url' => 'relatorio-detalhado', 'icon' => 'fas fa-chart-bar', 'text' => 'Relatórios'],
    ['url' => 'gerenciar-ferias-afastamento', 'icon' => 'fas fa-calendar-alt', 'text' => 'Férias e Afastamentos']
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/view_group_director.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

        <?php if (!$groupData || !isset($groupData['group'])): ?>
            <div class="error-message">Grupo não encontrado.</div>
        <?php else: ?>
            <div class="group-card">
                <h2>Grupo: <?php echo htmlspecialchars($groupData['group']['name'] ?? 'N/A'); ?></h2>
                <p><strong>Descrição:</strong> <?php echo htmlspecialchars($groupData['group']['description'] ?? 'N/A'); ?></p>
            </div>

            <!-- Exibir mensagens de sucesso ou erro -->
            <div class="alert-container" style="margin-top: 20px; max-width: 400px; margin-left: auto; margin-right: auto;">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <p class="alert success-message" style="color:#28a745; padding:10px; margin:0; border-radius:4px; background-color:rgba(40,167,69,0.1); text-align:center; font-size: 14px;">
                        <?php echo $_SESSION['success_message']; ?>
                    </p>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <p class="alert error-message" style="color:#dc3545; padding:10px; margin:0; border-radius:4px; background-color:rgba(220,53,69,0.1); text-align:center; font-size: 14px;">
                        <?php echo $_SESSION['error_message']; ?>
                    </p>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
            </div>

            <h3 class="section-title">Membros do Grupo</h3>
            <div class="summary-cards">
                <?php if (isset($groupData['users']) && is_array($groupData['users'])): ?>
                    <?php foreach ($groupData['users'] as $user): ?>
                        <div class="card">
                            <div class="member-name"><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></div>
                            <div class="card-content">
                                <div class="card-section">
                                    <h3>Pontos Acumulados</h3>
                                    <p class="big-number"><?php echo number_format($user['points'] ?? 0); ?></p>
                                </div>
                                <div class="card-section">
                                    <h3>Processos Concluídos</h3>
                                    <p class="big-number"><?php echo number_format($user['completed_processes'] ?? 0); ?></p>
                                </div>
                            </div>
                            <div class="btn-container">
                                <button class="btn-remove" data-user-id="<?php echo $user['id']; ?>" data-group-id="<?php echo $groupData['group']['id']; ?>">Remover do Grupo</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="error-message">Nenhum membro encontrado neste grupo.</div>
                <?php endif; ?>
            </div>

            <div class="total-summary-cards">
                <div class="card total-points-card">
                    <h3>Pontos Totais do Grupo</h3>
                    <p class="big-number"><?php echo number_format($totalPoints); ?></p>
                </div>
                <div class="card total-processes-card">
                    <h3>Processos Totais do Grupo</h3>
                    <p class="big-number"><?php echo number_format($totalProcesses); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmação -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <p>Deseja realmente remover o usuário do grupo?</p>
        <div class="modal-buttons">
            <button id="confirmYes">Sim</button>
            <button id="confirmNo">Não</button>
        </div>
    </div>
</div>

<script>
    // Função para fazer a mensagem desaparecer
    function fadeOutMessage(messageElement) {
        var opacity = 1;
        var timer = setInterval(function() {
            if (opacity <= 0.1) {
                clearInterval(timer);
                messageElement.style.display = 'none';
            }
            messageElement.style.opacity = opacity;
            opacity -= opacity * 0.1;
        }, 50);
    }

    // Seleciona todas as mensagens
    var messages = document.querySelectorAll('.success-message, .error-message');

    // Para cada mensagem, configura um temporizador para fazê-la desaparecer
    messages.forEach(function(message) {
        setTimeout(function() {
            fadeOutMessage(message);
        }, 7000); // 7000 milissegundos = 7 segundos
    });

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('confirmModal');
        const btnsRemove = document.querySelectorAll('.btn-remove');
        const btnYes = document.getElementById('confirmYes');
        const btnNo = document.getElementById('confirmNo');
        let currentUserId, currentGroupId;

        btnsRemove.forEach(btn => {
            btn.addEventListener('click', function() {
                currentUserId = this.getAttribute('data-user-id');
                currentGroupId = this.getAttribute('data-group-id');
                modal.style.display = 'block';
            });
        });

        btnYes.addEventListener('click', function() {
            window.location.href = `<?php echo BASE_URL; ?>/remove-user-from-group?user_id=${currentUserId}&group_id=${currentGroupId}`;
        });

        btnNo.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>