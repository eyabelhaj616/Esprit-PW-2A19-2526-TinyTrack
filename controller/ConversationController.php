<?php

require_once __DIR__ . "/../model/conversation.php";

class ConversationController {

    private $model;

    public function __construct() {
        $this->model = new Conversation();
    }

    public function all() {
        return $this->model->readAll();
    }

    public function show($id) {
        return $this->model->find($id);
    }

    public function stats() {
        return $this->model->stats();
    }

    public function adminSender() {
        return $this->model->firstAdmin();
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

    public function changeStatus() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';

        if (!$id || $status === '') {
            die("Invalid input");
        }

        $this->model->updateStatus($id, $status);

        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : "../view/back/communication_Backend.php";
        header("Location: " . $redirect);
        exit;
    }
}
