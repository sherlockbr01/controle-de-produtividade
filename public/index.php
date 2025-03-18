<?php
session_start();

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/config/database.php';

use Jti30\SistemaProdutividade\Controllers\AuthController;
use Jti30\SistemaProdutividade\Controllers\ServidorController;
use Jti30\SistemaProdutividade\Controllers\DiretorController;
use Jti30\SistemaProdutividade\Controllers\GroupController;
use Jti30\SistemaProdutividade\Controllers\FeriasAfastamentoController;
use Jti30\SistemaProdutividade\Controllers\RelatorioController;

// Definir a URL base do projeto
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];

    // Detectar se estamos em um ambiente de hospedagem comum
    $isSharedHosting = !in_array($host, ['localhost', '127.0.0.1']) &&
        !preg_match('/^localhost:[0-9]+$/', $host);

    if ($isSharedHosting) {
        // Em hospedagem compartilhada, geralmente queremos a raiz do domínio
        return $protocol . $host;
    } else {
        // Em ambiente local
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $dirName = dirname($scriptName);

        // Se estiver na raiz do domínio
        if ($dirName == '/' || $dirName == '\\') {
            return $protocol . $host;
        }

        // Caso contrário, retorna o caminho completo
        return $protocol . $host . $dirName;
    }
}


// Verificar se a constante BASE_URL já está definida
if (!defined('BASE_URL')) {
    // Definir a constante BASE_URL usando a função getBaseUrl()
    define('BASE_URL', getBaseUrl());

}
// Criar conexão com o banco de dados
$pdo = connectDatabase();

// Instanciar os controllers
$authController = new AuthController($pdo);
$servidorController = new ServidorController($pdo, $authController);
$diretorController = new DiretorController($pdo, $authController);
$groupController = new GroupController($pdo, $authController);
$relatorioController = new RelatorioController($pdo, $authController);
$feriasAfastamentoController = new FeriasAfastamentoController($pdo);

// Obter a URI da requisição
$request = $_SERVER['REQUEST_URI'];

// Extrair o caminho da requisição removendo a URL base e a query string
$basePath = parse_url(BASE_URL, PHP_URL_PATH);
$path = parse_url($request, PHP_URL_PATH);

// Remover o caminho base da URI
if (!empty($basePath) && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Remover barras no início e no final da string
$request = trim($path, '/');

// Se a requisição estiver vazia, defina-a como 'login'
if (empty($request)) {
    $request = 'login';
}

// Função auxiliar para redirecionar
function redirect($path) {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
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
                    redirect('login');
                }
            }
            require __DIR__ . '/../src/views/login.php';
            break;

        case 'logout':
            $authController->logout();
            redirect('login');

        case 'dashboard-servidor':
            $authController->requireServerAuth();
            $dashboardData = $servidorController->getDashboardData();
            $dashboardData['recentActivities'] = $servidorController->getRecentActivities($_SESSION['user_id']);
            require __DIR__ . '/../src/views/dashboard_servidor.php';
            break;

        case 'dashboard-diretor':
            $authController->requireDirectorAuth();
            $data = $diretorController->getDashboardData();

            // Adicione esta verificação
            if (!isset($data['groupProductivity'])) {
                $data['groupProductivity'] = [];
            }

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
                redirect('perfil');
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
                    redirect('dashboard-servidor');
                } else {
                    $_SESSION['productivity_error'] = $result['error'];
                    redirect('registrar-produtividade');
                }
            }
            $productivityData = $servidorController->registerProductivity();
            require __DIR__ . '/../src/views/register_productivity.php';
            break;

        case 'add-minute-type':
            $authController->requireServerAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                $result = $servidorController->addMinuteType();
                echo json_encode($result);
            } else {
                http_response_code(405); // Method Not Allowed
                echo json_encode(['error' => 'Método não permitido']);
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
                redirect('manage-groups');
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
                redirect('create-group');
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

        case 'remove-user-from-group':
            $authController->requireDirectorAuth();
            $userId = filter_input(INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT);
            $groupId = filter_input(INPUT_GET, 'group_id', FILTER_SANITIZE_NUMBER_INT);
            if ($userId && $groupId) {
                $result = $diretorController->removeUserFromGroup($userId, $groupId);
                if ($result['success']) {
                    $_SESSION['success_message'] = "Usuário removido do grupo com sucesso.";
                } else {
                    $_SESSION['error_message'] = $result['error'] ?? "Erro ao remover usuário do grupo.";
                }
            } else {
                $_SESSION['error_message'] = "ID do usuário ou do grupo não fornecido.";
            }
            redirect("visualizar-grupo-diretor?id=" . $groupId);

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
                redirect('dashboard-diretor');
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

        case 'gestao-ferias-afastamentos':
            $authController->requireServerAuth(); // Apenas servidores podem acessar
            $userData = $feriasAfastamentoController->listarFeriasAfastamentos($_SESSION['user_id']);
            require __DIR__ . '/../src/views/gestao_ferias_afastamentos.php';
            break;

        case 'gerenciar-ferias-afastamento':
            $authController->requireDirectorAuth();
            $gestaoData = $feriasAfastamentoController->getGestaoFeriasAfastamentosData();

            // Garantir que 'currentLeaves' existe no array, mesmo que vazio
            if (!isset($gestaoData['currentLeaves'])) {
                $gestaoData['currentLeaves'] = [];
            }

            // Adicionar mensagens de sucesso ou erro, se existirem
            $gestaoData['success_message'] = $_SESSION['success_message'] ?? null;
            $gestaoData['error_message'] = $_SESSION['error_message'] ?? null;

            // Limpar as mensagens da sessão após usá-las
            unset($_SESSION['success_message'], $_SESSION['error_message']);

            require __DIR__ . '/../src/views/manage_ferias_afastamento.php';
            break;

        case 'submit-ferias-afastamento':
            $authController->requireServerAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = $feriasAfastamentoController->registrarFeriasAfastamento(
                    $_SESSION['user_id'],
                    $_POST['tipo_afastamento'],
                    $_POST['data_inicio'],
                    $_POST['data_termino'],
                    $_POST['comentario']
                );

                if ($result) {
                    $_SESSION['success_message'] = 'Solicitação de férias/afastamento registrada com sucesso.';
                } else {
                    $_SESSION['error_message'] = 'Erro ao registrar a solicitação de férias/afastamento.';
                }

                redirect('gestao-ferias-afastamentos');
            }
            break;

        case 'processar-solicitacao-ferias-afastamento':
            $authController->requireDirectorAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $solicitacaoId = filter_input(INPUT_POST, 'solicitacao_id', FILTER_SANITIZE_NUMBER_INT);
                $acao = filter_input(INPUT_POST, 'acao', FILTER_SANITIZE_STRING);

                if ($solicitacaoId && $acao) {
                    try {
                        if ($acao === 'aprovar') {
                            $feriasAfastamentoController->aprovarSolicitacao($solicitacaoId);
                            $_SESSION['success_message'] = 'Solicitação aprovada com sucesso.';
                        } elseif ($acao === 'rejeitar') {
                            $feriasAfastamentoController->rejeitarSolicitacao($solicitacaoId);
                            $_SESSION['success_message'] = 'Solicitação rejeitada com sucesso.';
                        }
                    } catch (Exception $e) {
                        $_SESSION['error_message'] = 'Erro ao processar a solicitação: ' . $e->getMessage();
                    }
                } else {
                    $_SESSION['error_message'] = 'Dados inválidos para processar a solicitação.';
                }

                redirect('gerenciar-ferias-afastamento');
            }
            break;

        case 'relatorio-detalhado':
            $authController->requireAuth();
            $userId = $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $reportType = $_GET['report_type'] ?? 'default';

            $dadosRelatorio = $relatorioController->getDadosPagina($startDate, $endDate, $userId, $reportType);

            switch ($reportType) {
                case 'created_at':
                    require __DIR__ . '/../src/views/relatorio_created_at.php';
                    break;
                case 'tipos_decisoes':
                    require __DIR__ . '/../src/views/relatorio_tipos_decisoes.php';
                    break;
                default:
                    require __DIR__ . '/../src/views/relatorio_detalhado.php';
                    break;
            }
            break;

        case 'get-activities':
            $authController->requireServerAuth();
            $servidorController->getActivities();
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