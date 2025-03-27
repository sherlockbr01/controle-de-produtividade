<?php

namespace Jti30\SistemaProdutividade\Models;

use PDO; // Adicione esta linha no topo do arquivo


class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($name, $email, $password, $userType) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, user_type) VALUES (:name, :email, :password, :user_type)");
        return $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'user_type' => $userType
        ]);
    }


    public function getAllUsers() {
        $stmt = $this->db->prepare("SELECT id, name FROM users");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTopServers($limit = 500) {
        $query = "SELECT u.id, u.name, 
              SUM(p.points) as total_points, 
              COUNT(p.id) as completed_processes,
              AVG(p.efficiency) as avg_efficiency
              FROM users u
              LEFT JOIN productivity p ON u.id = p.user_id
              GROUP BY u.id
              ORDER BY total_points DESC
              LIMIT :limit";

        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProfileData($userId) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function updateProfile($userId, $data) {
        $allowedFields = [
            'name', 'function', 'birth_date', 'city', 'state', 'country',
            'bio', 'phone', 'education', 'skills',
            'interests', 'social_media_links', 'preferred_language'
        ];

        $setFields = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $setFields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($setFields)) {
            return false; // No fields to update
        }

        $setClause = implode(', ', $setFields);
        $params['user_id'] = $userId;

        $stmt = $this->db->prepare("UPDATE users SET $setClause WHERE id = :user_id");
        return $stmt->execute($params);
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        // Verifique se a senha atual está correta
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!password_verify($currentPassword, $user['password'])) {
            return false; // Senha atual incorreta
        }

        // Atualize a senha
        $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$newPasswordHash, $userId]);
    }

    public function saveResetCode($userId, $resetCode, $expiryTime) {
        $sql = "UPDATE users SET reset_code = ?, reset_code_expiry = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$resetCode, $expiryTime, $userId]);
    }

    public function findByResetCode($resetCode) {
        $sql = "SELECT * FROM users WHERE reset_code = :reset_code AND reset_expiry > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':reset_code' => $resetCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isResetCodeValid($userId, $resetCode) {
        $sql = "SELECT COUNT(*) FROM users WHERE id = :id AND reset_code = :reset_code AND reset_expiry > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId, ':reset_code' => $resetCode]);
        return $stmt->fetchColumn() > 0;
    }

    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = :password, reset_code = NULL, reset_expiry = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':password' => $hashedPassword,
            ':id' => $userId
        ]);
    }

    public function clearResetCode($userId) {
        $sql = "UPDATE users SET reset_code = NULL, reset_expiry = NULL WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $userId]);
    }

    public function resetPassword($resetCode, $newPassword) {
        // Verificar se o código de reset é válido e não expirou
        $stmt = $this->db->prepare("SELECT id FROM users WHERE reset_code = ? AND reset_code_expiry > NOW()");
        $stmt->execute([$resetCode]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Atualizar a senha do usuário e limpar o código de reset
            $updateStmt = $this->db->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_code_expiry = NULL WHERE id = ?");
            $success = $updateStmt->execute([$hashedPassword, $user['id']]);

            return $success;
        }

        return false;
    }

}

