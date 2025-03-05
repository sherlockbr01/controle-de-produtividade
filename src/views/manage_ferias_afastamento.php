<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'diretor') {
    header('Location: /sistema_produtividade/public/');
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
    ['url' => '/sistema_produtividade/public/dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => '/sistema_produtividade/public/relatorios', 'icon' => 'fas fa-chart-bar', 'text' => 'Relatórios'],
    ['url' => '/sistema_produtividade/public/manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
];
// Definir o título da página
$pageTitle = "Gestão de Férias e Afastamentos";

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/manage_ferias_afastamento.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/sidebar.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/header.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="ferias-afastamentos-page">
<div class="dashboard-container">
    <?php include __DIR__ . '/../compnents/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../compnents/header.php'; ?>

        <main class="dashboard-content">
            <div class="page-title">
            </div>
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
                    <h3>Total de Férias/Afastamentos</h3>
                    <p class="big-number"><?php echo $dadosPagina['totalLeaves']; ?></p>
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
                    <?php if (isset($dadosPagina['currentLeaves']) && is_array($dadosPagina['currentLeaves']) && !empty($dadosPagina['currentLeaves'])): ?>
                        <?php foreach ($dadosPagina['currentLeaves'] as $leave): ?>
                            <tr>
                                <td><?= htmlspecialchars($leave['name']) ?></td>
                                <td><?= htmlspecialchars($leave['data_inicio']) ?></td>
                                <td><?= htmlspecialchars($leave['data_termino']) ?></td>
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
                            <td><?php echo htmlspecialchars($solicitacao['data_inicio'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($solicitacao['data_termino'] ?? 'N/A'); ?></td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="solicitacao_id" value="<?php echo $solicitacao['id']; ?>">
                                    <button type="submit" name="acao" value="aprovar" class="btn-icon btn-aprovar" title="Aprovar">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="submit" name="acao" value="rejeitar" class="btn-icon btn-rejeitar" title="Rejeitar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
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
        </main>
    </div>
</div>

<script>
    // JavaScript para controlar o modal
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
</script>
</body>
</html>