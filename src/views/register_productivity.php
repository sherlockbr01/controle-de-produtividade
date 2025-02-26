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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Produtividade</title>
    <style>
        /* Reset e estilos gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #36393f;
            color: #ffffff;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #2f3136;
            padding: 20px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar a {
            display: block;
            color: #ecf0f1;
            text-decoration: none;
            margin-bottom: 15px;
            font-size: 1.1em;
            transition: color 0.3s;
        }

        .sidebar a:hover {
            color: #7289da;
        }

        /* Conteúdo principal */
        .main-content {
            margin-left: calc(250px + 1cm); /* Nova largura da sidebar + 1cm */
            padding: 20px;
            flex-grow: 1;
        }

        /* Cabeçalho */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #ffffff;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info span {
            margin-right: 10px;
        }

        .btn-logout {
            background-color: #e74c3c;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-logout:hover {
            background-color: #c0392b;
        }

        /* Formulário */
        .form-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 600px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #4f545c;
            border-radius: 4px;
            background-color: #40444b;
            color: #ffffff;
        }

        .btn-register {
            background-color: #7289da;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            align-self: flex-start;
            margin-top: 0.7cm; /* 0.7cm de distância do box de data */
        }

        .btn-register:hover {
            background-color: #5b6eae;
        }

        /* Botão de adicionar */
        .add-button {
            cursor: pointer;
            color: #7289da;
            margin-left: 5px;
            font-size: 1.2em;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: rgba(44, 47, 51, 0.9);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            margin: 5% auto;
            position: relative;
            color: #fff;
        }

        .modal-content input[type="text"],
        .modal-content input[type="number"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 10px;
            border: none;
            border-radius: 5px;
            background-color: #40444b;
            color: #fff;
        }

        .modal-content button {
            background-color: #7289da;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .modal-content button:hover {
            background-color: #5b6eae;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: #fff;
            text-decoration: none;
        }

        /* Alertas */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>
<div class="sidebar">
    <a href="/sistema_produtividade/public/inicio">Início</a>
    <a href="/sistema_produtividade/public/visualizar-historico">Visualizar Histórico</a>
    <a href="/sistema_produtividade/public/meu-grupo" data-has-group="<?php echo $hasGroup ? 'true' : 'false'; ?>">Meu Grupo</a>
</div>
<div class="main-content">
    <?php
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>
    <div class="header">
        <h1>Registrar Produtividade</h1>
        <div class="user-info">
            <span>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>!</span>
            <a href="/sistema_produtividade/public/logout" class="btn-logout">Sair</a>
        </div>
    </div>
    <form method="POST" action="/sistema_produtividade/public/registrar-produtividade">
        <?php echo isset($_SESSION['csrf_token']) ? '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">' : ''; ?>

        <div class="form-container">
            <div class="form-group">
                <label for="process_number">Número do Processo:</label>
                <input type="text" id="process_number" name="process_number" required pattern="[0-9]+" title="Por favor, insira apenas números">
            </div>
            <div class="form-group">
                <label for="minute_type_id">Tipo de Minuta:
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
                <label for="decision_type_id">Tipo de Decisão:
                    <span class="add-button" onclick="openModal('decisionModal')">+</span>
                </label>
                <select id="decision_type_id" name="decision_type_id" required>
                    <option value="">Selecione o tipo de decisão</option>
                    <?php foreach ($productivityData['decisionTypes'] as $id => $decisionType): ?>
                        <option value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($decisionType, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="points">Pontos:</label>
                <input type="number" id="points" name="points" required min="0">
            </div>
            <div class="form-group">
                <label for="date">Data:</label>
                <input type="date" id="date" name="date" required>
            </div>
        </div>
        <button type="submit" class="btn-register">Registrar Produtividade</button>
    </form>
</div>

<!-- Modals -->
<div id="minuteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('minuteModal')">&times;</span>
        <h2>Adicionar Tipo de Minuta</h2>
        <form id="addMinuteForm">
            <input type="text" id="new_minute_type" name="new_minute_type" placeholder="Novo Tipo de Minuta" required>
            <button type="button" onclick="addMinuteType()">Adicionar</button>
        </form>
    </div>
</div>

<div id="decisionModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('decisionModal')">&times;</span>
        <h2>Adicionar Tipo de Decisão</h2>
        <form id="addDecisionForm">
            <input type="text" id="new_decision_type" name="new_decision_type" placeholder="Novo Tipo de Decisão" required>
            <button type="button" onclick="addDecisionType()">Adicionar</button>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = "block";
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }

    function addMinuteType() {
        var newMinuteType = document.getElementById('new_minute_type').value;
        if (newMinuteType) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/sistema_produtividade/public/add-minute-type', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        closeModal('minuteModal');
                        var select = document.getElementById('minute_type_id');
                        var option = document.createElement('option');
                        option.value = response.id;
                        option.text = newMinuteType;
                        select.add(option);
                        document.getElementById('new_minute_type').value = '';
                    } else {
                        alert('Erro ao adicionar tipo de minuta: ' + response.message);
                    }
                }
            };
            xhr.send('new_minute_type=' + encodeURIComponent(newMinuteType));
        } else {
            alert('Por favor, preencha o campo do tipo de minuta.');
        }
    }

    function addDecisionType() {
        var newDecisionType = document.getElementById('new_decision_type').value;
        if (newDecisionType) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/sistema_produtividade/public/add-decision-type', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        closeModal('decisionModal');
                        var select = document.getElementById('decision_type_id');
                        var option = document.createElement('option');
                        option.value = response.id;
                        option.text = newDecisionType;
                        select.add(option);
                        document.getElementById('new_decision_type').value = '';
                    } else {
                        alert('Erro ao adicionar tipo de decisão: ' + response.message);
                    }
                }
            };
            xhr.send('new_decision_type=' + encodeURIComponent(newDecisionType));
        } else {
            alert('Por favor, preencha o campo do tipo de decisão.');
        }
    }

    // Fechar o modal quando clicar fora dele
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = "none";
        }
    }

    // Prevenir o envio do formulário ao pressionar Enter nos modais
    document.getElementById('addMinuteForm').onkeypress = function(e) {
        var key = e.charCode || e.keyCode || 0;
        if (key == 13) {
            e.preventDefault();
            addMinuteType();
        }
    }

    document.getElementById('addDecisionForm').onkeypress = function(e) {
        var key = e.charCode || e.keyCode || 0;
        if (key == 13) {
            e.preventDefault();
            addDecisionType();
        }
    }
</script>

    </body>
</html>