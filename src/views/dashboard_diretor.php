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

// Definir os itens de menu para esta página
$menuItems = [
    ['url' => 'dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => 'relatorio-detalhado', 'icon' => 'fas fa-chart-bar', 'text' => 'Relatórios'],
    ['url' => 'gerenciar-ferias-afastamento', 'icon' => 'fas fa-calendar-alt', 'text' => 'Gerenciar Férias e Afastamentos'],
    ['url' => 'register', 'icon' => 'fas fa-user-plus', 'text' => 'Cadastrar Usuário']
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
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

        <section class="monthly-productivity">
            <h2>Resumo de Produtividade Mensal</h2>
            <div class="year-selector">
                <label for="yearSelect">Ano:</label>
                <select id="yearSelect">
                    <?php
                    $currentYear = date('Y');
                    for ($year = 2020; $year <= 2050; $year++) {
                        $selected = $year == $currentYear ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="months-container">
                <?php
                $months = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
                $currentMonth = date('n');
                foreach ($months as $index => $month) {
                    $monthNumber = $index + 1;
                    $class = $monthNumber == $currentMonth ? 'current' : ($monthNumber < $currentMonth ? 'past' : 'future');
                    echo "<div class='month $class' data-month='$monthNumber'>$month</div>";
                }
                ?>
            </div>
            <div class="summary-cards">
                <div class="card">
                    <h3>Total de Pontos</h3>
                    <p class="big-number" id="totalPoints">-</p>
                </div>
                <div class="card">
                    <h3>Total de Processos</h3>
                    <p class="big-number" id="totalProcesses">-</p>
                </div>
            </div>
        </section>

        <section class="group-productivity">
            <h2>Produtividade por Grupo</h2>
            <div id="groupProductivityChart"></div>
        </section>
    </div>
</div>

<script>
    // Função para verificar se um array está vazio
    function isEmptyArray(arr) {
        return !Array.isArray(arr) || arr.length === 0;
    }

    // Dados do gráfico
    var groupNames = <?php echo json_encode(array_column($dashboardData['groupProductivity'] ?? [], 'group_name')); ?>;
    var groupPoints = <?php echo json_encode(array_column($dashboardData['groupProductivity'] ?? [], 'total_points')); ?>;

    // Verificar se há dados antes de renderizar o gráfico
    if (!isEmptyArray(groupNames) && !isEmptyArray(groupPoints)) {
        // Configurações do gráfico
        var options = {
            series: [{
                data: groupPoints
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    distributed: true,
                    dataLabels: {
                        position: 'top',
                    },
                }
            },
            dataLabels: {
                enabled: true,
                offsetX: -6,
                style: {
                    fontSize: '12px',
                    colors: ['#fff']
                }
            },
            xaxis: {
                categories: groupNames,
                labels: {
                    style: {
                        colors: '#fff'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#fff'
                    }
                }
            },
            colors: ['#008FFB', '#00E396', '#FEB019', '#FF4560', '#775DD0', '#546E7A', '#26a69a', '#D10CE8'],
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: function (val) {
                        return val + " pontos"
                    }
                }
            }
        };

        // Criar o gráfico
        var chart = new ApexCharts(document.querySelector("#groupProductivityChart"), options);
        chart.render();
    } else {
        // Se não houver dados, exibir uma mensagem
        document.querySelector("#groupProductivityChart").innerHTML = "Nenhum dado de produtividade por grupo disponível.";
    }

    // Evento para clique nos meses
    document.querySelectorAll('.month').forEach(el => {
        el.addEventListener('click', function() {
            const month = this.dataset.month;
            const year = document.getElementById('yearSelect').value;

            // Remover a classe 'selected' de todos os meses
            document.querySelectorAll('.month').forEach(monthEl => {
                monthEl.classList.remove('selected');
            });

            // Adicionar a classe 'selected' ao mês clicado
            this.classList.add('selected');

            updateMonthlyProductivity(year, month);
        });
    });

    // Função para atualizar o resumo de produtividade mensal
    function updateMonthlyProductivity(year, month, monthlyData = null) {
        if (!monthlyData) {
            monthlyData = <?php echo json_encode($dashboardData['monthlyProductivity']); ?>;
        }
        const data = monthlyData[month];

        if (data) {
            document.getElementById('totalPoints').textContent = data.totalPoints;
            document.getElementById('totalProcesses').textContent = data.totalProcesses;
        } else {
            document.getElementById('totalPoints').textContent = '-';
            document.getElementById('totalProcesses').textContent = '-';
        }

        // Atualizar a seleção visual dos meses
        document.querySelectorAll('.month').forEach(el => {
            el.classList.remove('selected', 'current');
            if (el.dataset.month == month) {
                el.classList.add('selected');
            }
            if (el.dataset.month == new Date().getMonth() + 1) {
                el.classList.add('current');
            }
        });
    }

    // Inicializar com o mês atual
    document.addEventListener('DOMContentLoaded', function() {
        const currentMonth = new Date().getMonth() + 1;
        const currentYear = new Date().getFullYear();
        updateMonthlyProductivity(currentYear, currentMonth);
    });

    // Ano
    document.getElementById('yearSelect').addEventListener('change', function() {
        const year = this.value;
        fetch(`${BASE_URL}/get-yearly-productivity?year=${year}`)
            .then(response => response.json())
            .then(data => {
                updateMonthlyProductivity(year, new Date().getMonth() + 1, data);
                // Atualize outros elementos da página conforme necessário
            })
            .catch(error => console.error('Erro ao buscar dados:', error));
    });
</script>
</body>
</html>