<?php
use Jti30\SistemaProdutividade\Controllers\RelatorioController;

require_once __DIR__ . '/../../vendor/autoload.php';

$relatorioController = new RelatorioController($pdo, $authController);

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$selectedUserId = $_GET['user_id'] ?? null;

$pageData = $relatorioController->getDadosPagina($startDate, $endDate, $selectedUserId);

$pageTitle = "Relatório Detalhado de Produtividade";

// Define os itens do menu
$menuItems = [
    ['url' => '/sistema_produtividade/public/dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => '/sistema_produtividade/public/manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => '/sistema_produtividade/public/relatorio-detalhado', 'icon' => 'fas fa-tools', 'text' => 'Gerar Relatórios'],
    ['url' => '/sistema_produtividade/public/gerenciar-ferias-afastamento', 'icon' => 'fas fa-calendar-alt', 'text' => 'Gerenciar Férias e Afastamentos']
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="/sistema_produtividade/public/css/relatorio.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/sidebar.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/header.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../compnents/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../compnents/header.php'; ?>

        <div class="content-wrapper">
            <div class="filter-form">
                <h2>Gerar Relatório de Produtividade</h2>
                <form action="" method="GET">
                    <div class="form-group">
                        <label for="user_id">Selecionar Usuário:</label>
                        <select name="user_id" id="user_id">
                            <?php foreach ($pageData['users'] as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo ($pageData['selectedUserId'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="report_type">Tipo de Relatório:</label>
                        <select name="report_type" id="report_type">
                            <option value="default" <?php echo ($pageData['reportType'] == 'default') ? 'selected' : ''; ?>>Padrão</option>
                            <option value="created_at" <?php echo ($pageData['reportType'] == 'created_at') ? 'selected' : ''; ?>>Por Data de Criação</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Data Inicial:</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $pageData['startDate']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="end_date">Data Final:</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $pageData['endDate']; ?>" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn-gerar-relatorio">Gerar Relatório</button>
                    </div>
                </form>
            </div>

            <div class="report-content">
                <?php if ($pageData['relatorioData']): ?>
                    <div class="report-results">
                        <h3>
                            Relatório de Produtividade para
                            <?php
                            $userName = htmlspecialchars($pageData['userName'] ?? '');
                            echo $userName ? "$userName - " : '';
                            echo date('d/m/Y', strtotime($pageData['startDate'])) . ' a ' . date('d/m/Y', strtotime($pageData['endDate']));
                            ?>
                        </h3>

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
                    </div>
                <?php else: ?>
                    <div class="no-report">
                        <p>Selecione os filtros e clique em "Gerar Relatório" para visualizar os dados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>