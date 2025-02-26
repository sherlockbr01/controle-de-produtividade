<?php
session_start();

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config/database.php';

use Jti30\SistemaProdutividade\Controllers\AuthController;
use Jti30\SistemaProdutividade\Controllers\ServidorController;
use Jti30\SistemaProdutividade\Controllers\DiretorController;
use Jti30\SistemaProdutividade\Controllers\GroupController;

// Criar conexão com o banco de dados
$pdo = connectDatabase();

// Instanciar os controllers
$authController = new AuthController($pdo);
$servidorController = new ServidorController($pdo, $authController);
$diretorController = new DiretorController($pdo, $authController);
$groupController = new GroupController($pdo, $authController);

$request = $_SERVER['REQUEST_URI'];

// Remove o prefixo do caminho do projeto e a query string, se houver
$prefix = '/sistema_produtividade/public';
$request = strtok(substr($request, strlen($prefix)), '?');

// Remover barras no início e no final da string
$request = trim($request, '/');

// Se a requisição estiver vazia, defina-a como 'login'
if (empty($request)) {
    $request = 'login';
}

try {
    switch ($request) {
        case 'register':
            $authController->register();
            include __DIR__ . '/../src/views/register.php';
            break;

        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $authController->login();
                if (isset($result['error'])) {
                    $_SESSION['login_error'] = $result['error'];
                    header('Location: /sistema_produtividade/public/login');
                    exit;
                }
            }
            require __DIR__ . '/../src/views/login.php';
            break;

        case 'logout':
            $authController->logout();
            header('Location: /sistema_produtividade/public/login');
            exit;

        case 'dashboard-servidor':
            $authController->requireServerAuth();
            $dashboardData = $servidorController->getDashboardData();
            $dashboardData['recentActivities'] = $servidorController->getRecentActivities($_SESSION['user_id']);
            require __DIR__ . '/../src/views/dashboard_servidor.php';
            break;

        case 'dashboard-diretor':
            $authController->requireDirectorAuth();
            $data = $diretorController->getDashboardData();
            require __DIR__ . '/../src/views/dashboard_diretor.php';
            break;

        case 'perfil':
            $authController->requireAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $authController->updateProfile();
                if (isset($result['success'])) {
                    $_SESSION['profile_success'] = $result['success'];
                } else {
                    $_SESSION['profile_error'] = $result['error'];
                }
                header('Location: /sistema_produtividade/public/perfil');
                exit;
            }
            $userData = $authController->getCurrentUser();
            $success = $_SESSION['profile_success'] ?? null;
            $error = $_SESSION['profile_error'] ?? null;
            unset($_SESSION['profile_success'], $_SESSION['profile_error']);
            require __DIR__ . '/../src/views/perfil.php';
            break;

        case 'registrar-produtividade':
            $authController->requireServerAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $servidorController->registerProductivity();
                if (isset($result['success'])) {
                    header('Location: /sistema_produtividade/public/dashboard-servidor');
                    exit;
                } else {
                    $_SESSION['productivity_error'] = $result['error'];
                    header('Location: /sistema_produtividade/public/registrar-produtividade');
                    exit;
                }
            }
            $productivityData = $servidorController->registerProductivity();
            require __DIR__ . '/../src/views/register_productivity.php';
            break;

        case 'add-minute-type':
            $authController->requireServerAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $servidorController->addMinuteType();
                if ($result['success']) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => $result['error']]);
                }
            }
            exit;

        case 'add-decision-type':
            $authController->requireServerAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $servidorController->addDecisionType();
                if ($result['success']) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => $result['error']]);
                }
            }
            exit;

        case 'inicio':
            $authController->requireServerAuth();
            $dashboardData = $servidorController->getDashboardData();
            require __DIR__ . '/../src/views/dashboard_servidor.php';
            break;

        case 'visualizar-servidor':
            $authController->requireDirectorAuth();
            $serverId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            if ($serverId) {
                $serverData = $diretorController->getServerData($serverId);
                require __DIR__ . '/../src/views/visualizar_servidor.php';
            } else {
                throw new Exception('ID do servidor não fornecido');
            }
            break;

        case 'manage-groups':
            $authController->requireDirectorAuth();
            $allGroups = $groupController->getAllGroups();
            require __DIR__ . '/../src/views/manage_groups.php';
            break;

        case 'delete-group':
            $authController->requireDirectorAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $groupController->deleteGroup();
                if (isset($result['success'])) {
                    $_SESSION['success_message'] = $result['success'];
                } else {
                    $_SESSION['error_message'] = $result['error'];
                }
                // Redirecionar de volta para a página de gerenciamento de grupos
                header('Location: /sistema_produtividade/public/manage-groups');
                exit;
            }
            break;

        case 'create-group':
            $authController->requireDirectorAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $groupController->createGroup();
                if (isset($result['success'])) {
                    $_SESSION['success_message'] = $result['success'];
                } else {
                    $_SESSION['error_message'] = $result['error'];
                }
                // Redirecionar de volta para a mesma página para exibir a mensagem
                header('Location: /sistema_produtividade/public/create-group');
                exit;
            }
            require __DIR__ . '/../src/views/create_group.php';
            break;

        case 'all_groups':
            $authController->requireAuth();
            $allGroups = (new \Jti30\SistemaProdutividade\Models\Group($pdo))->getAllGroups();
            require __DIR__ . '/../src/views/all_groups.php';
            break;

        case 'assign-user-group':
            $authController->requireDirectorAuth();
            $groupModel = new \Jti30\SistemaProdutividade\Models\Group($pdo);
            $userModel = new \Jti30\SistemaProdutividade\Models\User($pdo);

            $allGroups = $groupModel->getAllGroups();
            $allUsers = $userModel->getAllUsers();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $diretorController->assignUserToGroup();
                if (isset($result['success'])) {
                    $_SESSION['success_message'] = $result['success'];
                } else {
                    $_SESSION['error_message'] = $result['error'];
                }
            }
            require __DIR__ . '/../src/views/assign_user_group.php';
            break;

        case 'meu-grupo':
            $authController->requireAuth();
            $userId = $_SESSION['user_id'];
            $groupData = $servidorController->getAssignedGroup($userId);
            require __DIR__ . '/../src/views/view_my_group.php';
            break;

        case 'visualizar-grupo-diretor':
            $authController->requireDirectorAuth();
            $groupId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            if ($groupId) {
                $groupData = $diretorController->getGroupDetails($groupId);
                require __DIR__ . '/../src/views/view_group_director.php';
            } else {
                $_SESSION['error_message'] = 'ID do grupo não fornecido.';
                header('Location: /sistema_produtividade/public/dashboard-diretor');
                exit;
            }
            break;


        case 'detalhes-grupo':
            $authController->requireAuth();
            $groupId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            if ($groupId) {
                $groupDetails = $groupController->getGroupDetails($groupId);
                require __DIR__ . '/../src/views/group_details.php';
            } else {
                echo "ID do grupo não fornecido.";
            }
            break;

        default:
            throw new Exception('Página não encontrada', 404);
    }
} catch (Exception $e) {
    $statusCode = is_numeric($e->getCode()) ? (int)$e->getCode() : 500;
    http_response_code($statusCode);
    $viewFile = __DIR__ . "/../src/views/{$statusCode}.php";
    if (file_exists($viewFile)) {
        require $viewFile;
    } else {
        require __DIR__ . '/../src/views/500.php';
    }
}