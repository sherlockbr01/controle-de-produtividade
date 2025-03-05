<?php
use Jti30\SistemaProdutividade\Controllers\ServidorController;

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'servidor') {
    header('Location: /sistema_produtividade/public/login');
    exit;
}

$servidorController = new ServidorController($pdo, $authController);
$productivityData = $servidorController->registerProductivity();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $servidorController->addProductivity();
    if ($result['success']) {
        echo "<script>alert('Produtividade registrada com sucesso!');</script>";
    } else {
        echo "<script>alert('Erro ao registrar produtividade: " . $result['error'] . "');</script>";
    }
}

$pageTitle = "Registrar Produtividade";

$menuItems = [
    ['url' => '/sistema_produtividade/public/inicio', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => '/sistema_produtividade/public/meu-grupo', 'icon' => 'fas fa-users', 'text' => 'Meu Grupo'],
    ['url' => '/sistema_produtividade/public/gerenciar-ferias-afastamento', 'icon' => 'fas fa-calendar-alt', 'text' => 'Gerenciar Férias e Afastamentos']
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/reset.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/register_productivity.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/sidebar.css">
    <link rel="stylesheet" href="/sistema_produtividade/public/css/header.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../compnents/sidebar.php'; ?>

    <div class="main-content">
        <?php
        $headerTitle = $pageTitle;
        include __DIR__ . '/../compnents/header.php';
        ?>

        <div class="content-wrapper">
            <form class="productivity-form" method="POST" action="/sistema_produtividade/public/registrar-produtividade">
                <?php echo isset($_SESSION['csrf_token']) ? '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">' : ''; ?>

                <div class="form-group">
                    <label for="process_number">Número do Processo:</label>
                    <input type="text" id="process_number" name="process_number" required pattern="[0-9]+" title="Por favor, insira apenas números">
                </div>

                <div class="form-group">
                    <label for="minute_type_id">
                        Tipo de Minuta:
                        <span class="add-button" onclick="openModal('minuteModal')">+</span>
                    </label>
                    <select id="minute_type_id" name="minute_type_id" required>
                        <option value="">Selecione o tipo de minuta</option>
                        <?php foreach ($productivityData['minuteTypes'] as $id => $minuteType): ?>
                            <option value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($minuteType, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="decision_type_id">Tipo de Decisão:</label>
                    <select id="decision_type_id" name="decision_type_id" required>
                        <option value="">Selecione o tipo de decisão</option>
                        <?php foreach ($productivityData['decisionTypes'] as $id => $decisionType): ?>
                            <option value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($decisionType, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Data:</label>
                    <input type="date" id="date" name="date" required>
                </div>

                <button type="submit" class="btn-register">Registrar Produtividade</button>

                <div class="alert-container">
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success" id="success-message">
                            <?= htmlspecialchars($_SESSION['success_message']); ?>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger" id="error-message">
                            <?= htmlspecialchars($_SESSION['error_message']); ?>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Tipo de Minuta -->
<div id="minuteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('minuteModal')">&times;</span>
        <h2>Adicionar Tipo de Minuta</h2>
        <form id="addMinuteForm">
            <input type="text" id="new_minute_type" name="new_minute_type" placeholder="Novo Tipo de Minuta" required>
            <button type="button" id="addMinuteButton">Adicionar</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var messages = document.querySelectorAll('.alert');
        messages.forEach(function(message) {
            setTimeout(function() {
                message.classList.add('fade-out');
                setTimeout(function() {
                    message.style.display = 'none';
                }, 500);
            }, 5000);
        });
    });
</script>
</body>
</html>