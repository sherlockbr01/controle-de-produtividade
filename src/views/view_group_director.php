<?php
use Jti30\SistemaProdutividade\Controllers\DiretorController;
use Jti30\SistemaProdutividade\Controllers\AuthController;

// Initialize necessary variables
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$authController = new AuthController($pdo);
$authController->requireDirectorAuth();

// Instantiate the DiretorController
$diretorController = new DiretorController($pdo, $authController);

// Get group ID from URL parameter
$groupId = $_GET['id'] ?? null;

if (!$groupId) {
    die("ID do grupo não fornecido.");
}

// Get group data
$groupData = $diretorController->getGroupDetails($groupId);

// Calcule os totais do grupo
$totalPoints = 0;
$totalProcesses = 0;
if ($groupData && isset($groupData['users'])) {
    foreach ($groupData['users'] as $user) {
        $totalPoints += $user['points'] ?? 0;
        $totalProcesses += $user['completed_processes'] ?? 0;
    }
}
$diretorController = new DiretorController($pdo, $authController);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Grupo</title>
    <style>
        /* Reset e estilos gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #36393f;
            color: #ffffff;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #2f3136;
            padding: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar a {
            display: block;
            color: #ecf0f1;
            text-decoration: none;
            margin-bottom: 15px;
            font-size: 1.1em;
            transition: color 0.3s;
        }

        .sidebar a:hover {
            color: #7289da;
        }

        /* Cabeçalho */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            background-color: #36393f;
            padding: 20px;
            z-index: 1000;
        }

        .header h1 {
            font-size: 24px;
            color: #ffffff;
            margin: 0;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .user-info {
            margin-left: auto;
        }

        .user-info span {
            margin-right: 10px;
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

        /* Conteúdo principal */
        .main-content {
            margin-left: 250px;
            padding: 80px 20px 20px;
            flex-grow: 1;
            position: relative;
        }

        /* Estilos para os cartões */
        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
            justify-content: center;
        }

        .card {
            background-color: #2f3136;
            padding: 15px;
            border-radius: 8px;
            width: calc(33.33% - 20px);
            min-width: 250px;
            max-width: 350px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
        }

        .total-summary-cards {
            display: flex;
            gap: 37px;
            justify-content: center;
            margin-top: 20px;
        }

        .total-points-card, .total-processes-card {
            width: 50%;
            max-width: 400px;
            margin: 0 auto;
            background-color: #2f3136;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .total-points-card h3, .total-processes-card h3 {
            color: #ffffff;
            margin-bottom: 10px;
        }

        .total-points-card .big-number, .total-processes-card .big-number {
            font-size: 24px;
            color: #7289da;
        }

        @media (max-width: 768px) {
            .card {
                flex: 1 1 calc(50% - 10px);
            }
        }

        @media (max-width: 480px) {
            .card {
                flex: 1 1 100%;
            }
        }

        .card-content {
            display: flex;
            justify-content: space-between;
            background-color: #3a3b3c;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .card-section {
            width: 48%;
            text-align: center;
        }

        .card h3 {
            color: #ffffff;
            margin-bottom: 10px;
        }

        .big-number {
            font-size: 24px;
            color: #7289da;
        }

        .member-name {
            font-size: 18px;
            color: #ffffff;
            margin-bottom: 5px;
        }

        .section-title {
            font-size: 20px;
            margin-bottom: 10px;
            color: #7289da;
            text-align: center;
        }

        .group-card {
            background-color: #2f3136;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .group-card h2 {
            margin-bottom: 10px;
            color: #ffffff;
        }

        .group-card p {
            color: #ecf0f1;
        }

        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-top: 20px;
            font-size: 18px;
        }

        .btn-remove {
            background-color: #e74c3c;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-top: 10px;
            display: inline-block;
            width: auto;
        }

        .btn-remove:hover {
            background-color: #c0392b;
        }

        .success-message {
            background-color: #28a745;
            color: #ffffff;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #2f3136;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 300px;
            text-align: center;
        }

        .modal-buttons {
            margin-top: 20px;
        }

        .modal-buttons button {
            margin: 0 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        #confirmYes {
            background-color: #e74c3c;
            color: white;
        }

        #confirmNo {
            background-color: #7289da;
            color: white;
        }
    </style>
<body>
<div class="sidebar">
    <a href="/sistema_produtividade/public/dashboard-diretor">Início</a>
    <a href="/sistema_produtividade/public/manage-groups">Gerenciar Grupos</a>
    <a href="/sistema_produtividade/public/create-group">Criar Grupo</a>
    <a href="/sistema_produtividade/public/assign-user-group">Atribuir Usuário a Grupo</a>
</div>
<div class="main-content">
    <div class="header">
        <h1>Visualizar Grupo</h1>
        <div class="user-info">
            <span>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>!</span>
            <a href="/sistema_produtividade/public/logout" class="btn-logout">Sair</a>
        </div>
    </div>

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
            // Redirecionar para a página de remoção do usuário
            window.location.href = `/sistema_produtividade/public/remove-user-from-group?user_id=${currentUserId}&group_id=${currentGroupId}`;
        });

        btnNo.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        // Fecha o modal ao clicar fora dele
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>