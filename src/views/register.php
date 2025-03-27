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

// Inclua o arquivo de configuração do banco de dados
require_once __DIR__ . '/../config/database.php';

// Estabeleça a conexão com o banco de dados
$pdo = connectDatabase();

// Verifica se o usuário está logado e é um diretor
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'diretor') {
    // Redirecionar para a página de login se não estiver logado ou não for diretor
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// Definir os itens de menu para esta página
$menuItems = [
    ['url' => 'dashboard-diretor', 'icon' => 'fas fa-home', 'text' => 'Início'],
    ['url' => 'manage-groups', 'icon' => 'fas fa-users', 'text' => 'Gerenciar Grupos'],
    ['url' => 'relatorio-detalhado', 'icon' => 'fas fa-chart-bar', 'text' => 'Relatórios'],
    ['url' => 'gerenciar-ferias-afastamento', 'icon' => 'fas fa-calendar-alt', 'text' => 'Gerenciar Férias e Afastamentos'],
    ['url' => 'register', 'icon' => 'fas fa-user-plus', 'text' => 'Cadastrar Usuário']
];

// Definir o título da página
$pageTitle = "Cadastrar Usuários";

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/register.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title><?php echo $pageTitle; ?></title>
</head>
<body>
<div class="dashboard-container">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../components/header.php'; ?>

        <div class="register-container">
            <div class="info-container">
                <i class="fas fa-users fa-5x"></i>
            </div>
            <div class="form-container">
                <h2>Cadastrar Novo Usuário</h2>
                <form id="register-form" action="<?php echo BASE_URL; ?>/register" method="post" class="productivity-form" autocomplete="off">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-user"></i> Nome:</label>
                        <input type="text" id="name" name="name" placeholder="Nome" required autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                        <input type="email" id="email" name="email" placeholder="Email" required autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Senha:</label>
                        <input type="password" id="password" name="password" placeholder="Senha" required autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                    </div>
                    <div class="form-group">
                        <label for="user_type"><i class="fas fa-user-tag"></i> Tipo de Usuário:</label>
                        <select id="user_type" name="user_type" autocomplete="off">
                            <option value="servidor">Servidor</option>
                            <option value="diretor">Diretor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn-register">Registrar</button>
                    </div>
                </form>
                <div class="alert-container">
                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="success-message">
                            <p><?php echo $_SESSION['success_message']; ?></p>
                        </div>
                        <?php unset($_SESSION['success_message']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="error-message">
                            <p><?php echo $_SESSION['error_message']; ?></p>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('register-form');
        var inputs = form.getElementsByTagName('input');

        // Função para limpar e desabilitar o preenchimento automático
        function clearAndDisableAutocomplete(input) {
            input.value = '';
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('autocorrect', 'off');
            input.setAttribute('autocapitalize', 'off');
            input.setAttribute('spellcheck', 'false');
        }

        // Limpa os campos e desabilita o preenchimento automático no carregamento da página
        for (var i = 0; i < inputs.length; i++) {
            clearAndDisableAutocomplete(inputs[i]);
        }

        // Previne o preenchimento automático do navegador
        window.setTimeout(function() {
            for (var i = 0; i < inputs.length; i++) {
                clearAndDisableAutocomplete(inputs[i]);
            }
        }, 100);

        // Limpa os campos quando recebem foco
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].addEventListener('focus', function() {
                clearAndDisableAutocomplete(this);
            });
        }

        // Previne o preenchimento automático quando o formulário é submetido
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i].value === '') {
                    clearAndDisableAutocomplete(inputs[i]);
                }
            }
            this.submit();
        });

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
    });
</script>
</body>
</html>