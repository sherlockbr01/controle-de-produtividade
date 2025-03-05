<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'diretor') {
    header('Location: /sistema_produtividade/public/');
    exit;
}

use Jti30\SistemaProdutividade\Controllers\DiretorController;

// Certifique-se de que o autoload do Composer está sendo incluído
require_once __DIR__ . '/../../vendor/autoload.php';

// Verifique se as variáveis $pdo e $authController estão definidas
// $pdo = new PDO(...); // Inicialize a conexão com o banco de dados
// $authController = new AuthController($pdo); // Inicialize o controlador de autenticação

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'diretor') {
    header('Location: /sistema_produtividade/public/');
    exit;
}
// Definir os itens de menu para esta página
$menuItems = [
    [
        'url' => '/sistema_produtividade/public/dashboard-diretor',
        'icon' => 'fas fa-tachometer-alt',
        'text' => 'Dashboard'
    ],
    [
        'url' => '/sistema_produtividade/public/manage-groups',
        'icon' => 'fas fa-users',
        'text' => 'Gerenciar Grupos'
    ],
    [
        'url' => '/sistema_produtividade/public/generate-reports',
        'icon' => 'fas fa-tools',
        'text' => 'Gerar Relatórios'
    ],
    [
        'url' => '/sistema_produtividade/public/gerenciar-ferias-afastamento',
        'icon' => 'fas fa-calendar-alt',
        'text' => 'Gerenciar Férias e Afastamentos'
    ]
];

// Lógica para buscar os dados de produtividade
$diretorController = new DiretorController($pdo, $authController);
$dashboardData = $diretorController->dashboard();


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do coordenador - Sistema de Produtividade</title>
    <link rel="stylesheet" href="/sistema_produtividade/public/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <a href="/sistema_produtividade/public/manage-groups">
            <i class="fas fa-users"></i> Gerenciar Grupos
        </a>
        <a href="/sistema_produtividade/public/generate-reports">
            <i class="fas fa-tools"></i> Gerar Relatórios
        </a>
        <a href="/sistema_produtividade/public/gerenciar-ferias-afastamento">
            <i class="fas fa-calendar-alt"></i> Gerenciar Férias e Afastamentos
        </a>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Dashboard do coordenador</h1>
            <div class="user-info">
                <span>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
                <a href="/sistema_produtividade/public/logout" class="btn-logout">Sair</a>
            </div>
        </div>

        <main class="dashboard-content">
            <section class="productivity-summary">
                <h2>Resumo de Produtividade</h2>
                <div class="summary-cards">
                    <div class="card">
                        <h3>Total de Pontos</h3>
                        <p class="big-number"><?php echo number_format($dashboardData['totalPoints']); ?></p>
                    </div>
                    <div class="card">
                        <h3>Total de Processos</h3>
                        <p class="big-number"><?php echo number_format($dashboardData['totalProcesses']); ?></p>
                    </div>
                    <div class="card">
                        <h3>Eficiência Média</h3>
                        <p class="big-number"><?php echo number_format($dashboardData['averageEfficiency'], 2); ?>%</p>
                    </div>
                </div>
            </section>

            <section class="group-productivity">
                <h2>Produtividade por Grupo</h2>
                <canvas id="groupProductivityChart"></canvas>
            </section>

            <section class="top-servers">
                <h2>Top 5 Servidores Mais Produtivos</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Pontos</th>
                        <th>Processos</th>
                        <th>Eficiência</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $topServers = array_slice($dashboardData['topServers'], 0, 5);
                    foreach ($topServers as $server):
                        $points = isset($server['points']) ? number_format($server['points']) : 'N/A';
                        $processes = isset($server['processes']) ? number_format($server['processes']) : 'N/A';
                        $efficiency = isset($server['efficiency']) ? number_format($server['efficiency'], 2) . '%' : 'N/A';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($server['name']); ?></td>
                            <td><?php echo $points; ?></td>
                            <td><?php echo $processes; ?></td>
                            <td><?php echo $efficiency; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>

<script>
    // Gráfico de Produtividade por Grupo
    var ctx = document.getElementById('groupProductivityChart').getContext('2d');
    var groupProductivityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($dashboardData['groupProductivity'], 'group_name')); ?>,
            datasets: [{
                label: 'Pontos de Produtividade',
                data: <?php echo json_encode(array_column($dashboardData['groupProductivity'], 'total_points')); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>
</body>
</html>