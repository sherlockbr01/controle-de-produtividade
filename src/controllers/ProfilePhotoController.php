<?php

namespace Jti30\SistemaProdutividade\Controllers;

use Jti30\SistemaProdutividade\Models\UserPhoto;
use PDO;

class ProfilePhotoController {
    private $pdo;
    private $userPhoto;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->userPhoto = new UserPhoto($pdo);
    }

    public function uploadProfilePhoto() {
        error_log("Iniciando uploadProfilePhoto");

        if (!isset($_SESSION['user_id'])) {
            error_log("Usuário não autenticado");
            return json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("Método POST recebido");
            $inputStream = file_get_contents('php://input');
            error_log("Tamanho dos dados recebidos: " . strlen($inputStream));

            if (strpos($inputStream, 'data:image') === 0) {
                error_log("Dados de imagem base64 detectados");
                $base64Image = substr($inputStream, strpos($inputStream, ',') + 1);
                $imageData = base64_decode($base64Image);
            } else {
                error_log("Formato de dados não reconhecido");
                return json_encode(['success' => false, 'message' => 'Formato de dados não reconhecido']);
            }

            if (!$imageData) {
                error_log("Falha ao decodificar os dados da imagem");
                return json_encode(['success' => false, 'message' => 'Falha ao decodificar os dados da imagem']);
            }

            $uploadDir = __DIR__ . '/../../public/uploads/profile_photos/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.jpg';
            $filePath = $uploadDir . $fileName;

            if (file_put_contents($filePath, $imageData)) {
                error_log("Arquivo salvo com sucesso: " . $filePath);
                try {
                    $result = $this->userPhoto->saveUserPhoto($_SESSION['user_id'], $fileName, $filePath);
                    error_log("Resultado do saveUserPhoto: " . var_export($result, true));

                    if ($result) {
                        error_log("Informações da foto salvas no banco de dados");
                        return json_encode([
                            'success' => true,
                            'imageUrl' => BASE_URL . '/public/uploads/profile_photos/' . $fileName
                        ]);
                    } else {
                        error_log("Erro ao salvar a foto no banco de dados");
                        return json_encode(['success' => false, 'message' => 'Erro ao salvar a foto no banco de dados']);
                    }
                } catch (\Exception $e) {
                    error_log("Exceção ao salvar no banco de dados: " . $e->getMessage());
                    return json_encode(['success' => false, 'message' => 'Erro ao salvar a foto: ' . $e->getMessage()]);
                }
            } else {
                error_log("Erro ao salvar o arquivo: " . $filePath);
                return json_encode(['success' => false, 'message' => 'Erro ao salvar o arquivo']);
            }
        } else {
            error_log("Método de requisição inválido");
            return json_encode(['success' => false, 'message' => 'Requisição inválida']);
        }
    }

    public function getUserPhoto($userId) {
        $photo = $this->userPhoto->getUserPhoto($userId);
        if ($photo && file_exists($photo['file_path'])) {
            return $photo['file_path'];
        }
        return null;
    }
}




