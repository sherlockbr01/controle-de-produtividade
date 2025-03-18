<?php
use Jti30\SistemaProdutividade\Controllers\RelatorioController;

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

// Certifique-se de que as variáveis $pdo e $authController estão definidas
global $pdo, $authController;

$relatorioController = new RelatorioController($pdo, $authController);

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$selectedUserId = $_GET['user_id'] ?? null;
$reportType = $_GET['report_type'] ?? 'productivity';

$pageData = $relatorioController->getDadosPagina($startDate, $endDate, $selectedUserId, $reportType);

$pageTitle = "Relatório Detalhado";

// Define os itens do menu
$menuItems = [
    ['url' => 'dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => 'relatorio-detalhado', 'icon' => 'fas fa-tools', 'text' => 'Gerar Relatórios'],
    ['url' => 'gerenciar-ferias-afastamento', 'icon' => 'fas fa-calendar-alt', 'text' => 'Gerenciar Férias e Afastamentos']
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/relatorio.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

        <div class="content-wrapper">
            <div class="filter-form">
                <h2>Filtro de relatórios</h2>
                <form action="<?php echo BASE_URL; ?>/relatorio-detalhado" method="GET" id="reportForm">
                    <div class="form-group">
                        <label for="user_id">Selecionar Usuário:</label>
                        <select name="user_id" id="user_id">
                            <?php foreach ($pageData['users'] as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo ($selectedUserId == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="report_type">Tipo de Relatório:</label>
                        <select name="report_type" id="report_type">
                            <option value="productivity" <?php echo ($reportType == 'productivity') ? 'selected' : ''; ?>>Produtividade</option>
                            <option value="decisions" <?php echo ($reportType == 'decisions') ? 'selected' : ''; ?>>Decisões</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Data Inicial:</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">Data Final:</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-gerar-relatorio">Gerar Relatório</button>
                    </div>
                </form>
            </div>

            <div class="report-content">
                <?php if (isset($pageData['relatorioData'])): ?>
                    <?php if ($reportType == 'productivity'): ?>
                        <div id="productivity-report" class="report-results">
                            <h3>
                                Relatório de Produtividade para
                                <?php
                                $userName = htmlspecialchars($pageData['userName'] ?? '');
                                echo $userName ? "$userName - " : '';
                                echo date('d/m/Y', strtotime($startDate)) . ' a ' . date('d/m/Y', strtotime($endDate));
                                ?>
                            </h3>

                            <?php if (isset($pageData['relatorioData']['productivity_details']) && !empty($pageData['relatorioData']['productivity_details'])): ?>
                                <div class="report-summary">
                                    <div class="summary-item">
                                        <label>Total de Pontos:</label>
                                        <p class="big-number"><?php echo $pageData['relatorioData']['total_points'] ?? 0; ?></p>
                                    </div>
                                    <div class="summary-item">
                                        <label>Total de Processos:</label>
                                        <p class="big-number"><?php echo $pageData['relatorioData']['total_processes'] ?? 0; ?></p>
                                    </div>
                                    <div class="summary-item">
                                        <label>Média de Pontos por Processo:</label>
                                        <p class="big-number"><?php echo number_format($pageData['relatorioData']['average_points_per_process'] ?? 0, 2); ?></p>
                                    </div>
                                </div>

                                <table class="report-table">
                                    <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo de Minuta</th>
                                        <th>Tipo de Decisão</th>
                                        <th>Pontos</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($pageData['relatorioData']['productivity_details'] as $detail): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($detail['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($detail['minute_type_name']); ?></td>
                                            <td><?php echo htmlspecialchars($detail['decision_type_name']); ?></td>
                                            <td><?php echo $detail['points']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="no-data-message">
                                    <p>Não existem dados para o período selecionado.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($reportType == 'decisions'): ?>
                        <div id="decisions-report" class="report-results">
                            <h3>
                                Relatório de Decisões para
                                <?php
                                $userName = htmlspecialchars($pageData['userName'] ?? '');
                                echo $userName ? "$userName - " : '';
                                echo date('d/m/Y', strtotime($startDate)) . ' a ' . date('d/m/Y', strtotime($endDate));
                                ?>
                            </h3>

                            <?php if (isset($pageData['relatorioData']['decision_types']) &&
                                !empty($pageData['relatorioData']['decision_types']) &&
                                $pageData['relatorioData']['overall_stats']['total_count'] > 0): ?>
                                <div class="report-summary">
                                    <div class="summary-item">
                                        <label>Total de Decisões:</label>
                                        <p class="big-number"><?php echo $pageData['relatorioData']['overall_stats']['total_count'] ?? 0; ?></p>
                                    </div>
                                    <div class="summary-item">
                                        <label>Total de Pontos:</label>
                                        <p class="big-number"><?php echo $pageData['relatorioData']['overall_stats']['total_points'] ?? 0; ?></p>
                                    </div>
                                    <div class="summary-item">
                                        <label>Média de Pontos por Decisão:</label>
                                        <p class="big-number"><?php echo number_format($pageData['relatorioData']['overall_stats']['average_points'] ?? 0, 2); ?></p>
                                    </div>
                                </div>

                                <table class="report-table">
                                    <thead>
                                    <tr>
                                        <th>Tipo de Decisão</th>
                                        <th>Quantidade</th>
                                        <th>Percentual</th>
                                        <th>Total de Pontos</th>
                                        <th>Média de Pontos</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($pageData['relatorioData']['decision_types'] as $decisionType): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($decisionType['name']); ?></td>
                                            <td><?php echo $decisionType['count']; ?></td>
                                            <td><?php echo number_format($decisionType['percentage'], 2); ?>%</td>
                                            <td><?php echo $decisionType['total_points']; ?></td>
                                            <td><?php echo number_format($decisionType['average_points'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="no-data-message">
                                    <p>Não existem dados para o período selecionado.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-report">
                        <p>Selecione os filtros e clique em "Gerar Relatório" para visualizar os dados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Adiciona o evento de submissão apenas ao botão "Gerar Relatório"
        const form = document.querySelector('form');
        form.addEventListener('submit', function(event) {
            // O formulário será submetido normalmente quando o botão for clicado
        });
    });
</script>
</body>
</html>