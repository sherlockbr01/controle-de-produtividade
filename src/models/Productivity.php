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

    public function getUserTotalProductivity($userId) {
        $stmt = $this->db->prepare("SELECT SUM(points) as totalPoints, COUNT(*) as completedProcesses FROM productivity WHERE user_id = :userId");
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRecentActivities($userId) {
        $stmt = $this->db->prepare("SELECT process_number, minute_type, decision_type, points, created_at FROM productivity WHERE user_id = :userId ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([':userId' => $userId]);
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


    public function getProductivityByUserId($userId) {
        $stmt = $this->db->prepare("SELECT SUM(points) as totalPoints, COUNT(*) as completedProcesses FROM productivity WHERE user_id = :userId");
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function getProductivityBetweenDates($startDate, $endDate)
    {
        $stmt = $this->pdo->prepare("
        SELECT SUM(points) as totalPoints, COUNT(*) as totalProcesses
        FROM productivity
        WHERE date BETWEEN :startDate AND :endDate
    ");
        $stmt->execute([':startDate' => $startDate, ':endDate' => $endDate]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function getProductivityByDateRange($startDate, $endDate) {
        $query = "SELECT SUM(points) as totalPoints, COUNT(DISTINCT process_number) as totalProcesses
              FROM productivity
              WHERE created_at BETWEEN :startDate AND :endDate";

        $stmt = $this->db->prepare($query);
        $stmt->execute([':startDate' => $startDate, ':endDate' => $endDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'totalPoints' => $result['totalPoints'] ?? 0,
            'totalProcesses' => $result['totalProcesses'] ?? 0
        ];
    }

    public function getUserMonthlyProductivity($userId, $year) {
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            $stmt = $this->db->prepare("
            SELECT SUM(points) as total_points, COUNT(DISTINCT process_number) as total_processes
            FROM productivity
            WHERE user_id = :user_id AND date BETWEEN :start_date AND :end_date
        ");
            $stmt->execute([':user_id' => $userId, ':start_date' => $startDate, ':end_date' => $endDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $monthlyData[$month] = [
                'totalPoints' => $result['total_points'] ?? 0,
                'totalProcesses' => $result['total_processes'] ?? 0
            ];
        }

        return $monthlyData;
    }

}