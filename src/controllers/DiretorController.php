<?php

namespace Jti30\SistemaProdutividade\Controllers;

use Jti30\SistemaProdutividade\Models\Group;
use Jti30\SistemaProdutividade\Models\Productivity;
use Jti30\SistemaProdutividade\Models\User;
use PDO;
use Exception;

class DiretorController {
    private $pdo;
    private $authController;
    private $baseUrl;

    public function __construct(PDO $pdo, $authController) {
        $this->pdo = $pdo;
        $this->authController = $authController;
        $this->baseUrl = $this->authController->getBaseUrlForViews();
    }

    public function dashboard($year = null) {
        $productivity = new Productivity($this->pdo);
        $groupModel = new Group($this->pdo);

        $year = $year ?? date('Y');
        $monthlyProductivity = $this->getYearlyProductivity($year);

        $totalPoints = array_sum(array_column($monthlyProductivity, 'totalPoints'));
        $totalProcesses = array_sum(array_column($monthlyProductivity, 'totalProcesses'));
        $averageEfficiency = $productivity->getAverageEfficiency();
        $topServers = $productivity->getTopServers(5, $totalPoints);
        $groupProductivity = $groupModel->getGroupProductivity();

        return [
            'totalPoints' => $totalPoints,
            'totalProcesses' => $totalProcesses,
            'averageEfficiency' => $averageEfficiency,
            'topServers' => $topServers,
            'groupProductivity' => $groupProductivity,
            'monthlyProductivity' => $monthlyProductivity,
            'currentYear' => $year
        ];
    }

    public function getDashboardData() {
        $productivity = new Productivity($this->pdo);
        $groupModel = new Group($this->pdo);

        $currentYear = date('Y');
        $monthlyProductivity = $this->getMonthlyProductivity($currentYear);

        $totalPoints = array_sum(array_column($monthlyProductivity, 'totalPoints'));
        $totalProcesses = array_sum(array_column($monthlyProductivity, 'totalProcesses'));
        $averageEfficiency = $productivity->getAverageEfficiency();
        $topServers = $productivity->getTopServers(5, $totalPoints);
        $groupProductivity = $groupModel->getGroupProductivity();

        return [
            'totalPoints' => $totalPoints,
            'totalProcesses' => $totalProcesses,
            'averageEfficiency' => $averageEfficiency,
            'topServers' => $topServers,
            'groupProductivity' => $groupProductivity,
            'monthlyProductivity' => $monthlyProductivity,
            'currentYear' => $currentYear
        ];
    }


    public function getProductivityData($year, $month) {
        error_log("Ano: $year, Mês: $month"); // Log dos parâmetros de entrada

        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        error_log("Data inicial: $startDate, Data final: $endDate"); // Log das datas calculadas

        $stmt = $this->pdo->prepare("
        SELECT SUM(points) as total_points, COUNT(DISTINCT process_number) as total_processes
        FROM productivity
        WHERE date BETWEEN :start_date AND :end_date
        ");
        $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        error_log("Resultado da consulta: " . print_r($result, true)); // Log do resultado da consulta

        $returnData = [
            'totalPoints' => $result['total_points'] ?? 0,
            'totalProcesses' => $result['total_processes'] ?? 0
        ];

        error_log("Dados retornados: " . print_r($returnData, true)); // Log dos dados retornados

        return $returnData;
    }

    public function getGroupDetails($groupId) {
        $groupModel = new Group($this->pdo);
        $productivityModel = new Productivity($this->pdo);

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
            header('Location: ' . $this->baseUrl . '/assign-user-group');
            exit;
        }

        $groups = $group->getAllGroups();
        $users = $user->getAllUsers();

        return [
            'groups' => $groups,
            'users' => $users
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
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao remover usuário do grupo: ' . $e->getMessage()];
        }
    }

    public function getYearlyProductivity($year) {
        error_log("Iniciando getYearlyProductivity para o ano: $year");
        $monthlyProductivity = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            $stmt = $this->pdo->prepare("
        SELECT SUM(points) as total_points, COUNT(DISTINCT process_number) as total_processes
        FROM productivity
        WHERE created_at BETWEEN :start_date AND :end_date
        ");
            $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate . ' 23:59:59']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            error_log("Resultado para $year-$month: " . json_encode($result));

            $monthlyProductivity[$month] = [
                'totalPoints' => $result['total_points'] ?? 0,
                'totalProcesses' => $result['total_processes'] ?? 0
            ];
        }

        error_log("Dados finais: " . json_encode($monthlyProductivity));
        return $monthlyProductivity;
    }

    private function getMonthlyProductivity($year) {
        $productivity = new Productivity($this->pdo);
        $monthlyProductivity = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            $stmt = $this->pdo->prepare("
            SELECT SUM(points) as total_points, COUNT(DISTINCT process_number) as total_processes
            FROM productivity
            WHERE date BETWEEN :start_date AND :end_date
        ");
            $stmt->execute([':start_date' => $startDate, ':end_date' => $endDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $monthlyProductivity[$month] = [
                'totalPoints' => $result['total_points'] ?? 0,
                'totalProcesses' => $result['total_processes'] ?? 0
            ];
        }

        return $monthlyProductivity;
    }
}