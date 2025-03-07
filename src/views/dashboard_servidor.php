<?php
use Jti30\SistemaProdutividade\Controllers\ServidorController;

// Instanciar o ServidorController
$servidorController = new ServidorController($pdo, $authController);

// Verificar se o usuário pertence a um grupo
$userGroup = $servidorController->getAssignedGroup($_SESSION['user_id']);
$hasGroup = !empty($userGroup);

// Definir os itens de menu para esta página
$menuItems = [
    ['url' => '/sistema_produtividade/public/dashboard-servidor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => '/sistema_produtividade/public/registrar-produtividade', 'icon' => 'fas fa-clipboard-list', 'text' => 'Registrar Produtividade'],
    ['url' => '/sistema_produtividade/public/meu-grupo', 'icon' => 'fas fa-users', 'text' => 'Meu Grupo', 'data-has-group' => $hasGroup ? 'true' : 'false'],
    ['url' => '/sistema_produtividade/public/gestao-ferias-afastamentos', 'icon' => 'fas fa-calendar-alt', 'text' => 'Férias e Afastamentos']
];

// Definir o título da página
$pageTitle = "Dashboard do Servidor";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Sistema de Produtividade</title>
    <link rel="stylesheet" href="/sistema_produtividade/public/css/dashboard.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/sidebar.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../compnents/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../compnents/header.php'; ?>

        <main class="dashboard">
            <section class="dashboard-summary">
                <h2>Resumo de Produtividade</h2>
                <div class="summary-cards">
                    <div class="card">
                        <h3>Pontos Acumulados</h3>
                        <p class="big-number"><?php echo number_format($dashboardData['totalPoints'] ?? 0); ?></p>
                    </div>
                    <div class="card">
                        <h3>Processos Concluídos</h3>
                        <p class="big-number"><?php echo number_format($dashboardData['completedProcesses'] ?? 0); ?></p>
                    </div>
                    <div class="card">
                        <h3>Eficiência</h3>
                        <p class="big-number"><?php echo number_format($dashboardData['efficiency'] ?? 0, 2); ?>%</p>
                    </div>
                </div>
            </section>

            <section class="dashboard-chart">
                <h2>Produtividade Mensal</h2>
                <div class="chart-container">
                    <canvas id="productivityChart"></canvas>
                </div>
            </section>

            <section class="dashboard-activities">
                <h2>Atividades Recentes</h2>
                <div class="activities-container">
                    <table id="activitiesTable">
                        <thead>
                        <tr>
                            <th>Processo</th>
                            <th>Tipo de Minuta</th>
                            <th>Tipo de Decisão</th>
                            <th>Pontos</th>
                            <th>Data</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dashboardData['recentActivities'] as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['process_number']); ?></td>
                                <td><?php echo htmlspecialchars($activity['minute_type']); ?></td>
                                <td><?php echo htmlspecialchars($activity['decision_type']); ?></td>
                                <td><?php echo htmlspecialchars($activity['points']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($activity['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="pagination" style="text-align: center; margin-top: 20px;">
                        <?php if ($dashboardData['currentPage'] > 1): ?>
                            <a href="?page=<?php echo $dashboardData['currentPage'] - 1; ?>" class="nav-link" style="display: inline-block; padding: 5px 15px; margin: 5px; background-color: #007bff; color: white; text-decoration: none; border-radius: 3px; font-size: 14px;">Anterior</a>
                        <?php endif; ?>
                        <a href="?page=<?php echo $dashboardData['currentPage'] + 1; ?>" class="nav-link" style="display: inline-block; padding: 5px 15px; margin: 5px; background-color: #007bff; color: white; text-decoration: none; border-radius: 3px; font-size: 14px;">Próximo</a>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
    var ctx = document.getElementById('productivityChart').getContext('2d');
    var productivityChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($dashboardData['monthlyProductivity'], 'month')); ?>,
            datasets: [{
                label: 'Pontos de Produtividade',
                data: <?php echo json_encode(array_column($dashboardData['monthlyProductivity'], 'points')); ?>,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Pontos'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Mês'
                    }
                }
            },
            plugins: {
                legend: {
                    display: true
                }
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        var meuGrupoLink = document.querySelector('a[href="/sistema_produtividade/public/meu-grupo"]');

        meuGrupoLink.addEventListener('click', function(e) {
            if (this.getAttribute('data-has-group') === 'false') {
                e.preventDefault();
                alert('Você não está em nenhum grupo. Entre em contato com o coordenador do sistema.');
            }
        });
    });
</script>

</body>
</html>