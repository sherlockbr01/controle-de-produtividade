<?php
use Jti30\SistemaProdutividade\Controllers\ServidorController;
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

// Initialize necessary variables
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';

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

$menuItems = [
    ['url' => 'dashboard-servidor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'registrar-produtividade', 'icon' => 'fas fa-clipboard-list', 'text' => 'Registrar Produtividade'],
    ['url' => 'meu-grupo', 'icon' => 'fas fa-users', 'text' => 'Meu Grupo'],
    ['url' => 'gestao-ferias-afastamentos', 'icon' => 'fas fa-calendar-alt', 'text' => 'Férias e Afastamentos']
];

$pageTitle = "Meu Grupo";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/view_my_group.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

        <?php if (!$groupData || !isset($groupData['group'])): ?>
            <div class="error-message">Você não está associado a nenhum grupo no momento.</div>
        <?php else: ?>
            <div class="group-card">
                <h2 style="text-align: left;">Grupo: <?php echo htmlspecialchars($groupData['group']['name'] ?? 'N/A'); ?></h2>
                <p class="group-description"><strong>Descrição:</strong> <?php echo htmlspecialchars($groupData['group']['description'] ?? 'N/A'); ?></p>
            </div>

            <div class="members-section">
                <h2 class="members-title">Membros do Grupo</h2>
                <div class="members-cards">
                    <?php foreach ($groupData['users'] as $member): ?>
                        <div class="member-card">
                            <div class="member-name"><?php echo htmlspecialchars($member['name']); ?></div>
                            <div class="member-stats">
                                <div class="stat-item">
                                    <div class="stat-label">Pontos Acumulados</div>
                                    <div class="stat-value"><?php echo number_format($member['points']); ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">Processos Concluídos</div>
                                    <div class="stat-value"><?php echo number_format($member['completed_processes']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="group-totals">
                    <div class="total-card">
                        <div class="total-label">Pontos Totais do Grupo</div>
                        <div class="total-value"><?php echo number_format($totalPoints); ?></div>
                    </div>
                    <div class="total-card">
                        <div class="total-label">Processos Totais do Grupo</div>
                        <div class="total-value"><?php echo number_format($totalProcesses); ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>