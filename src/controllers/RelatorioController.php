<?php

namespace Jti30\SistemaProdutividade\Controllers;

use Jti30\SistemaProdutividade\Models\Relatorio;
use Jti30\SistemaProdutividade\Models\User;
use Exception;
use PDO;

class RelatorioController {
    private PDO $pdo;
    private $authController;
    private Relatorio $relatorioModel;

    public function __construct(PDO $pdo, $authController) {
        $this->pdo = $pdo;
        $this->authController = $authController;
        $this->relatorioModel = new Relatorio($pdo);
    }

    public function getDadosPagina($startDate = null, $endDate = null, $selectedUserId = null, $reportType = 'default') {
        $users = $this->getAllUsers();
        $relatorioData = null;
        $userName = null;

        if ($startDate && $endDate && $selectedUserId) {
            // Obter o nome do usuário selecionado
            foreach ($users as $user) {
                if ($user['id'] == $selectedUserId) {
                    $userName = $user['name'];
                    break;
                }
            }

            switch ($reportType) {
                case 'created_at':
                    $relatorioData = $this->gerarRelatorioByCreatedAt($selectedUserId, $startDate, $endDate);
                    break;
                case 'decisions':
                    $relatorioData = $this->gerarRelatorioTiposDecisao($selectedUserId, $startDate, $endDate);
                    $relatorioData['userName'] = $userName;
                    break;
                case 'productivity':
                    $relatorioData = $this->gerarRelatorioDetalhado($selectedUserId, $startDate, $endDate);
                    break;
                default:
                    $relatorioData = $this->gerarRelatorioDetalhado($selectedUserId, $startDate, $endDate);
                    break;
            }
        }

        return [
            'users' => $users,
            'relatorioData' => $relatorioData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedUserId' => $selectedUserId,
            'reportType' => $reportType,
            'userName' => $userName
        ];
    }

    private function getUserName($userId) {
        $stmt = $this->pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['name'] : '';
    }

    public function gerarRelatorioDetalhado($userId, $startDate, $endDate) {
        try {
            return $this->relatorioModel->getRelatorioDetalhado($userId, $startDate, $endDate);
        } catch (Exception $e) {
            return [
                'error' => 'Erro ao gerar relatório detalhado: ' . $e->getMessage()
            ];
        }
    }

    public function gerarRelatorioByCreatedAt($userId = null, $startDate = null, $endDate = null) {
        if (!$userId) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        if (!$startDate) {
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
        }
        if (!$endDate) {
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
        }

        if (!$userId) {
            return ['error' => 'Usuário não autenticado'];
        }

        try {
            $query = "SELECT p.*, u.name as userName, dt.name as decisionTypeName, mt.name as minuteTypeName
                      FROM productivity p
                      JOIN users u ON p.user_id = u.id
                      JOIN decision_types dt ON p.decision_type_id = dt.id
                      JOIN minute_types mt ON p.minute_type_id = mt.id
                      WHERE p.user_id = :userId
                      AND p.created_at BETWEEN :startDate AND :endDate
                      ORDER BY p.created_at";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                ':userId' => $userId,
                ':startDate' => $startDate,
                ':endDate' => $endDate
            ]);

            $processos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalPontos = 0;
            $totalProcessos = count($processos);

            foreach ($processos as &$processo) {
                $totalPontos += $processo['points'];
                $processo['data'] = date('d/m/Y', strtotime($processo['created_at']));
            }

            $mediaPontos = $totalProcessos > 0 ? $totalPontos / $totalProcessos : 0;

            return [
                'success' => true,
                'data' => [
                    'userName' => $processos[0]['userName'] ?? '',
                    'totalPontos' => $totalPontos,
                    'totalProcessos' => $totalProcessos,
                    'mediaPontos' => $mediaPontos,
                    'processos' => $processos
                ],
                'startDate' => $startDate,
                'endDate' => $endDate
            ];
        } catch (Exception $e) {
            return [
                'error' => 'Erro ao gerar relatório: ' . $e->getMessage()
            ];
        }
    }

    public function getAllUsers() {
        $stmt = $this->pdo->prepare("SELECT id, name FROM users");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function gerarRelatorioUsuario($userId = null, $startDate = null, $endDate = null) {
        if (!$userId) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        if (!$userId) {
            return ['error' => 'Usuário não autenticado'];
        }

        try {
            return $this->relatorioModel->getRelatorioUsuario($userId, $startDate, $endDate);
        } catch (Exception $e) {
            return [
                'error' => 'Erro ao gerar relatório do usuário: ' . $e->getMessage()
            ];
        }
    }

    public function gerarRelatorioGrupo($groupId = null, $startDate = null, $endDate = null) {
        try {
            return $this->relatorioModel->getRelatorioGrupo($groupId, $startDate, $endDate);
        } catch (Exception $e) {
            return [
                'error' => 'Erro ao gerar relatório do grupo: ' . $e->getMessage()
            ];
        }
    }

    public function gerarRelatorioTiposDecisao($userId = null, $startDate = null, $endDate = null) {
        if (!$userId) {
            $userId = $_SESSION['user_id'] ?? null;
        }
        if (!$userId) {
            return ['error' => 'Usuário não autenticado'];
        }

        try {
            return $this->relatorioModel->getDecisionTypesReport($userId, $startDate, $endDate);
        } catch (Exception $e) {
            return [
                'error' => 'Erro ao gerar relatório de tipos de decisão: ' . $e->getMessage(),
                'decision_types' => [],
                'overall_stats' => [
                    'total_count' => 0,
                    'total_points' => 0,
                    'average_points' => 0
                ]
            ];
        }
    }
}