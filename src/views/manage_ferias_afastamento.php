<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'diretor') {
    header('Location: ' . BASE_URL . '/');
    exit;
}

use Jti30\SistemaProdutividade\Controllers\FeriasAfastamentoController;

require_once __DIR__ . '/../../vendor/autoload.php';

global $pdo;

$feriasAfastamentoController = new FeriasAfastamentoController($pdo);

// Obter dados para a página
$dadosPagina = $feriasAfastamentoController->getDadosPagina();

// Processar ações de aprovar/rejeitar se houver
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao']) && isset($_POST['solicitacao_id'])) {
        $acao = $_POST['acao'];
        $solicitacaoId = $_POST['solicitacao_id'];

        if ($acao === 'aprovar') {
            $resultado = $feriasAfastamentoController->aprovarSolicitacao($solicitacaoId);
        } elseif ($acao === 'rejeitar') {
            $resultado = $feriasAfastamentoController->rejeitarSolicitacao($solicitacaoId);
        }

        // Redirecionar para evitar reenvio do formulário
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Definição dos itens de menu
$menuItems = [
    ['url' => 'dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => 'relatorio-detalhado', 'icon' => 'fas fa-chart-bar', 'text' => 'Relatórios'],
    ['url' => 'gerenciar-ferias-afastamento', 'icon' => 'fas fa-calendar-alt', 'text' => 'Férias e Afastamentos']
];
// Definir o título da página
$pageTitle = "Gestão de Férias e Afastamentos";

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

function isAfastamentoFuturo($dataInicio) {
    $hoje = new DateTime();
    $inicioAfastamento = new DateTime($dataInicio);
    return $inicioAfastamento > $hoje;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/manage_ferias_afastamento.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="ferias-afastamentos-page">
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

        <main class="dashboard-content">

            <section class="summary-cards">
                <div class="card">
                    <h3>Solicitações Pendentes</h3>
                    <p class="big-number"><?php echo $dadosPagina['pendingCount']; ?></p>
                </div>
                <div class="card">
                    <h3>Solicitações Aprovadas</h3>
                    <p class="big-number"><?php echo $dadosPagina['approvedCount']; ?></p>
                </div>
                <div class="card">
                    <h3>Solicitações Rejeitadas</h3>
                    <p class="big-number"><?php echo $dadosPagina['rejectedCount']; ?></p>
                </div>
                <div class="card">
                    <h3>Servidores Afastados</h3>
                    <p class="big-number"><?php echo $dadosPagina['currentLeavesCount']; ?></p>
                </div>
            </section>

            <section class="current-leaves">
                <h2>Servidores Atualmente Afastados</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Data de Início</th>
                        <th>Data de Término</th>
                        <th>Tipo de Afastamento</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($dadosPagina['currentLeaves'])): ?>
                        <?php foreach ($dadosPagina['currentLeaves'] as $leave): ?>
                            <tr>
                                <td><?= htmlspecialchars($leave['name']) ?></td>
                                <td><?= formatarData($leave['data_inicio']) ?></td>
                                <td><?= formatarData($leave['data_termino']) ?></td>
                                <td><?= htmlspecialchars($leave['tipo_afastamento']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">Não há servidores atualmente afastados.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="future-leaves">
                <h2>Servidores com Afastamento Futuro</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Data de Início</th>
                        <th>Data de Término</th>
                        <th>Tipo de Afastamento</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($dadosPagina['futureLeaves'])): ?>
                        <?php foreach ($dadosPagina['futureLeaves'] as $afastamento): ?>
                            <?php if ($afastamento['status'] === 'Aprovado' && isAfastamentoFuturo($afastamento['data_inicio'])): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($afastamento['name']); ?></td>
                                    <td><?php echo formatarData($afastamento['data_inicio']); ?></td>
                                    <td><?php echo formatarData($afastamento['data_termino']); ?></td>
                                    <td><?php echo htmlspecialchars($afastamento['tipo_afastamento']); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (empty($dadosPagina['futureLeaves']) || !array_filter($dadosPagina['futureLeaves'], function($afastamento) { return $afastamento['status'] === 'Aprovado' && isAfastamentoFuturo($afastamento['data_inicio']); })): ?>
                        <tr>
                            <td colspan="4">Não há servidores com afastamento futuro.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="ferias-afastamento-list">
                <h2>Solicitações Pendentes de Férias e Afastamentos</h2>
                <table>
                    <thead>
                    <tr>
                        <th>Servidor</th>
                        <th>Tipo</th>
                        <th>Motivo</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th>Ações</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dadosPagina['pendingLeaveRequests'] as $solicitacao): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($solicitacao['servidor_nome'] ?? 'Nome não disponível'); ?></td>
                            <td><?php echo htmlspecialchars($solicitacao['tipo_afastamento'] ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                $motivo = $solicitacao['comentario'] ?? 'Servidor não registrou o motivo!';
                                $motivoTruncado = mb_substr($motivo, 0, 20, 'UTF-8');
                                echo htmlspecialchars($motivoTruncado);
                                if (mb_strlen($motivo, 'UTF-8') > 20) {
                                    echo '... ';
                                    echo '<button class="btn-ver-mais" data-motivo="' . htmlspecialchars($motivo) . '">Ver mais</button>';
                                }
                                ?>
                            </td>
                            <td><?php echo formatarData($solicitacao['data_inicio'] ?? 'N/A'); ?></td>
                            <td><?php echo formatarData($solicitacao['data_termino'] ?? 'N/A'); ?></td>
                            <td>
                                <button class="btn-icon btn-aprovar" data-action="aprovar" data-id="<?php echo $solicitacao['id']; ?>" title="Aprovar">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn-icon btn-rejeitar" data-action="rejeitar" data-id="<?php echo $solicitacao['id']; ?>" title="Rejeitar">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <!-- Modal para exibir o motivo completo -->
            <div id="motivoModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Motivo Completo</h2>
                    <p id="motivoCompleto"></p>
                </div>
            </div>

            <!-- Modal de confirmação -->
            <div id="confirmModal" class="modal">
                <div class="modal-content">
                    <h2>Confirmação</h2>
                    <p id="confirmMessage"></p>
                    <button id="confirmYes" class="btn btn-primary">Sim</button>
                    <button id="confirmNo" class="btn btn-secondary">Não</button>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // JavaScript para controlar o modal do motivo
    var modal = document.getElementById("motivoModal");
    var motivoCompleto = document.getElementById("motivoCompleto");
    var buttons = document.getElementsByClassName("btn-ver-mais");
    var span = document.getElementsByClassName("close")[0];

    for (var i = 0; i < buttons.length; i++) {
        buttons[i].onclick = function() {
            modal.style.display = "block";
            motivoCompleto.textContent = this.getAttribute("data-motivo");
        }
    }

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // JavaScript para controlar o modal de confirmação
    var confirmModal = document.getElementById("confirmModal");
    var confirmMessage = document.getElementById("confirmMessage");
    var confirmYes = document.getElementById("confirmYes");
    var confirmNo = document.getElementById("confirmNo");
    var actionButtons = document.querySelectorAll('.btn-aprovar, .btn-rejeitar');

    actionButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var action = this.getAttribute('data-action');
            var id = this.getAttribute('data-id');
            var actionText = action === 'aprovar' ? 'aprovar' : 'rejeitar';
            confirmMessage.textContent = `Tem certeza que deseja ${actionText} esta solicitação?`;
            confirmModal.style.display = "block";

            // Armazenar os dados da ação para uso posterior
            confirmYes.setAttribute('data-action', action);
            confirmYes.setAttribute('data-id', id);
        });
    });

    // Ação de confirmação
    confirmYes.onclick = function() {
        var action = this.getAttribute('data-action');
        var id = this.getAttribute('data-id');

        // Criar e submeter o formulário
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;

        var inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'acao';
        inputAction.value = action;

        var inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'solicitacao_id';
        inputId.value = id;

        form.appendChild(inputAction);
        form.appendChild(inputId);

        document.body.appendChild(form);
        form.submit();

        confirmModal.style.display = "none";
    }

    // Ação de cancelamento
    confirmNo.onclick = function() {
        confirmModal.style.display = "none";
    }

    // Fechar o modal de confirmação se clicar fora dele
    window.onclick = function(event) {
        if (event.target == confirmModal) {
            confirmModal.style.display = "none";
        }
    }
</script>
</body>
</html>