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

// Obter dados de produtividade do usuário
$productivityData = $servidorController->getUserTotalProductivity($_SESSION['user_id']);

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

        <section class="monthly-productivity">
            <h2>Minha Produtividade Mensal</h2>
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
                    <h3>Meus Pontos</h3>
                    <p class="big-number" id="totalPoints"><?php echo $productivityData['totalPoints'] ?? '-'; ?></p>
                </div>
                <div class="card">
                    <h3>Meus Processos</h3>
                    <p class="big-number" id="totalProcesses"><?php echo $productivityData['completedProcesses'] ?? '-'; ?></p>
                </div>
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

    document.addEventListener('DOMContentLoaded', function() {
        const monthButtons = document.querySelectorAll('.month-button');
        monthButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remover a classe de todos os botões
                monthButtons.forEach(btn => btn.classList.remove('selected-month'));
                // Adicionar a classe ao botão clicado
                this.classList.add('selected-month');
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const monthElements = document.querySelectorAll('.month');
        monthElements.forEach(month => {
            month.addEventListener('click', function() {
                // Remover a classe 'selected' de todos os meses
                monthElements.forEach(m => m.classList.remove('selected'));
                // Adicionar a classe 'selected' ao mês clicado
                this.classList.add('selected');

                // Obter o número do mês selecionado
                const selectedMonth = this.getAttribute('data-month');

                // Carregar dados para o mês selecionado
                loadMonthlyData(selectedMonth);
            });
        });

        // Carregar dados para o mês atual ao carregar a página
        const currentMonthElement = document.querySelector('.month.current');
        if (currentMonthElement) {
            currentMonthElement.click();
        }
    });

    function loadMonthlyData(selectedMonth) {
        const selectedYear = document.getElementById('yearSelect').value;
        fetch(`${BASE_URL}/get-monthly-data?year=${selectedYear}&month=${selectedMonth}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalPoints').textContent = data.data.totalPoints;
                    document.getElementById('totalProcesses').textContent = data.data.totalProcesses;
                } else {
                    console.error('Erro ao carregar dados mensais:', data.error);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar dados mensais:', error);
            });
    }

</script>

</body>
</html>