<?php

namespace Jti30\SistemaProdutividade\Controllers;

use Jti30\SistemaProdutividade\Models\Productivity;
use Jti30\SistemaProdutividade\Models\User;
use Jti30\SistemaProdutividade\Models\Group;
use Exception;
use PDO;

class ServidorController {
    private $db;
    private $pdo;
    private $authController;
    private $groupModel;

    public function __construct($db, $authController) {
        $this->db = $db;
        $this->pdo = $db; // Adicionando a inicialização de $pdo
        $this->authController = $authController;
        $this->groupModel = new Group($db);
    }

    public function getMyGroup() {
        $userId = $_SESSION['user_id'];
        $stmt = $this->db->prepare("SELECT g.id, g.name, g.description FROM groups g
                                 JOIN user_groups ug ON g.id = ug.group_id
                                 WHERE ug.user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($group) {
            $stmt = $this->db->prepare("SELECT u.name FROM users u
                                     JOIN user_groups ug ON u.id = ug.user_id
                                     WHERE ug.group_id = :group_id");
            $stmt->execute(['group_id' => $group['id']]);
            $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $group['members'] = $members;
        }

        return $group;
    }

    public function registerProductivity() {
        $this->authController->requireServerAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $userId = $_SESSION['user_id'];
                $processNumber = $_POST['process_number'] ?? '';
                $minuteTypeId = $_POST['minute_type_id'] ?? '';
                $decisionTypeId = $_POST['decision_type_id'] ?? '';

                $productivity = new Productivity($this->db);

                if (!$productivity->minuteTypeExists($minuteTypeId)) {
                    throw new Exception("Tipo de minuta inválido.");
                }

                if (!$productivity->decisionTypeExists($decisionTypeId)) {
                    throw new Exception("Tipo de decisão inválido.");
                }

                // Buscar os pontos da tabela decision_types
                $stmt = $this->db->prepare("SELECT points FROM decision_types WHERE id = :id");
                $stmt->execute(['id' => $decisionTypeId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$result) {
                    throw new Exception("Pontos não encontrados para o tipo de decisão selecionado.");
                }

                $points = $result['points'];

                if ($productivity->registerActivity($userId, $processNumber, $minuteTypeId, $decisionTypeId, $points)) {
                    $_SESSION['success_message'] = 'Produtividade registrada com sucesso.';
                    header('Location: /sistema_produtividade/public/registrar-produtividade');
                    exit;
                } else {
                    throw new Exception("Erro ao registrar produtividade.");
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Erro ao registrar produtividade: ' . $e->getMessage();
                header('Location: /sistema_produtividade/public/registrar-produtividade');
                exit;
            }
        }

        $userId = $_SESSION['user_id'];
        $productivity = new Productivity($this->db);
        return [
            'minuteTypes' => $productivity->getMinuteTypes($userId),
            'decisionTypes' => $productivity->getDecisionTypes($userId)
        ];
    }

    public function getDashboardData() {
        $this->authController->requireServerAuth();

        $userId = $_SESSION['user_id'];
        $productivity = new Productivity($this->db);

        $currentPage = $_GET['page'] ?? 1;
        $limit = 5;
        $offset = ($currentPage - 1) * $limit;

        try {
            $productivityData = $productivity->getProductivityByUserId($userId);
            $recentActivities = $productivity->getRecentActivities($userId, $limit, $offset);
            $monthlyProductivity = $productivity->getMonthlyProductivity($userId);
            $totalActivities = $productivity->getTotalActivities($userId);

            $efficiency = isset($productivityData['averagePoints']) && $productivityData['averagePoints'] > 0
                ? ($productivityData['totalPoints'] / ($productivityData['completedProcesses'] * $productivityData['averagePoints'])) * 100
                : 0;

            $totalPages = ceil($totalActivities / $limit);

            return [
                'totalPoints' => $productivityData['totalPoints'] ?? 0,
                'completedProcesses' => $productivityData['completedProcesses'] ?? 0,
                'efficiency' => $efficiency,
                'recentActivities' => $recentActivities,
                'monthlyProductivity' => $monthlyProductivity,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalActivities' => $totalActivities
            ];
        } catch (Exception $e) {
            return [
                'totalPoints' => 0,
                'completedProcesses' => 0,
                'efficiency' => 0,
                'recentActivities' => [],
                'monthlyProductivity' => [],
                'currentPage' => 1,
                'totalPages' => 1,
                'totalActivities' => 0,
                'error' => 'Erro ao obter dados do dashboard: ' . $e->getMessage()
            ];
        }
    }

    public function getRecentActivities($userId, $limit = 5, $offset = 0) {
        $stmt = $this->db->prepare("
        SELECT 
            p.process_number, 
            mt.name AS minute_type, 
            dt.name AS decision_type, 
            p.points, 
            p.created_at
        FROM 
            productivity p
        JOIN 
            minute_types mt ON p.minute_type_id = mt.id
        JOIN 
            decision_types dt ON p.decision_type_id = dt.id
        WHERE 
            p.user_id = ?
        ORDER BY 
            p.created_at DESC
        LIMIT ? OFFSET ?
    ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addMinuteType() {
        $this->authController->requireServerAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $minuteTypeName = $_POST['new_minute_type'] ?? '';
            $userId = $_SESSION['user_id'];

            if ($minuteTypeName) {
                try {
                    $productivity = new Productivity($this->db);
                    $newId = $productivity->addMinuteType($minuteTypeName, $userId);

                    if ($newId) {
                        return [
                            'success' => true,
                            'id' => $newId,
                            'name' => $minuteTypeName
                        ];
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Erro ao adicionar tipo de minuta.'
                        ];
                    }
                } catch (Exception $e) {
                    return [
                        'success' => false,
                        'error' => 'Erro ao adicionar tipo de minuta: ' . $e->getMessage()
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'Nome do tipo de minuta é obrigatório.'
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'Método não permitido.'
        ];
    }
    public function getAssignedGroup($userId) {
        try {
            $stmt = $this->db->prepare("
        SELECT g.* 
        FROM groups g
        JOIN group_users gu ON g.id = gu.group_id
        WHERE gu.user_id = :userId
    ");
            $stmt->execute(['userId' => $userId]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($group) {
                // Fetch group members with their points and completed processes
                $stmtMembers = $this->db->prepare("
            SELECT u.id, u.name, 
                   COALESCE(SUM(p.points), 0) as points, 
                   COUNT(p.id) as completed_processes
            FROM users u
            JOIN group_users gu ON u.id = gu.user_id
            LEFT JOIN productivity p ON u.id = p.user_id
            WHERE gu.group_id = :groupId
            GROUP BY u.id, u.name
        ");
                $stmtMembers->execute(['groupId' => $group['id']]);
                $users = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

                // Fetch total points for the group
                $stmtPoints = $this->db->prepare("
            SELECT SUM(points) as total_points
            FROM productivity
            WHERE user_id IN (
                SELECT user_id 
                FROM group_users 
                WHERE group_id = :groupId
            )
        ");
                $stmtPoints->execute(['groupId' => $group['id']]);
                $totalPoints = $stmtPoints->fetchColumn();

                return [
                    'group' => $group,
                    'users' => $users,
                    'totalPoints' => $totalPoints
                ];
            }
            return ['error' => 'Grupo não encontrado para este usuário.'];
        } catch (PDOException $e) {
            return ['error' => 'Erro ao buscar dados do grupo: ' . $e->getMessage()];
        }
    }

    public function getActivities() {
        $userId = $_SESSION['user_id'];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 5; // Número de atividades por página
        $offset = ($page - 1) * $limit;

        $activities = $this->getRecentActivities($userId, $limit, $offset);
        $totalActivities = $this->getTotalActivities($userId);
        $totalPages = ceil($totalActivities / $limit);

        header('Content-Type: application/json');
        echo json_encode([
            'activities' => $activities,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    private function getTotalActivities($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM productivity WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }


    public function addDecisionType() {
        $this->authController->requireServerAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $decisionTypeName = $_POST['new_decision_type'] ?? '';
            $points = $_POST['points'] ?? 0;

            if ($decisionTypeName) {
                $stmt = $this->db->prepare("INSERT INTO decision_types (name, points) VALUES (?, ?)");
                $stmt->execute([$decisionTypeName, $points]);
                return ['success' => true];
            } else {
                return ['error' => 'Nome do tipo de decisão é obrigatório.'];
            }
        }
    }
}