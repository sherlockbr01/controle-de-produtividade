<?php
// Verifica se a sessão está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Buscar dados completos do perfil
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../config/database.php';

use Jti30\SistemaProdutividade\Controllers\AuthController;

$pdo = connectDatabase(); // Usando a função definida em database.php
$authController = new AuthController($pdo);
$profileData = $authController->getProfileData();

$userName = htmlspecialchars($profileData['name'] ?? $_SESSION['user_name']);
$userInitials = strtoupper(substr($userName, 0, 2));

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

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    // Redirecionar para a página de login se não estiver logado
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$userName = htmlspecialchars($_SESSION['user_name']);
$userInitials = strtoupper(substr($userName, 0, 2));
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Sistema de Produtividade'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<header class="header">
    <h1><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Sistema de Produtividade'; ?></h1>
    <div class="user-info">
        <div class="user-dropdown">
            <div class="user-avatar" id="userDropdownToggle">
                <?php echo $userInitials; ?>
            </div>
            <div class="user-dropdown-content" id="userDropdownContent">
                <div class="user-profile">
                    <div class="user-details">
                        <span class="user-fullname"><?php echo $userName; ?></span>
                    </div>
                </div>
                <a href="#" id="editProfileLink">Editar Perfil</a>
                <a href="#" id="changePasswordLink">Alterar Senha</a>
                <div class="dropdown-divider"></div>
                <a href="<?php echo BASE_URL; ?>/logout">Sair</a>
            </div>
        </div>
    </div>
</header>

<!-- Modal para edição de perfil -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Editar Perfil</h2>
        <form id="profileForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Nome:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($profileData['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="function">Função:</label>
                    <input type="text" id="function" name="function" value="<?php echo htmlspecialchars($profileData['function'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="birth_date">Data de Nascimento:</label>
                    <input type="date" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($profileData['birth_date'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Telefone:</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($profileData['phone'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="city">Cidade:</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($profileData['city'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="state">Estado:</label>
                    <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($profileData['state'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="country">País:</label>
                    <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($profileData['country'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <button type="submit">Salvar Alterações</button>
            </div>
        </form>
        <div id="profileUpdateMessage"></div>
    </div>
</div>

<!-- Modal para alterar senha -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Alterar Senha</h2>
        <form id="passwordForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="current_password">Senha Atual:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="new_password">Nova Senha:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="confirm_password">Confirmar Nova Senha:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            <div class="form-row">
                <button type="submit">Alterar Senha</button>
            </div>
        </form>
        <div id="passwordUpdateMessage"></div>
    </div>
</div>

<script>
    $(document).ready(function() {
        const userDropdownToggle = $('#userDropdownToggle');
        const userDropdownContent = $('#userDropdownContent');
        const editProfileLink = $('#editProfileLink');
        const changePasswordLink = $('#changePasswordLink');
        const profileModal = $('#profileModal');
        const passwordModal = $('#passwordModal');
        const closeBtns = $('.close');

        userDropdownToggle.on('click', function(event) {
            event.stopPropagation();
            userDropdownContent.toggleClass('show');
        });

        $(document).on('click', function(event) {
            if (!userDropdownContent.is(event.target) && userDropdownContent.has(event.target).length === 0 && !userDropdownToggle.is(event.target)) {
                userDropdownContent.removeClass('show');
            }
        });

        editProfileLink.on('click', function(event) {
            event.preventDefault();
            profileModal.css('display', 'block');
        });

        changePasswordLink.on('click', function(event) {
            event.preventDefault();
            passwordModal.css('display', 'block');
        });

        closeBtns.on('click', function() {
            $(this).closest('.modal').css('display', 'none');
        });

        $(window).on('click', function(event) {
            if (event.target == profileModal[0] || event.target == passwordModal[0]) {
                $('.modal').css('display', 'none');
            }
        });

        function showMessage(element, message, type) {
            const html = `
                <div class="message message-${type}">
                    ${message}
                </div>
            `;
            element.html(html);
            setTimeout(() => {
                element.find('.message').addClass('fade-out');
                setTimeout(() => element.html(''), 500);
            }, 3000);
        }

        $('#profileForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: '<?php echo BASE_URL; ?>/update-profile',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Atualiza os campos do formulário com os novos valores
                        for (var key in response.updatedData) {
                            $('#' + key).val(response.updatedData[key]);
                        }

                        showMessage($('#profileUpdateMessage'), 'Perfil atualizado com sucesso!', 'success');

                        // Atualiza o nome no dropdown do usuário
                        $('.user-fullname').text(response.updatedData.name);

                        // Atualiza as iniciais no avatar
                        $('#userDropdownToggle').text(response.updatedData.name.substring(0, 2).toUpperCase());
                    } else {
                        showMessage($('#profileUpdateMessage'), 'Erro ao atualizar o perfil: ' + response.error, 'error');
                    }
                },
                error: function() {
                    showMessage($('#profileUpdateMessage'), 'Erro ao processar a solicitação.', 'error');
                }
            });
        });

        $('#passwordForm').on('submit', function(e) {
            e.preventDefault();
            const newPassword = $('#new_password').val();
            const confirmPassword = $('#confirm_password').val();

            if (newPassword !== confirmPassword) {
                showMessage($('#passwordUpdateMessage'), 'As senhas não coincidem.', 'error');
                return;
            }

            $.ajax({
                url: '<?php echo BASE_URL; ?>/update-password',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showMessage($('#passwordUpdateMessage'), 'Senha atualizada com sucesso!', 'success');
                        setTimeout(function() {
                            passwordModal.css('display', 'none');
                            $('#passwordUpdateMessage').html('');
                            $('#passwordForm')[0].reset();
                        }, 3500);
                    } else {
                        showMessage($('#passwordUpdateMessage'), response.error, 'error');
                    }
                },
                error: function() {
                    showMessage($('#passwordUpdateMessage'), 'Erro ao processar a solicitação.', 'error');
                }
            });
        });
    });
</script>
</body>
</html>



