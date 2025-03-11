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


}