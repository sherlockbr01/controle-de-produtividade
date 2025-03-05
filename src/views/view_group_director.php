<?php
use Jti30\SistemaProdutividade\Controllers\DiretorController;
use Jti30\SistemaProdutividade\Controllers\AuthController;

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
    ['url' => '/sistema_produtividade/public/dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => '/sistema_produtividade/public/manage-groups', 'icon' => 'fas fa-users-cog', 'text' => 'Gerenciar Grupos'],
    ['url' => '/sistema_produtividade/public/create-group', 'icon' => 'fas fa-plus-circle', 'text' => 'Criar Grupo'],
    ['url' => '/sistema_produtividade/public/assign-user-group', 'icon' => 'fas fa-user-plus', 'text' => 'Atribuir Usuário a Grupo']
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/view_group_director.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/sidebar.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/header.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../compnents/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../compnents/header.php'; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message" id="success-message">
                <?= htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!$groupData || !isset($groupData['group'])): ?>
            <div class="error-message">Grupo não encontrado.</div>
        <?php else: ?>
            <div class="group-card">
                <h2>Grupo: <?php echo htmlspecialchars($groupData['group']['name'] ?? 'N/A'); ?></h2>
                <p><strong>Descrição:</strong> <?php echo htmlspecialchars($groupData['group']['description'] ?? 'N/A'); ?></p>
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
    // Faz a mensagem de sucesso desaparecer após 5 segundos
    setTimeout(function() {
        var successMessage = document.getElementById('success-message');
        if (successMessage) {
            successMessage.style.display = 'none';
        }
    }, 5000);

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
            window.location.href = `/sistema_produtividade/public/remove-user-from-group?user_id=${currentUserId}&group_id=${currentGroupId}`;
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