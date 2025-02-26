<?php

namespace Jti30\SistemaProdutividade\Models;

class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
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

    public function getProfileData($userId) {
        $stmt = $this->pdo->prepare("SELECT name, email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile($userId, $name, $email, $currentPassword, $newPassword) {
        try {
            // Verifique se a senha atual está correta
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!password_verify($currentPassword, $user['password'])) {
                return false; // Senha atual incorreta
            }

            // Atualize o nome e o email
            $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $userId]);

            // Atualize a senha, se fornecida
            if (!empty($newPassword)) {
                $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$newPasswordHash, $userId]);
            }

            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }
}