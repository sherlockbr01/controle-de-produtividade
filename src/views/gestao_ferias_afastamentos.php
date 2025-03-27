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

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'servidor') {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

use Jti30\SistemaProdutividade\Controllers\FeriasAfastamentoController;

require_once __DIR__ . '/../../vendor/autoload.php';

global $pdo;

$feriasAfastamentoController = new FeriasAfastamentoController($pdo);

$data = $feriasAfastamentoController->listarFeriasAfastamentos();
$userAfastamentos = $data['afastamentos'];
$tiposAfastamento = $data['tiposAfastamento'];

$menuItems = [
    ['url' => 'dashboard-servidor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'registrar-produtividade', 'icon' => 'fas fa-clipboard-list', 'text' => 'Registrar Produtividade'],
    ['url' => 'meu-grupo', 'icon' => 'fas fa-users', 'text' => 'Meu Grupo'],
    ['url' => 'gestao-ferias-afastamentos', 'icon' => 'fas fa-calendar-alt', 'text' => 'Férias e Afastamentos']
];
$pageTitle = "Registrar Férias ou Afastamento";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/gestao_ferias_afastamentos.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
</head>
<body class="ferias-afastamentos-page">

<div class="dashboard-container">
    <?php include_once __DIR__ . '/../components/sidebar.php'; ?>
    <div class="main-content">
        <div class="header">
            <?php include_once __DIR__ . '/../components/header.php'; ?>
        </div>
        <div class="dashboard-content">
            <section class="register-section">
                <form action="<?php echo BASE_URL; ?>/submit-ferias-afastamento" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="tipo_afastamento">Motivo do Afastamento/Férias:</label>
                            <select name="tipo_afastamento" id="tipo_afastamento" required>
                                <?php foreach ($tiposAfastamento as $tipo): ?>
                                    <option value="<?php echo htmlspecialchars($tipo['id']); ?>"><?php echo htmlspecialchars($tipo['descricao']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="data_inicio">Data de Início:</label>
                            <input type="date" name="data_inicio" id="data_inicio" required>
                        </div>
                        <div class="form-group">
                            <label for="data_termino">Data de Término:</label>
                            <input type="date" name="data_termino" id="data_termino" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="comentario">Motivo (opcional):</label>
                        <textarea name="comentario" id="comentario" rows="3"></textarea>
                    </div>
                    <div class="form-group button-group">
                        <button type="submit" class="btn-submit">Registrar Afastamento</button>
                    </div>
                </form>
                <div class="alert-container" style="display: flex; justify-content: center; align-items: center; height: 60px;">
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <p class="success-message" style="color:#28a745;padding:10px;margin:0;border-radius:4px;font-weight:bold;background-color:rgba(40,167,69,0.1);text-align:center;">
                            <?php echo $_SESSION['success_message']; ?>
                        </p>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <p class="error-message" style="color:#dc3545;padding:10px;margin:0;border-radius:4px;font-weight:bold;background-color:rgba(220,53,69,0.1);text-align:center;">
                            <?php echo $_SESSION['error_message']; ?>
                        </p>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="afastamentos-section">
                <h2>Meus Afastamentos</h2>
                <table class="afastamentos-table">
                    <thead>
                    <tr>
                        <th class="column-header">Tipo</th>
                        <th class="column-header">Data de Início</th>
                        <th class="column-header">Data de Término</th>
                        <th class="column-header">Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($userAfastamentos as $afastamento): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($afastamento['tipo_afastamento']); ?></td>
                            <td><?php echo formatarData($afastamento['data_inicio']); ?></td>
                            <td><?php echo formatarData($afastamento['data_termino']); ?></td>
                            <td><?php echo htmlspecialchars($afastamento['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
<script>
    // Função para fazer a mensagem desaparecer
    function fadeOutMessage(messageElement) {
        var opacity = 1;
        var timer = setInterval(function() {
            if (opacity <= 0.1) {
                clearInterval(timer);
                messageElement.style.display = 'none';
            }
            messageElement.style.opacity = opacity;
            opacity -= opacity * 0.1;
        }, 50);
    }

    // Seleciona todas as mensagens
    var messages = document.querySelectorAll('.success-message, .error-message');

    // Para cada mensagem, configura um temporizador para fazê-la desaparecer
    messages.forEach(function(message) {
        setTimeout(function() {
            fadeOutMessage(message);
        }, 7000); // 7000 milissegundos = 7 segundos
    });
</script>
</body>
</html>