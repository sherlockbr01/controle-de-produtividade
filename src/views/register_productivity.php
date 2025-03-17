<?php
use Jti30\SistemaProdutividade\Controllers\ServidorController;
use Jti30\SistemaProdutividade\Controllers\AuthController;

// Incluir o arquivo de configuração do banco de dados
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'servidor') {
    header('Location: /sistema_produtividade/public/login');
    exit;
}

$authController = new AuthController($pdo);
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
    ['url' => '/sistema_produtividade/public/dashboard-servidor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => '/sistema_produtividade/public/registrar-produtividade', 'icon' => 'fas fa-clipboard-list', 'text' => 'Registrar Produtividade'],
    ['url' => '/sistema_produtividade/public/meu-grupo', 'icon' => 'fas fa-users', 'text' => 'Meu Grupo'],
    ['url' => '/sistema_produtividade/public/gestao-ferias-afastamentos', 'icon' => 'fas fa-calendar-alt', 'text' => 'Férias e Afastamentos']
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
        <h2>Adicionar Novo Tipo de Minuta</h2>
        <form action="/sistema_produtividade/public/adicionar-tipo-minuta" method="POST">
            <label for="new_minute_type">Nome do Tipo de Minuta:</label>
            <input type="text" id="new_minute_type" name="new_minute_type" required>
            <button type="button" onclick="addMinuteType()">Adicionar</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Funcionalidade para mensagens de alerta
        var messages = document.querySelectorAll('.alert');
        messages.forEach(function(message) {
            setTimeout(function() {
                message.classList.add('fade-out');
                setTimeout(function() {
                    message.style.display = 'none';
                }, 500);
            }, 5000);
        });



        // Configurar o evento de fechar para o botão X do modal
        var closeButtons = document.getElementsByClassName('close');
        for (var i = 0; i < closeButtons.length; i++) {
            closeButtons[i].onclick = function() {
                closeModal('minuteModal');
            };
        }
    });

    function openModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = "block";
        }
    }

    function closeModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = "none";
        }
    }

    function addMinuteType() {
        var newMinuteType = document.getElementById('new_minute_type').value;
        if (newMinuteType) {
            fetch('/sistema_produtividade/public/add-minute-type', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'new_minute_type=' + encodeURIComponent(newMinuteType)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateMinuteTypesList(data.id, newMinuteType);
                        document.getElementById('new_minute_type').value = '';
                        closeModal('minuteModal');
                    } else {
                        alert('Erro ao adicionar tipo de minuta: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao adicionar tipo de minuta. Por favor, tente novamente.');
                });
        }
    }

    function updateMinuteTypesList(newId, newType) {
        var select = document.getElementById('minute_type_id');
        var option = document.createElement('option');
        option.value = newId;
        option.textContent = newType;
        select.appendChild(option);

        // Seleciona o novo tipo de minuta
        select.value = newId;
    }

    // Fechar o modal se clicar fora dele
    window.onclick = function(event) {
        var modal = document.getElementById('minuteModal');
        if (event.target == modal) {
            closeModal('minuteModal');
        }
    }
</script>