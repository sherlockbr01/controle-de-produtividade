<?php

namespace Jti30\SistemaProdutividade\Models;

use PDO;

class Group {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function create($name, $description) {
        $stmt = $this->db->prepare("INSERT INTO groups (name, description) VALUES (?, ?)");
        return $stmt->execute([$name, $description]);
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

    public function deleteGroupById($groupId) {
        $stmt = $this->db->prepare('DELETE FROM groups WHERE id = :id');
        $stmt->bindParam(':id', $groupId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getUserCountByGroup($groupId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM group_users WHERE group_id = :group_id");
        $stmt->execute(['group_id' => $groupId]);
        return $stmt->fetchColumn();
    }

    public function getUserGroups($userId) {
        $stmt = $this->db->prepare("
        SELECT g.id, g.name, g.description 
        FROM groups g
        JOIN group_users gu ON g.id = gu.group_id
        WHERE gu.user_id = :user_id
    ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGroupMembers($groupId) {
        $stmt = $this->db->prepare("
        SELECT u.id, u.name, u.email
        FROM users u
        JOIN group_users gu ON u.id = gu.user_id
        WHERE gu.group_id = :group_id
    ");
        $stmt->execute(['group_id' => $groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGroupById($groupId) {
        $stmt = $this->db->prepare("SELECT * FROM groups WHERE id = :id");
        $stmt->execute(['id' => $groupId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTotalPoints($groupId) {
        $stmt = $this->db->prepare("
        SELECT SUM(p.points) as total_points
        FROM productivity p
        JOIN group_users gu ON p.user_id = gu.user_id
        WHERE gu.group_id = :group_id
    ");
        $stmt->execute(['group_id' => $groupId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_points'] ?? 0;
    }

    public function getUserCurrentGroup($userId) {
        $stmt = $this->db->prepare("
            SELECT g.id, g.name, g.description 
            FROM groups g
            JOIN group_users gu ON g.id = gu.group_id
            WHERE gu.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUsersInGroup($groupId) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.email
            FROM users u
            JOIN group_users gu ON u.id = gu.user_id
            WHERE gu.group_id = :group_id
        ");
        $stmt->execute(['group_id' => $groupId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function removeUserFromAllGroups($userId) {
        $stmt = $this->db->prepare("DELETE FROM group_users WHERE user_id = :user_id");
        return $stmt->execute(['user_id' => $userId]);
    }


    public function addUserToGroup($userId, $groupId) {
        // Primeiro, remova o usuário de todos os grupos
        $this->removeUserFromAllGroups($userId);

        // Agora, adicione o usuário ao novo grupo
        $stmt = $this->db->prepare("INSERT INTO group_users (user_id, group_id) VALUES (:user_id, :group_id)");
        return $stmt->execute(['user_id' => $userId, 'group_id' => $groupId]);
    }


}