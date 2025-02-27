<?php

namespace Jti30\SistemaProdutividade\Controllers;

use Jti30\SistemaProdutividade\Models\Group;
use Jti30\SistemaProdutividade\Models\Productivity;
use Jti30\SistemaProdutividade\Models\User;
use PDO;

class DiretorController {
    private $pdo;
    private $authController;

    public function __construct(PDO $pdo, $authController) {
        $this->pdo = $pdo;
        $this->authController = $authController;
    }

    public function getDashboardData() {
        // Exemplo de dados fictícios, substitua com a lógica real
        return [
            'totalPoints' => 1000,
            'totalProcesses' => 150,
            'averageEfficiency' => 85.5,
            'topServers' => [
                ['name' => 'Servidor 1', 'points' => 300, 'processes' => 50, 'efficiency' => 90],
                ['name' => 'Servidor 2', 'points' => 250, 'processes' => 40, 'efficiency' => 88],
                // Adicione mais servidores conforme necessário
            ],
        ];
    }

    public function dashboard() {
        $this->authController->requireDirectorAuth();

        $productivity = new Productivity($this->pdo);

        $totalPoints = $productivity->getTotalPoints();
        $totalProcesses = $productivity->getTotalProcesses();
        $averageEfficiency = $productivity->getAverageEfficiency();
        $topServers = $productivity->getTopServers(10);

        return [
            'totalPoints' => $totalPoints,
            'totalProcesses' => $totalProcesses,
            'averageEfficiency' => $averageEfficiency,
            'topServers' => $topServers
        ];
    }

    public function getGroupDetails($groupId) {
        $groupModel = new Group($this->pdo);  // Alterado de $this->db para $this->pdo
        $productivityModel = new Productivity($this->pdo);  // Alterado de $this->db para $this->pdo

        $group = $groupModel->getGroupById($groupId);
        if (!$group) {
            return null;
        }

        $users = $groupModel->getUsersInGroup($groupId);
        $groupData = [
            'group' => $group,
            'users' => []
        ];

        foreach ($users as $user) {
            $userId = $user['id'];
            $userProductivity = $productivityModel->getUserProductivitySummary($userId);
            $groupData['users'][] = [
                'id' => $userId,
                'name' => $user['name'],
                'points' => $userProductivity['total_points'] ?? 0,
                'completed_processes' => $userProductivity['completed_processes'] ?? 0
            ];
        }

        return $groupData;
    }
    public function showAssignUserToGroupPage() {
        $this->authController->requireDirectorAuth();

        $groupModel = new Group($this->db);
        $userModel = new User($this->db);

        $allGroups = $groupModel->getAllGroups();
        $allUsers = $userModel->getAllUsers();

        require __DIR__ . '/../views/assign_user_group.php';
    }

    public function assignUserToGroup() {
        $this->authController->requireDirectorAuth();

        $group = new Group($this->pdo);
        $user = new User($this->pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $groupId = $_POST['group_id'] ?? '';
            $userId = $_POST['user_id'] ?? '';

            if ($group->addUserToGroup($userId, $groupId)) {
                $_SESSION['success_message'] = 'Usuário atribuído ao grupo com sucesso.';
            } else {
                $_SESSION['error_message'] = 'Erro ao atribuir usuário ao grupo.';
            }
            header('Location: /sistema_produtividade/public/assign-user-group');
            exit;
        }

        $groups = $group->getAllGroups();
        $users = $user->getAllUsers();

        return [
            'groups' => $groups,
            'users' => $users
        ];
    }

    public function generateReports() {
        $this->authController->requireDirectorAuth();

        $productivity = new Productivity($this->pdo);

        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $report = $productivity->getReport($startDate, $endDate);

        return [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }

    public function viewServerDetails($serverId) {
        $this->authController->requireDirectorAuth();

        $user = new User($this->pdo);
        $productivity = new Productivity($this->pdo);

        $serverDetails = $user->findById($serverId);
        if (!$serverDetails) {
            return ['error' => 'Servidor não encontrado.'];
        }

        $productivityDetails = $productivity->getServerProductivity($serverId);

        return [
            'serverDetails' => $serverDetails,
            'productivityDetails' => $productivityDetails
        ];
    }

    public function getServerData($serverId) {
        $user = new User($this->pdo);
        $serverData = $user->findById($serverId);

        if (!$serverData) {
            throw new Exception('Servidor não encontrado');
        }

        return $serverData;
    }

    public function compareServers() {
        $this->authController->requireDirectorAuth();

        $user = new User($this->pdo);
        $productivity = new Productivity($this->pdo);

        $serverIds = $_GET['server_ids'] ?? [];
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');

        $comparisonData = [];
        foreach ($serverIds as $serverId) {
            $serverDetails = $user->findById($serverId);
            $productivityData = $productivity->getServerProductivity($serverId, $startDate, $endDate);
            $comparisonData[] = [
                'serverDetails' => $serverDetails,
                'productivityData' => $productivityData
            ];
        }

        return [
            'comparisonData' => $comparisonData,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
    }
    public function removeUserFromGroup($userId, $groupId) {
        $groupModel = new Group($this->pdo);
        try {
            if ($groupModel->removeUserFromGroup($userId, $groupId)) {
                return ['success' => true, 'message' => 'Usuário removido do grupo com sucesso.'];
            } else {
                return ['success' => false, 'error' => 'Falha ao remover usuário do grupo. O usuário pode não estar no grupo ou pode ter ocorrido um erro no banco de dados.'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Erro ao remover usuário do grupo: ' . $e->getMessage()];
        }
    }

}
