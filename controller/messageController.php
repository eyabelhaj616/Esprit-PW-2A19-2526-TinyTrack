<?php

require_once __DIR__ . "/../model/message.php";

class MessageController {

    private $model;

    public function __construct() {
        $this->model = new Message();
    }

    public function store() {

        $conversation_id = isset($_POST['conversation_id']) ? $_POST['conversation_id'] : null;
        $sender_id = isset($_POST['sender_id']) ? $_POST['sender_id'] : null;
        $sender_role = isset($_POST['sender_role']) ? $_POST['sender_role'] : null;
        $body = isset($_POST['body']) ? trim($_POST['body']) : '';
        $redirect = isset($_POST['redirect_to']) ? trim($_POST['redirect_to']) : '../view/front/communication.php';

        // validation
        if (!$conversation_id || !$sender_id || !$sender_role || $body === '') {
            die("Invalid input");
        }

        $this->model->create($conversation_id, $sender_id, $sender_role, $body);

        header("Location: " . $redirect);
        exit;
    }

    public function index($conversation_id) {
        return $this->model->read($conversation_id);
    }

    public function all() {
        return $this->model->readAll();
    }

    public function destroy() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if (!$id) {
            die("Invalid ID");
        }

        $this->model->delete($id);

        header("Location: ../view/back/communication_Backend.php");
        exit;
    }
}
