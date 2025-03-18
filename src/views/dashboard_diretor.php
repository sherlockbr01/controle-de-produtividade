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
            $basePath = substr($basePath, 0, strpos($basePath, '/public'));
        }

        return $basePath;
    }

    define('BASE_URL', getBaseUrl());
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'diretor') {
    header('Location: ' . BASE_URL . '/');
    exit;
}

use Jti30\SistemaProdutividade\Controllers\DiretorController;

// Certifique-se de que o autoload do Composer está sendo incluído
require_once __DIR__ . '/../../vendor/autoload.php';

// Verifique se as variáveis $pdo e $authController estão definidas
// $pdo = new PDO(...); // Inicialize a conexão com o banco de dados
// $authController = new AuthController($pdo); // Inicialize o controlador de autenticação

// Definir os itens de menu para esta página
$menuItems = [
    ['url' => 'dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => 'relatorio-detalhado', 'icon' => 'fas fa-chart-bar', 'text' => 'Relatórios'],
    ['url' => 'gerenciar-ferias-afastamento', 'icon' => 'fas fa-calendar-alt', 'text' => 'Gerenciar Férias e Afastamentos']
];

// Lógica para buscar os dados de produtividade
$diretorController = new DiretorController($pdo, $authController);
$dashboardData = $diretorController->dashboard();

// Definir o título da página
$pageTitle = "Dashboard do Administrador";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

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
                <h2>Servidores mais produtivos</h2>
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
                    if (isset($dashboardData['topServers']) && is_array($dashboardData['topServers'])) {
                        foreach ($dashboardData['topServers'] as $server):
                            $name = htmlspecialchars($server['name'] ?? 'N/A');
                            $points = isset($server['points']) ? number_format($server['points']) : 'N/A';
                            $processes = isset($server['processes']) ? number_format($server['processes']) : 'N/A';
                            $efficiency = isset($server['efficiency']) ? number_format($server['efficiency'], 2) . '%' : 'N/A';
                            ?>
                            <tr>
                                <td><?php echo $name; ?></td>
                                <td><?php echo $points; ?></td>
                                <td><?php echo $processes; ?></td>
                                <td><?php echo $efficiency; ?></td>
                            </tr>
                        <?php endforeach;
                    } else {
                        echo "<tr><td colspan='4'>Nenhum dado de servidor disponível.</td></tr>";
                    }
                    ?>
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