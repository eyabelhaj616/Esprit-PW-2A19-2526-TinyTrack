<?php

require_once __DIR__ . "/../config/Database.php";

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

    public function read($conversation_id) {

        $sql = "SELECT * FROM message
                WHERE conversation_id = ?
                ORDER BY created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$conversation_id]);

        return $stmt->fetchAll();
    }

    public function readAll() {
        $sql = "SELECT * FROM message
                ORDER BY created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function delete($id) {

        $sql = "DELETE FROM message WHERE id = ?";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$id]);
    }
}