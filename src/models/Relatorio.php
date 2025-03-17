<?php

namespace Jti30\SistemaProdutividade\Models;

use PDO;

class Relatorio
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getRelatorioByCreatedAt($userId, $startDate, $endDate)
    {
        $stmt = $this->db->prepare("
        SELECT 
            p.*, 
            mt.name AS minute_type_name, 
            dt.name AS decision_type_name
        FROM productivity p
        JOIN minute_types mt ON p.minute_type_id = mt.id
        JOIN decision_types dt ON p.decision_type_id = dt.id
        WHERE p.user_id = ? AND DATE(p.created_at) BETWEEN ? AND ?
        ORDER BY p.created_at DESC, p.id DESC
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        $productivity_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("
        SELECT 
            SUM(points) as total_points,
            COUNT(*) as total_processes,
            AVG(points) as average_points_per_process
        FROM productivity
        WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'productivity_details' => $productivity_details,
            'total_points' => $summary['total_points'] ?? 0,
            'total_processes' => $summary['total_processes'] ?? 0,
            'average_points_per_process' => $summary['average_points_per_process'] ?? 0,
        ];
    }

    public function getRelatorioDetalhado($userId, $startDate, $endDate)
    {
        $stmt = $this->db->prepare("
        SELECT 
            p.*, 
            mt.name AS minute_type_name, 
            dt.name AS decision_type_name
        FROM productivity p
        JOIN minute_types mt ON p.minute_type_id = mt.id
        JOIN decision_types dt ON p.decision_type_id = dt.id
        WHERE p.user_id = ? AND DATE(p.created_at) BETWEEN ? AND ?
        ORDER BY p.created_at DESC, p.id DESC
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        $productivity_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->db->prepare("
        SELECT 
            SUM(points) as total_points,
            COUNT(*) as total_processes,
            AVG(points) as average_points_per_process
        FROM productivity
        WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $startDate, $endDate]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'productivity_details' => $productivity_details,
            'total_points' => $summary['total_points'] ?? 0,
            'total_processes' => $summary['total_processes'] ?? 0,
            'average_points_per_process' => $summary['average_points_per_process'] ?? 0,
        ];
    }

    public function getDecisionTypesReport($userId, $startDate, $endDate)
    {
        $stmt = $this->db->prepare("
    SELECT 
        dt.id,
        dt.name,
        COUNT(p.id) as count,
        SUM(p.points) as total_points
    FROM decision_types dt
    LEFT JOIN productivity p ON dt.id = p.decision_type_id 
        AND p.user_id = :user_id 
        AND DATE(p.created_at) BETWEEN :start_date AND :end_date
    GROUP BY dt.id, dt.name
    ORDER BY count DESC, total_points DESC
    ");

        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);

        $decisionTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalCount = 0;
        $totalPoints = 0;

        foreach ($decisionTypes as $type) {
            $totalCount += intval($type['count']);
            $totalPoints += intval($type['total_points']);
        }

        foreach ($decisionTypes as &$type) {
            $type['percentage'] = $totalCount > 0 ? ($type['count'] / $totalCount) * 100 : 0;
            $type['average_points'] = $type['count'] > 0 ? $type['total_points'] / $type['count'] : 0;
        }

        return [
            'decision_types' => $decisionTypes,
            'overall_stats' => [
                'total_count' => $totalCount,
                'total_points' => $totalPoints,
                'average_points' => $totalCount > 0 ? $totalPoints / $totalCount : 0
            ]
        ];
    }
}