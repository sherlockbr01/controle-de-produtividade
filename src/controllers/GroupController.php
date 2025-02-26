<?php

namespace Jti30\SistemaProdutividade\Controllers;

use Jti30\SistemaProdutividade\Models\Group;
use Jti30\SistemaProdutividade\Models\User;
use Exception;
use PDO;

class GroupController {
    private $db;
    private $authController;
    private $groupModel;

    public function __construct($pdo, AuthController $authController) {
        $this->db = $pdo;
        $this->authController = $authController;
        $this->groupModel = new Group($pdo);
    }

    public function createGroup() {
        $this->authController->requireDirectorAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';

            if ($this->groupModel->create($name, $description)) {
                return ['success' => 'Grupo criado com sucesso.'];
            } else {
                return ['error' => 'Erro ao criar grupo.'];
            }
        }

        return [];
    }

    public function getAllGroups() {
        $stmt = $this->db->prepare("
        SELECT g.id, g.name, g.description, COUNT(gu.user_id) as user_count
        FROM groups g
        LEFT JOIN group_users gu ON g.id = gu.group_id
        GROUP BY g.id
    ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteGroup() {
        $groupId = filter_input(INPUT_POST, 'group_id', FILTER_SANITIZE_NUMBER_INT);
        if ($groupId) {
            try {
                $this->db->beginTransaction();

                // Remover todos os usuários do grupo
                $stmt = $this->db->prepare("DELETE FROM group_users WHERE group_id = :group_id");
                $stmt->execute(['group_id' => $groupId]);

                // Excluir o grupo
                if ($this->groupModel->deleteGroupById($groupId)) {
                    $_SESSION['success_message'] = 'Grupo excluído com sucesso.';
                } else {
                    $_SESSION['error_message'] = 'Erro ao excluir o grupo.';
                }

                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                $_SESSION['error_message'] = 'Erro ao excluir o grupo.';
            }
        } else {
            $_SESSION['error_message'] = 'ID do grupo não fornecido.';
        }
        header('Location: /sistema_produtividade/public/manage-groups');
        exit;
    }

    public function showAssignUserToGroupPage() {
        $this->authController->requireDirectorAuth();

        $allGroups = $this->getAllGroups();
        $userModel = new User($this->db);
        $allUsers = $userModel->getAllUsers();

        require __DIR__ . '/../views/assign_user_group.php';
    }

    public function getGroupDetails($groupId) {
        $stmt = $this->db->prepare("SELECT * FROM groups WHERE id = :groupId");
        $stmt->execute(['groupId' => $groupId]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$group) {
            return ['error' => 'Grupo não encontrado'];
        }

        $stmtMembers = $this->db->prepare("
        SELECT u.id, u.name 
        FROM users u
        JOIN group_users gu ON u.id = gu.user_id
        WHERE gu.group_id = :groupId
    ");
        $stmtMembers->execute(['groupId' => $groupId]);
        $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

        $group['members'] = $members;

        return $group;
    }

    public function assignUserToGroup() {
        $this->authController->requireDirectorAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $groupId = $_POST['group_id'] ?? '';
            $userId = $_POST['user_id'] ?? '';

            if (empty($groupId) || empty($userId)) {
                return ['error' => 'ID do grupo ou do usuário não fornecido.'];
            }

            try {
                $this->db->beginTransaction();

                // Verificar se o usuário já está em um grupo
                $currentGroup = $this->groupModel->getUserCurrentGroup($userId);

                if ($currentGroup) {
                    // Se o usuário já está no grupo desejado, não fazemos nada
                    if ($currentGroup['id'] == $groupId) {
                        $this->db->commit();
                        return ['info' => 'Usuário já pertence a este grupo.'];
                    }

                    // Remover o usuário do grupo atual
                    if (!$this->groupModel->removeUserFromAllGroups($userId)) {
                        throw new Exception('Erro ao remover usuário do grupo atual.');
                    }
                }

                // Adicionar o usuário ao novo grupo
                if (!$this->groupModel->addUserToGroup($userId, $groupId)) {
                    throw new Exception('Erro ao adicionar usuário ao novo grupo.');
                }

                $this->db->commit();
                return ['success' => 'Usuário atribuído ao novo grupo com sucesso.'];
            } catch (Exception $e) {
                $this->db->rollBack();
                return ['error' => $e->getMessage()];
            }
        }

        return [];
    }
}