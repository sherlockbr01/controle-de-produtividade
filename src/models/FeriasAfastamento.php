<?php

namespace Jti30\SistemaProdutividade\Models;

use PDO;

class FeriasAfastamento {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function registrar($userId, $tipoAfastamentoId, $dataInicio, $dataTermino, $comentario) {
        $sql = "INSERT INTO afastamentos (user_id, tipo_afastamento_id, data_inicio, data_termino, comentario, status) 
                VALUES (:user_id, :tipo_afastamento_id, :data_inicio, :data_termino, :comentario, 'Pendente')";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':tipo_afastamento_id' => $tipoAfastamentoId,
            ':data_inicio' => $dataInicio,
            ':data_termino' => $dataTermino,
            ':comentario' => $comentario
        ]);
    }

    public function listarPorUsuario($userId) {
        $sql = "SELECT a.*, t.descricao as tipo_afastamento 
                FROM afastamentos a 
                JOIN tipos_afastamento t ON a.tipo_afastamento_id = t.id 
                WHERE a.user_id = :user_id 
                ORDER BY a.data_inicio DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorStatus($status) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM afastamentos WHERE status = :status");
        $stmt->execute([':status' => $status]);
        return $stmt->fetchColumn();
    }

    public function listarPendentes() {
        $stmt = $this->pdo->prepare("
        SELECT a.id, u.name as servidor_nome, t.descricao as tipo_afastamento,
               a.comentario, a.data_inicio, a.data_termino
        FROM afastamentos a 
        JOIN users u ON a.user_id = u.id 
        JOIN tipos_afastamento t ON a.tipo_afastamento_id = t.id
        WHERE a.status = 'Pendente'
        ORDER BY a.data_inicio ASC
    ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarPorTipo($tipoId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM afastamentos WHERE tipo_afastamento_id = :tipo_id");
        $stmt->execute([':tipo_id' => $tipoId]);
        return $stmt->fetchColumn();
    }

    public function atualizarStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE afastamentos SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }


    public function listarTiposAfastamento() {
        $stmt = $this->pdo->prepare("SELECT * FROM tipos_afastamento");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAfastamentosAtuais() {
        $sql = "SELECT u.name, a.data_inicio, a.data_termino, t.descricao as tipo_afastamento
        FROM afastamentos a
        JOIN users u ON a.user_id = u.id
        JOIN tipos_afastamento t ON a.tipo_afastamento_id = t.id
        WHERE a.status = 'Aprovado'
        AND CURDATE() BETWEEN a.data_inicio AND a.data_termino
        ORDER BY a.data_inicio";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retorna um array vazio se não houver resultados
        return $result ? $result : [];
    }

    public function listarAfastamentosFuturos() {
        $dataAtual = date('Y-m-d');
        $sql = "SELECT a.*, u.name, t.descricao as tipo_afastamento 
            FROM afastamentos a 
            JOIN users u ON a.user_id = u.id 
            JOIN tipos_afastamento t ON a.tipo_afastamento_id = t.id
            WHERE a.data_inicio > :data_atual
            ORDER BY a.data_inicio ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':data_atual' => $dataAtual]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAfastamentosPorUsuario($userId)
    {
        $sql = "SELECT a.*, t.descricao as tipo_afastamento 
            FROM afastamentos a 
            JOIN tipos_afastamento t ON a.tipo_afastamento_id = t.id 
            WHERE a.user_id = :user_id 
            ORDER BY a.data_inicio DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarPorServidor($userId) {
        $sql = "SELECT a.*, t.descricao as tipo_afastamento 
            FROM afastamentos a 
            JOIN tipos_afastamento t ON a.tipo_afastamento_id = t.id 
            WHERE a.user_id = :user_id 
            ORDER BY a.data_inicio DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
