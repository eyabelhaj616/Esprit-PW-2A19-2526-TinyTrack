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
                    MAX(m.created_at) AS last_message_at
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
                    MAX(m.created_at) AS last_message_at
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
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count,
                    SUM(CASE WHEN status <> 'open' THEN 1 ELSE 0 END) AS closed_count
                FROM conversation";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetch() ?: (object) [
            'total' => 0,
            'open_count' => 0,
            'closed_count' => 0
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

    public function firstAdmin() {
        $sql = "SELECT id, nom, prenom FROM user WHERE role = 'admin' ORDER BY id ASC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetch();
    }
}
