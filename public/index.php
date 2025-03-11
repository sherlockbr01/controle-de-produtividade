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




// Criar conexão com o banco de dados
$pdo = connectDatabase();

// Instanciar os controllers
$authController = new AuthController($pdo);
$servidorController = new ServidorController($pdo, $authController);
$diretorController = new DiretorController($pdo, $authController);
$groupController = new GroupController($pdo, $authController);
$relatorioController = new RelatorioController($pdo, $authController);




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
            // Redirecionar de volta para a página do grupo
            header("Location: /sistema_produtividade/public/visualizar-grupo-diretor?id=" . $groupId);
            exit;


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

        case 'gestao-ferias-afastamentos':
            $authController->requireServerAuth(); // Apenas servidores podem acessar
            $feriasAfastamentoController = new FeriasAfastamentoController($pdo);
            $userData = $feriasAfastamentoController->listarFeriasAfastamentos($_SESSION['user_id']);
            require __DIR__ . '/../src/views/gestao_ferias_afastamentos.php';
            break;

        case 'gerenciar-ferias-afastamento':
            $authController->requireDirectorAuth();
            $feriasAfastamentoController = new FeriasAfastamentoController($pdo);
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
                $feriasAfastamentoController = new FeriasAfastamentoController($pdo);
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

                // Redirecionar para a página de gestão de férias e afastamentos
                header('Location: /sistema_produtividade/public/gestao-ferias-afastamentos');
                exit;
            }
            break;


        case 'submit-ferias-afastamento':
            $authController->requireServerAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $feriasAfastamentoController = new FeriasAfastamentoController($pdo);
                $result = $feriasAfastamentoController->registrarFeriasAfastamento(
                    $_SESSION['user_id'],
                    $_POST['tipo_afastamento'],
                    $_POST['data_inicio'],
                    $_POST['data_termino'],
                    $_POST['comentario']
                );
                echo json_encode(['success' => $result]);
            }
            exit;

        case 'processar-solicitacao-ferias-afastamento':
            $authController->requireDirectorAuth();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $feriasAfastamentoController = new FeriasAfastamentoController($pdo);
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

                // Redirecionar de volta para a página de gerenciamento
                header('Location: /sistema_produtividade/public/gerenciar-ferias-afastamento');
                exit;
            }
            break;

        case 'relatorio-detalhado':
            $userId = $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $relatorioDetalhado = $relatorioController->gerarRelatorioByCreatedAt($userId, $startDate, $endDate);
            require __DIR__ . '/../src/views/relatorio_detalhado.php';
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