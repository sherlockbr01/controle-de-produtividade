<?php
use Jti30\SistemaProdutividade\Controllers\ServidorController;
use Jti30\SistemaProdutividade\Controllers\AuthController;

// Initialize necessary variables
require_once __DIR__ . '/../config/database.php'; // Ensure this file sets up $pdo
require_once __DIR__ . '/../controllers/AuthController.php'; // Ensure this file sets up AuthController

$authController = new AuthController($pdo);
$authController->requireAuth();

// Instantiate the ServidorController
$servidorController = new ServidorController($pdo, $authController);

// Get group data
$groupData = $servidorController->getAssignedGroup($_SESSION['user_id']);

$totalPoints = 0;
$totalProcesses = 0;

if ($groupData && isset($groupData['users'])) {
    foreach ($groupData['users'] as $user) {
        $totalPoints += $user['points'] ?? 0;
        $totalProcesses += $user['completed_processes'] ?? 0;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Grupo</title>
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
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #ffffff;
        }

        .user-info {
            position: absolute;
            right: 20px;
            display: flex;
            align-items: center;
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
            padding: 20px;
            flex-grow: 1;
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
            width: calc(33.33% - 20px); /* Ajusta para 3 cartões por linha com espaçamento */
            min-width: 250px; /* Largura mínima para evitar cartões muito estreitos */
            max-width: 350px; /* Largura máxima para manter consistência */
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
        }

        .total-summary-cards {
            display: flex;
            gap: 37px; /* Ajuste o espaçamento aqui */
            justify-content: center;
            margin-top: 20px;
        }

        .total-points-card, .total-processes-card {
            width: 50%; /* Ajusta a largura do cartão */
            max-width: 400px; /* Define uma largura máxima */
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
    </style>
</head>
<body>
<div class="sidebar">
    <a href="/sistema_produtividade/public/inicio">Início</a>
    <a href="/sistema_produtividade/public/registrar-produtividade">Registrar Produtividade</a>
    <a href="/sistema_produtividade/public/informar-férias">Informar Férias</a>
</div>
<div class="main-content">
    <div class="header">
        <h1>Meu Grupo</h1>
        <div class="user-info">
            <span>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>!</span>
            <a href="/sistema_produtividade/public/logout" class="btn-logout">Sair</a>
        </div>
    </div>


    <?php if (!$groupData || !isset($groupData['group'])): ?>
        <div class="error-message">Você não está associado a nenhum grupo no momento.</div>
    <?php else: ?>
        <div class="group-card">
            <h2 style="text-align: left;">Grupo: <?php echo htmlspecialchars($groupData['group']['name'] ?? 'N/A'); ?></h2>
            <p class="group-description"><strong>Descrição:</strong> <?php echo htmlspecialchars($groupData['group']['description'] ?? 'N/A'); ?></p>
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
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="error-message">Nenhum membro encontrado neste grupo.</div>
            <?php endif; ?>
        </div>

        <!-- Cards for total group points and processes -->
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
</body>
</html>