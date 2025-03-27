<?php
use Jti30\SistemaProdutividade\Controllers\GroupController;

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

// Verifique se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$groupController = new GroupController($pdo, $authController);

// Obtenha todos os grupos
$groups = $groupController->getAllGroups();

// Processar a submissão do formulário para criar um grupo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (mantenha o código existente para processamento do formulário)
}

// Definir os itens de menu para esta página
$menuItems = [
    ['url' => 'dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'create-group', 'icon' => 'fas fa-plus-circle', 'text' => 'Criar Grupo'],
    ['url' => 'manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => 'assign-user-group', 'icon' => 'fas fa-user-plus', 'text' => 'Atribuir Usuário a Grupo'],
    ['url' => 'relatorio-detalhado', 'icon' => 'fas fa-chart-bar', 'text' => 'Relatórios'],
    ['url' => 'gerenciar-ferias-afastamento', 'icon' => 'fas fa-calendar-alt', 'text' => 'Férias e Afastamentos']
];
// Definir o título da página
$pageTitle = "Criar grupo";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/create_group.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

        <main>
            <form action="<?php echo BASE_URL; ?>/create-group" method="post">
                <div class="form-group">
                    <label for="name">Nome do Grupo:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Descrição:</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                <button type="submit" class="button-submit">Criar Grupo</button>
            </form>

            <!-- Exibir mensagens de sucesso ou erro -->
            <div class="alert-container" style="display: flex; justify-content: center; align-items: center; height: 60px; margin-top: 20px;">
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

            <!-- Exibir grupos existentes em cartões -->
            <h2>Grupos Existentes</h2>
            <div class="summary-cards">
                <?php foreach ($groups as $group): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                        <p><?php echo htmlspecialchars($group['description']); ?></p>
                        <div class="user-count">
                            <span class="icon">👤</span>
                            <span class="count"><?php echo htmlspecialchars($group['user_count']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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