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
            $basePath = substr($basePath, 0, strpos($basePath, '/public') + 7);
        }

        return $basePath;
    }

    define('BASE_URL', getBaseUrl());
}

use Jti30\SistemaProdutividade\Controllers\ServidorController;

// Instanciar o ServidorController
$servidorController = new ServidorController($pdo, $authController);

// Verificar se o usuário pertence a um grupo
$userGroup = $servidorController->getAssignedGroup($_SESSION['user_id']);
$hasGroup = !empty($userGroup);

// Definir os itens de menu para esta página
$menuItems = [
    ['url' => 'dashboard-servidor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'registrar-produtividade', 'icon' => 'fas fa-clipboard-list', 'text' => 'Registrar Produtividade'],
    ['url' => 'meu-grupo', 'icon' => 'fas fa-users', 'text' => 'Meu Grupo', 'data-has-group' => $hasGroup ? 'true' : 'false'],
    ['url' => 'gestao-ferias-afastamentos', 'icon' => 'fas fa-calendar-alt', 'text' => 'Férias e Afastamentos']
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

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
                        <!-- Dados serão carregados via JavaScript -->
                        </tbody>
                    </table>
                    <div id="pagination" class="pagination">
                        <!-- Paginação será carregada via JavaScript -->
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
    // Função para carregar os dados da página
    function loadPage(page) {
        // Usar apenas BASE_URL sem concatenar com window.location.origin
        fetch(`${BASE_URL}/get-activities?page=${page}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta da rede: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateActivitiesTable(data.activities);
                    updatePagination(data.currentPage, data.totalPages);
                } else {
                    console.error('Erro retornado pelo servidor:', data.error);
                    document.querySelector('#activitiesTable tbody').innerHTML =
                        `<tr><td colspan="5" class="error-message">${data.error || 'Erro ao carregar atividades'}</td></tr>`;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar atividades:', error);
                document.querySelector('#activitiesTable tbody').innerHTML =
                    '<tr><td colspan="5" class="error-message">Erro ao carregar atividades. Por favor, tente novamente.</td></tr>';
            });
    }

    // Função para atualizar a tabela de atividades
    function updateActivitiesTable(activities) {
        const tbody = document.querySelector('#activitiesTable tbody');
        tbody.innerHTML = '';
        activities.forEach(activity => {
            const row = `
                <tr>
                    <td>${activity.process_number}</td>
                    <td>${activity.minute_type}</td>
                    <td>${activity.decision_type}</td>
                    <td>${activity.points}</td>
                    <td>${new Date(activity.created_at).toLocaleDateString('pt-BR')}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    // Função para atualizar a paginação
    function updatePagination(currentPage, totalPages) {
        const pagination = document.getElementById('pagination');
        pagination.innerHTML = '';

        const range = 2;
        let start = Math.max(1, currentPage - range);
        let end = Math.min(totalPages, currentPage + range);

        if (currentPage > 1) {
            pagination.innerHTML += `<a href="#" onclick="loadPage(${currentPage - 1}); return false;" class="pagination-link">Anterior</a>`;
        }

        if (start > 1) {
            pagination.innerHTML += `<a href="#" onclick="loadPage(1); return false;" class="pagination-link">1</a>`;
            if (start > 2) {
                pagination.innerHTML += '<span class="pagination-ellipsis">...</span>';
            }
        }

        for (let i = start; i <= end; i++) {
            if (i === currentPage) {
                pagination.innerHTML += `<span class="pagination-link active">${i}</span>`;
            } else {
                pagination.innerHTML += `<a href="#" onclick="loadPage(${i}); return false;" class="pagination-link">${i}</a>`;
            }
        }

        if (end < totalPages) {
            if (end < totalPages - 1) {
                pagination.innerHTML += '<span class="pagination-ellipsis">...</span>';
            }
            pagination.innerHTML += `<a href="#" onclick="loadPage(${totalPages}); return false;" class="pagination-link">${totalPages}</a>`;
        }

        if (currentPage < totalPages) {
            pagination.innerHTML += `<a href="#" onclick="loadPage(${currentPage + 1}); return false;" class="pagination-link">Próximo</a>`;
        }
    }

    // Função para inicializar o gráfico de produtividade
    function initProductivityChart(monthlyProductivity) {
        var ctx = document.getElementById('productivityChart').getContext('2d');
        var productivityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyProductivity.map(item => item.month),
                datasets: [{
                    label: 'Pontos de Produtividade',
                    data: monthlyProductivity.map(item => item.points),
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
    }

    // Função para configurar o link "Meu Grupo"
    function setupMeuGrupoLink() {
        const baseUrl = '<?php echo BASE_URL; ?>';
        var meuGrupoLink = document.querySelector(`a[href*="meu-grupo"]`);
        if (meuGrupoLink) {
            meuGrupoLink.addEventListener('click', function(e) {
                if (this.getAttribute('data-has-group') === 'false') {
                    e.preventDefault();
                    alert('Você não está em nenhum grupo. Entre em contato com o coordenador do sistema.');
                }
            });
        }
    }

    // Definir a constante BASE_URL para uso no JavaScript
    const BASE_URL = '<?php echo BASE_URL; ?>';

    // Carregar a primeira página e inicializar componentes ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        loadPage(1);
        initProductivityChart(<?php echo json_encode($dashboardData['monthlyProductivity'] ?? []); ?>);
        setupMeuGrupoLink();
    });
</script>

</body>
</html>