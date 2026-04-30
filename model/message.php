<?php

require_once __DIR__ . "/../config/database.php";

class Message {

    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function create($conversation_id, $sender_id, $sender_role, $body) {

        $sql = "INSERT INTO message (conversation_id, sender_id, sender_role, body)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$conversation_id, $sender_id, $sender_role, $body]);
    }

    public function unreadCount($conversation_id, $reader_role) {
        $sql = "SELECT COUNT(*) AS unread_count
                FROM message
                WHERE conversation_id = ?
                  AND sender_role <> ?
                  AND read_at IS NULL";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$conversation_id, $reader_role]);

        $row = $stmt->fetch();

        return $row ? (int) $row->unread_count : 0;
    }

    public function markAsRead($conversation_id, $reader_role) {
        $sql = "UPDATE message
                SET read_at = NOW()
                WHERE conversation_id = ?
                  AND sender_role <> ?
                  AND read_at IS NULL";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$conversation_id, $reader_role]);
    }

    public function setAdminAlert($id, $byRole) {
        $sql = "UPDATE message
                SET needs_admin_attention = 1,
                    admin_alert_at = NOW(),
                    admin_alert_by_role = ?
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$byRole, $id]);
    }

    public function clearAdminAlert($id) {
        $sql = "UPDATE message
                SET needs_admin_attention = 0,
                    admin_alert_at = NULL,
                    admin_alert_by_role = NULL
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$id]);
    }

    public function adminAlertCount() {
        $sql = "SELECT COUNT(*) AS alert_count
                FROM message
                WHERE needs_admin_attention = 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $row = $stmt->fetch();

        return $row ? (int) $row->alert_count : 0;
    }

    public function read($conversation_id) {

        $sql = "SELECT * FROM message
                WHERE conversation_id = ?
                ORDER BY created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$conversation_id]);

        return $stmt->fetchAll();
    }

}
