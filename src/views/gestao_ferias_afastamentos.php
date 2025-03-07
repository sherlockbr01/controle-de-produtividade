<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'servidor') {
    header('Location: /sistema_produtividade/public/login');
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
    ['url' => '/sistema_produtividade/public/dashboard-servidor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => '/sistema_produtividade/public/registrar-produtividade', 'icon' => 'fas fa-clipboard-list', 'text' => 'Registrar Produtividade'],
    ['url' => '/sistema_produtividade/public/meu-grupo', 'icon' => 'fas fa-users', 'text' => 'Meu Grupo'],
    ['url' => '/sistema_produtividade/public/gestao-ferias-afastamentos', 'icon' => 'fas fa-calendar-alt', 'text' => 'Férias e Afastamentos']
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
    <link rel="stylesheet" href="/sistema_produtividade/public/css/gestao_ferias_afastamentos.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/sidebar.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/header.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var successMessage = document.querySelector('.alert-success');
                var errorMessage = document.querySelector('.alert-danger');
                if (successMessage) {
                    successMessage.style.display = 'none';
                }
                if (errorMessage) {
                    errorMessage.style.display = 'none';
                }
            }, 5000);
        });
    </script>
</head>
<body class="ferias-afastamentos-page">
<div class="dashboard-container">
    <?php include_once __DIR__ . '/../compnents/sidebar.php'; ?>
    <div class="main-content">
        <?php include_once __DIR__ . '/../compnents/header.php'; ?>

        <main class="dashboard-content">
            <?php
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                unset($_SESSION['success_message']);
            }

            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                unset($_SESSION['error_message']);
            }
            ?>
            <section class="register-section">
                <form action="/sistema_produtividade/public/submit-ferias-afastamento" method="POST">
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
                    <div class="form-group">
                        <label for="comentario">Motivo (opcional):</label>
                        <textarea name="comentario" id="comentario" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-submit">Registrar Afastamento</button>
                    </div>
                </form>
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
                            <td><?php echo htmlspecialchars($afastamento['data_inicio']); ?></td>
                            <td><?php echo htmlspecialchars($afastamento['data_termino']); ?></td>
                            <td><?php echo htmlspecialchars($afastamento['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</div>
</body>
</html>