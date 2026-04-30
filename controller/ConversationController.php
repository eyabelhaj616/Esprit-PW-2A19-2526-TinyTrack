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

    public function forUser($userId, $role) {
        return $this->model->readForUser($userId, $role);
    }

    public function contactsForUser($userId, $role) {
        return $this->model->contactsForUser($userId, $role);
    }

    public function openConversationForUser($userId, $role, $contactId) {
        if ($role === 'parent') {
            $parentId = (int) $userId;
            $staffId = (int) $contactId;
        } elseif ($role === 'educateur') {
            $parentId = (int) $contactId;
            $staffId = (int) $userId;
        } else {
            return null;
        }

        $conversation = $this->model->getOrCreate($parentId, $staffId);

        return $conversation->id ?? null;
    }

    public function show($id) {
        return $this->model->find($id);
    }

    public function stats() {
        return $this->model->stats();
    }

    public function archive($id) {
        return $this->model->archive($id);
    }

    public function restore($id) {
        return $this->model->restore($id);
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
        $action = isset($_GET['action']) ? trim($_GET['action']) : '';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';

        if (!$id) {
            die("Invalid input");
        }

        if ($action === 'archive' || $status === 'archived') {
            $this->model->archive($id);
        } elseif ($action === 'restore' || $status === 'active') {
            $this->model->restore($id);
        } else {
            die("Invalid input");
        }

        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : "../view/back/communication_Backend.php";
        header("Location: " . $redirect);
        exit;
    }
}
