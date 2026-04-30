<?php

require_once __DIR__ . "/../config/database.php";

class Conversation {

    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function readAll() {
        $sql = "SELECT
                    c.*,
                    p.nom AS parent_nom,
                    p.prenom AS parent_prenom,
                    s.nom AS staff_nom,
                    s.prenom AS staff_prenom,
                    s.role AS staff_role,
                    COUNT(m.id) AS messages_count,
                    MAX(m.created_at) AS last_message_at,
                    SUM(CASE WHEN m.needs_admin_attention = 1 THEN 1 ELSE 0 END) AS alert_messages_count
                FROM conversation c
                LEFT JOIN user p ON p.id = c.parent_id
                LEFT JOIN user s ON s.id = c.staff_id
                LEFT JOIN message m ON m.conversation_id = c.id
                GROUP BY c.id, c.parent_id, c.staff_id, c.status, c.created_at,
                         p.nom, p.prenom, s.nom, s.prenom, s.role
                ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function readForUser($userId, $role) {
        $baseSql = "SELECT
                        c.*,
                        p.nom AS parent_nom,
                        p.prenom AS parent_prenom,
                        s.nom AS staff_nom,
                        s.prenom AS staff_prenom,
                        s.role AS staff_role,
                        COUNT(m.id) AS messages_count,
                        MAX(m.created_at) AS last_message_at,
                        SUM(CASE WHEN m.needs_admin_attention = 1 THEN 1 ELSE 0 END) AS alert_messages_count
                    FROM conversation c
                    LEFT JOIN user p ON p.id = c.parent_id
                    LEFT JOIN user s ON s.id = c.staff_id
                    LEFT JOIN message m ON m.conversation_id = c.id";

        $whereSql = "";
        $params = [];

        if ($role === 'parent') {
            $whereSql = " WHERE c.parent_id = ?";
            $params[] = $userId;
        } elseif ($role === 'educateur') {
            $whereSql = " WHERE c.staff_id = ?";
            $params[] = $userId;
        }

        $sql = $baseSql . $whereSql . "
                GROUP BY c.id, c.parent_id, c.staff_id, c.status, c.created_at,
                         p.nom, p.prenom, s.nom, s.prenom, s.role
                ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function contactsForUser($userId, $role) {
        if ($role === 'parent') {
            $sql = "SELECT
                        u.id AS contact_id,
                        u.nom AS contact_nom,
                        u.prenom AS contact_prenom,
                        u.role AS contact_role,
                        GROUP_CONCAT(
                            DISTINCT CONCAT(e.prenom, ' (', g.nom, ')')
                            ORDER BY e.prenom
                            SEPARATOR ', '
                        ) AS child_group_names,
                        COUNT(DISTINCT e.id) AS children_count,
                        c.id AS conversation_id
                    FROM enfant e
                    INNER JOIN groupe g ON g.id = e.groupe_id
                    INNER JOIN user u ON u.id = g.educateur_id
                    LEFT JOIN conversation c ON c.parent_id = e.parent_id AND c.staff_id = u.id AND c.status <> 'archived'
                    WHERE e.parent_id = ?
                      AND e.statut = 'actif'
                      AND u.role = 'educateur'
                    GROUP BY u.id, u.nom, u.prenom, u.role, c.id
                    ORDER BY u.prenom ASC, u.nom ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId]);

            return $stmt->fetchAll();
        }

        if ($role === 'educateur') {
            $sql = "SELECT
                        p.id AS contact_id,
                        p.nom AS contact_nom,
                        p.prenom AS contact_prenom,
                        p.role AS contact_role,
                        GROUP_CONCAT(
                            DISTINCT e.prenom
                            ORDER BY e.prenom
                            SEPARATOR ', '
                        ) AS child_names,
                        COUNT(DISTINCT e.id) AS children_count,
                        c.id AS conversation_id
                    FROM enfant e
                    INNER JOIN groupe g ON g.id = e.groupe_id
                    INNER JOIN user p ON p.id = e.parent_id
                    LEFT JOIN conversation c ON c.parent_id = p.id AND c.staff_id = ? AND c.status <> 'archived'
                    WHERE g.educateur_id = ?
                      AND e.statut = 'actif'
                      AND p.role = 'parent'
                    GROUP BY p.id, p.nom, p.prenom, p.role, c.id
                    ORDER BY p.prenom ASC, p.nom ASC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $userId]);

            return $stmt->fetchAll();
        }

        return [];
    }

    public function findBetween($parentId, $staffId) {
        $sql = "SELECT * FROM conversation
                WHERE parent_id = ? AND staff_id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$parentId, $staffId]);

        return $stmt->fetch();
    }

    public function create($parentId, $staffId, $status = 'active') {
        $sql = "INSERT INTO conversation (parent_id, staff_id, status, created_at)
                VALUES (?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$parentId, $staffId, $status]);

        return $this->conn->lastInsertId();
    }

    public function getOrCreate($parentId, $staffId) {
        $conversation = $this->findBetween($parentId, $staffId);

        if ($conversation) {
            if (($conversation->status ?? '') === 'archived') {
                return null;
            }

            return $conversation;
        }

        $newId = $this->create($parentId, $staffId);

        return (object) [
            'id' => (int) $newId,
            'parent_id' => (int) $parentId,
            'staff_id' => (int) $staffId,
            'status' => 'active'
        ];
    }

    public function find($id) {
        $sql = "SELECT
                    c.*,
                    p.nom AS parent_nom,
                    p.prenom AS parent_prenom,
                    p.email AS parent_email,
                    s.nom AS staff_nom,
                    s.prenom AS staff_prenom,
                    s.email AS staff_email,
                    s.role AS staff_role,
                    COUNT(m.id) AS messages_count,
                    MAX(m.created_at) AS last_message_at,
                    SUM(CASE WHEN m.needs_admin_attention = 1 THEN 1 ELSE 0 END) AS alert_messages_count
                FROM conversation c
                LEFT JOIN user p ON p.id = c.parent_id
                LEFT JOIN user s ON s.id = c.staff_id
                LEFT JOIN message m ON m.conversation_id = c.id
                WHERE c.id = ?
                GROUP BY c.id, c.parent_id, c.staff_id, c.status, c.created_at,
                         p.nom, p.prenom, p.email, s.nom, s.prenom, s.email, s.role
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    public function stats() {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN status <> 'archived' THEN 1 ELSE 0 END) AS active_count,
                    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived_count
                FROM conversation";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetch() ?: (object) [
            'total' => 0,
            'active_count' => 0,
            'archived_count' => 0
        ];
    }

    public function delete($id) {
        $sql = "DELETE FROM conversation WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$id]);
    }

    public function updateStatus($id, $status) {
        $sql = "UPDATE conversation SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$status, $id]);
    }

    public function archive($id) {
        return $this->updateStatus($id, 'archived');
    }

    public function restore($id) {
        return $this->updateStatus($id, 'active');
    }

    public function firstAdmin() {
        $sql = "SELECT id, nom, prenom FROM user WHERE role = 'admin' ORDER BY id ASC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetch();
    }
}
