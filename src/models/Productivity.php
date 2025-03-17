<?php

namespace Jti30\SistemaProdutividade\Models;

use PDO;

class Productivity
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getReport($startDate, $endDate) {
        $stmt = $this->db->prepare("
        SELECT 
            u.name as user_name, 
            SUM(p.points) as total_points, 
            COUNT(p.id) as total_processes
        FROM 
            productivity p
        JOIN 
            users u ON p.user_id = u.id
        WHERE 
            p.date BETWEEN ? AND ?
        GROUP BY 
            u.id, u.name
        ORDER BY 
            total_points DESC
    ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getServerProductivity($serverId, $startDate = null, $endDate = null) {
        $query = "
        SELECT 
            SUM(points) as total_points, 
            COUNT(id) as total_processes, 
            AVG(points) as average_points
        FROM 
            productivity
        WHERE 
            user_id = ?
    ";

        $params = [$serverId];

        if ($startDate && $endDate) {
            $query .= " AND date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
    public function getMonthlyProductivity($userId) {
        $stmt = $this->db->prepare("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month, 
            SUM(points) as points
        FROM productivity
        WHERE user_id = ?
        GROUP BY month
        ORDER BY month
    ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getUserTotalPoints($userId)
    {
        $stmt = $this->db->prepare("SELECT SUM(points) as total FROM productivity WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getUserCompletedProcesses($userId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM productivity WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getUserEfficiency($userId)
    {
        $stmt = $this->db->prepare("SELECT AVG(points) as efficiency FROM productivity WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['efficiency'] ?? 0;
    }

    public function getUserMonthlyProductivity($userId)
    {
        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(date, '%Y-%m') as month, SUM(points) as total
            FROM productivity
            WHERE user_id = ?
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserRecentActivities($userId, $limit)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM productivity
            WHERE user_id = ?
            ORDER BY date DESC, id DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registerActivity($userId, $processNumber, $minuteTypeId, $decisionTypeId)
    {
        try {
            $this->db->beginTransaction();

            // Buscar os pontos associados ao tipo de decisão
            $stmtPoints = $this->db->prepare("SELECT points FROM decision_types WHERE id = ?");
            $stmtPoints->execute([$decisionTypeId]);
            $result = $stmtPoints->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new Exception("Tipo de decisão não encontrado.");
            }

            $points = $result['points'];

            // Inserir a atividade com os pontos extraídos
            $sql = "INSERT INTO productivity (user_id, process_number, minute_type_id, decision_type_id, points) 
            VALUES (:user_id, :process_number, :minute_type_id, :decision_type_id, :points)";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':process_number', $processNumber, PDO::PARAM_STR);
            $stmt->bindParam(':minute_type_id', $minuteTypeId, PDO::PARAM_INT);
            $stmt->bindParam(':decision_type_id', $decisionTypeId, PDO::PARAM_INT);
            $stmt->bindParam(':points', $points, PDO::PARAM_INT);

            $result = $stmt->execute();

            if (!$result) {
                throw new Exception("Falha ao inserir a atividade.");
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erro ao registrar atividade: " . $e->getMessage());
            return false;
        }
    }

    public function getTotalActivities($userId = null)
    {
        $sql = "SELECT COUNT(*) as total FROM productivity";
        $params = [];

        if ($userId !== null) {
            $sql .= " WHERE user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }


    public function addMinuteType($name, $userId) {
        $sql = "INSERT INTO minute_types (name, user_id) VALUES (:name, :user_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }

        return false;
    }
    public function getMinuteTypes($userId)
    {
        $sql = "SELECT id, name FROM minute_types WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getDecisionTypes()
    {
        $sql = "SELECT id, name FROM decision_types";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function getUserHistory($userId, $startDate, $endDate)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM productivity
            WHERE user_id = ? AND date BETWEEN ? AND ?
            ORDER BY date DESC, id DESC
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserSummary($userId, $startDate, $endDate)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_processes,
                SUM(points) as total_points,
                AVG(points) as average_points
            FROM productivity
            WHERE user_id = ? AND date BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserProductivitySummary($userId) {
        $stmt = $this->db->prepare("
        SELECT 
            COALESCE(SUM(points), 0) as total_points,
            COUNT(*) as completed_processes
        FROM productivity
        WHERE user_id = :userId
    ");
        $stmt->execute(['userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserPerformanceData($userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                SUM(points) as total_points,
                COUNT(*) as total_processes,
                AVG(points) as average_points
            FROM productivity
            WHERE user_id = ?
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month DESC
            LIMIT 6
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getProductivityByUserId($userId) {
        $stmt = $this->db->prepare("
        SELECT 
            SUM(points) as totalPoints, 
            COUNT(DISTINCT process_number) as completedProcesses, 
            AVG(points) as averagePoints
        FROM productivity
        WHERE user_id = ?
    ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function decisionTypeExists($decisionTypeId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM decision_types WHERE id = :id");
        $stmt->bindParam(':id', $decisionTypeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function minuteTypeExists($minuteTypeId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM minute_types WHERE id = :id");
        $stmt->bindParam(':id', $minuteTypeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function getTotalPoints()
    {
        $stmt = $this->db->query("SELECT SUM(points) as total FROM productivity");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getTotalProcesses()
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM productivity");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function getAverageEfficiency()
    {
        $stmt = $this->db->query("SELECT AVG(points) as average FROM productivity");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['average'] ?? 0;
    }

    public function getTopServers($limit)
    {
        $stmt = $this->db->prepare("
            SELECT 
                u.id, u.name, SUM(p.points) as total_points
            FROM 
                users u
            JOIN 
                productivity p ON u.id = p.user_id
            GROUP BY 
                u.id, u.name
            ORDER BY 
                total_points DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGroupProductivityDetails($groupId)
    {
        $stmt = $this->db->prepare("
        SELECT 
            g.id as group_id, 
            g.name as group_name, 
            u.id as user_id,
            u.name as user_name,
            COALESCE(SUM(p.points), 0) as total_points,
            COUNT(DISTINCT p.id) as completed_processes
        FROM 
            groups g
        JOIN 
            group_users gu ON g.id = gu.group_id
        JOIN 
            users u ON gu.user_id = u.id
        LEFT JOIN 
            productivity p ON u.id = p.user_id
        WHERE 
            g.id = :group_id
        GROUP BY 
            g.id, g.name, u.id, u.name
        ORDER BY 
            total_points DESC
    ");
        $stmt->execute(['group_id' => $groupId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtTotal = $this->db->prepare("
        SELECT 
            SUM(p.points) as group_total_points,
            COUNT(DISTINCT p.id) as group_total_processes
        FROM 
            groups g
        JOIN 
            group_users gu ON g.id = gu.group_id
        JOIN 
            users u ON gu.user_id = u.id
        LEFT JOIN 
            productivity p ON u.id = p.user_id
        WHERE 
            g.id = :group_id
    ");
        $stmtTotal->execute(['group_id' => $groupId]);
        $groupTotal = $stmtTotal->fetch(PDO::FETCH_ASSOC);

        return [
            'users' => $users,
            'group_total' => $groupTotal
        ];
    }



    public function getProductivityByGroup()
    {
        $stmt = $this->db->query("
            SELECT 
                g.id as group_id, 
                g.name as group_name, 
                SUM(p.points) as total_points
            FROM 
                groups g
            JOIN 
                user_groups ug ON g.id = ug.group_id
            JOIN 
                productivity p ON ug.user_id = p.user_id
            GROUP BY 
                g.id, g.name
            ORDER BY 
                total_points DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function calculatePoints($minuteType, $decisionType)
    {
        // Implemente a lógica de cálculo de pontos aqui
        // Este é apenas um exemplo, ajuste conforme suas regras de negócio
        $basePoints = 10;
        $minuteTypeMultiplier = 1;
        $decisionTypeMultiplier = 1;

        // Ajuste os multiplicadores com base no tipo de minuta e decisão
        switch ($minuteType) {
            case 'complexa':
                $minuteTypeMultiplier = 2;
                break;
            case 'simples':
                $minuteTypeMultiplier = 1;
                break;
            // Adicione mais casos conforme necessário
        }

        switch ($decisionType) {
            case 'favoravel':
                $decisionTypeMultiplier = 1.5;
                break;
            case 'desfavoravel':
                $decisionTypeMultiplier = 1;
                break;
            // Adicione mais casos conforme necessário
        }

        return $basePoints * $minuteTypeMultiplier * $decisionTypeMultiplier;
    }
}