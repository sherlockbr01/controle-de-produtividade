<?php
// Verifique se a constante BASE_URL está definida
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

// Verifique se o usuário está autenticado como diretor
$authController->requireDirectorAuth();

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
$pageTitle = "Atribuir Usuário a Grupo";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/assign_user_group.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

        <main class="content">
            <form action="<?php echo BASE_URL; ?>/assign-user-group" method="post" class="assign-form">
                <div class="form-group">
                    <label for="group">Selecione o Grupo:</label>
                    <select name="group_id" id="group" required>
                        <?php foreach ($allGroups as $group): ?>
                            <option value="<?= htmlspecialchars($group['id']) ?>"><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="user">Selecione o Usuário:</label>
                    <select name="user_id" id="user" required>
                        <?php foreach ($allUsers as $user): ?>
                            <option value="<?= htmlspecialchars($user['id']) ?>"><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Atribuir Usuário ao Grupo</button>
            </form>

            <!-- Exibir mensagens de sucesso ou erro -->
            <div class="alert-container" style="margin-top: 20px; max-width: 400px; margin-left: auto; margin-right: auto;">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <p class="alert success-message" style="color:#28a745; padding:10px; margin:0; border-radius:4px; background-color:rgba(40,167,69,0.1); text-align:center; font-size: 14px;">
                        <?php echo $_SESSION['success_message']; ?>
                    </p>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <p class="alert error-message" style="color:#dc3545; padding:10px; margin:0; border-radius:4px; background-color:rgba(220,53,69,0.1); text-align:center; font-size: 14px;">
                        <?php echo $_SESSION['error_message']; ?>
                    </p>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
            </div>

            <h2>Grupos Existentes</h2>
            <div class="summary-cards">
                <?php foreach ($allGroups as $group): ?>
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
    // Faz a mensagem de sucesso desaparecer após 5 segundos
    setTimeout(function() {
        var successMessage = document.getElementById('success-message');
        if (successMessage) {
            successMessage.style.display = 'none';
        }
    }, 5000);
</script>

</body>
</html>