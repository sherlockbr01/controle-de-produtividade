<?php

namespace Jti30\SistemaProdutividade\Models;

use PDO;

class UserPhoto {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function saveUserPhoto($userId, $fileName, $filePath) {
        error_log("Iniciando saveUserPhoto para usuário: " . $userId);

        try {
            $relativeFilePath = 'uploads/profile_photos/' . $fileName;

            $stmt = $this->pdo->prepare("INSERT INTO user_photos (user_id, file_name, file_path) 
                                 VALUES (:user_id, :file_name, :file_path) 
                                 ON DUPLICATE KEY UPDATE 
                                 file_name = VALUES(file_name), 
                                 file_path = VALUES(file_path)");

            $result = $stmt->execute([
                ':user_id' => $userId,
                ':file_name' => $fileName,
                ':file_path' => $relativeFilePath
            ]);

            error_log("Resultado da execução do statement: " . var_export($result, true));

            if (!$result) {
                error_log("Erro PDO: " . var_export($stmt->errorInfo(), true));
            }

            return $result;
        } catch (\PDOException $e) {
            error_log("Exceção PDO: " . $e->getMessage());
            return false;
        }
    }

    public function getUserPhoto($userId) {
        $stmt = $this->pdo->prepare("SELECT file_path FROM user_photos WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}