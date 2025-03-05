<?php

namespace Jti30\SistemaProdutividade\Controllers;

use Jti30\SistemaProdutividade\Models\FeriasAfastamento;
use PDO;

class FeriasAfastamentoController
{
    private $pdo;
    private $authController;
    private $model;

    public function __construct(PDO $pdo, $authController = null)
    {
        $this->pdo = $pdo;
        $this->authController = $authController;
        $this->model = new FeriasAfastamento($pdo);
    }

    public function getDadosPagina()
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            return ['error' => 'Usuário não autenticado'];
        }

        $afastamentos = $this->getUserAfastamentos($userId);
        $tiposAfastamento = $this->getTiposAfastamento();
        $gestaoData = $this->getGestaoFeriasAfastamentosData();

        return [
            'afastamentos' => $afastamentos,
            'tiposAfastamento' => $tiposAfastamento,
            'gestaoData' => $gestaoData,
            'pendingCount' => $gestaoData['pendingCount'],
            'approvedCount' => $gestaoData['approvedCount'],
            'rejectedCount' => $gestaoData['rejectedCount'],
            'pendingLeaveRequests' => $gestaoData['pendingLeaveRequests'],
            'totalVacations' => $gestaoData['totalVacations'],
            'totalLeaves' => $gestaoData['totalLeaves']
        ];
    }

    public function listarFeriasAfastamentos()
    {
        $userId = $_SESSION['user_id'];
        $afastamentos = $this->model->listarAfastamentosPorUsuario($userId);
        $tiposAfastamento = $this->getTiposAfastamento();

        return [
            'afastamentos' => $afastamentos,
            'tiposAfastamento' => $tiposAfastamento
        ];
    }



    private function getTiposAfastamento()
    {
        return $this->model->listarTiposAfastamento();
    }

    public function getUserAfastamentos($userId) {
        return $this->model->listarPorServidor($userId);
    }

    public function getPendingLeaveRequests() {
        return $this->model->contarPorStatus('Pendente');
    }

    public function getApprovedLeaveRequests() {
        return $this->model->contarPorStatus('Aprovado');
    }

    public function getRejectedLeaveRequests() {
        return $this->model->contarPorStatus('Rejeitado');
    }

    public function getPendingLeaveRequestsDetails() {
        return $this->model->listarPendentes();
    }

    public function getTotalVacations() {
        return $this->model->contarPorTipo('Férias');
    }

    public function getTotalLeaves() {
        return $this->model->contarPorTipo('Afastamento') + $this->model->contarPorTipo('Licença');
    }

    public function aprovarSolicitacao() {
        if (isset($_POST['solicitacao_id'])) {
            $solicitacaoId = $_POST['solicitacao_id'];
            $resultado = $this->model->atualizarStatus($solicitacaoId, 'Aprovado');
            if ($resultado) {
                $_SESSION['success_message'] = "Solicitação aprovada com sucesso.";
            } else {
                $_SESSION['error_message'] = "Erro ao aprovar a solicitação.";
            }
        }
        header('Location: /sistema_produtividade/public/gerenciar-ferias-afastamento');
        exit;
    }


    public function getGestaoFeriasAfastamentosData() {
        return [
            'pendingCount' => $this->getPendingLeaveRequests(),
            'approvedCount' => $this->getApprovedLeaveRequests(),
            'rejectedCount' => $this->getRejectedLeaveRequests(),
            'pendingLeaveRequests' => $this->getPendingLeaveRequestsDetails(),
            'totalVacations' => $this->getTotalVacations(),
            'totalLeaves' => $this->getTotalLeaves(),
            'currentLeaves' => $this->getCurrentLeaves(), // Adicionando esta linha
        ];
    }

    public function getCurrentLeaves() {
        return $this->model->listarAfastamentosAtuais();
    }

    public function rejeitarSolicitacao() {
        if (isset($_POST['solicitacao_id'])) {
            $solicitacaoId = $_POST['solicitacao_id'];
            $resultado = $this->model->atualizarStatus($solicitacaoId, 'Rejeitado');
            if ($resultado) {
                $_SESSION['success_message'] = "Solicitação rejeitada com sucesso.";
            } else {
                $_SESSION['error_message'] = "Erro ao rejeitar a solicitação.";
            }
        }
        header('Location: /sistema_produtividade/public/gerenciar-ferias-afastamento');
        exit;
    }
    public function handleFormSubmission($postData) {
        $this->authController->requireAuth();
        $userId = $_SESSION['user_id'];

        $tipoAfastamentoId = $postData['tipo_afastamento'] ?? '';
        $dataInicio = $postData['data_inicio'] ?? '';
        $dataTermino = $postData['data_termino'] ?? '';
        $comentario = $postData['comentario'] ?? '';

        if (empty($tipoAfastamentoId) || empty($dataInicio) || empty($dataTermino)) {
            return ['error' => 'Todos os campos são obrigatórios.'];
        }

        $result = $this->registrarFeriasAfastamento($userId, $tipoAfastamentoId, $dataInicio, $dataTermino, $comentario);

        if ($result) {
            return ['success' => 'Solicitação registrada com sucesso.'];
        } else {
            return ['error' => 'Erro ao registrar a solicitação.'];
        }
    }

    public function registrarFeriasAfastamento($userId, $tipoAfastamentoId, $dataInicio, $dataTermino, $comentario)
    {
        return $this->model->registrar($userId, $tipoAfastamentoId, $dataInicio, $dataTermino, $comentario);
    }
}