<?php

require_once __DIR__ . "/../model/conversation.php";
require_once __DIR__ . "/../model/message.php";

class MessageController {

    private $model;
    private $conversationModel;

    public function __construct() {
        $this->model = new Message();
        $this->conversationModel = new Conversation();
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

        $conversation = $this->conversationModel->find($conversation_id);
        if (!$conversation) {
            die("Conversation not found");
        }

        if (($conversation->status ?? '') === 'archived' && $sender_role !== 'admin') {
            die("This conversation is archived");
        }

        $this->model->create($conversation_id, $sender_id, $sender_role, $body);

        header("Location: " . $redirect);
        exit;
    }

    public function index($conversation_id) {
        return $this->model->read($conversation_id);
    }

    public function unreadCount($conversation_id, $reader_role) {
        return $this->model->unreadCount($conversation_id, $reader_role);
    }

    public function markAsRead($conversation_id, $reader_role) {
        return $this->model->markAsRead($conversation_id, $reader_role);
    }

    public function adminAlertCount() {
        return $this->model->adminAlertCount();
    }

    public function claimForAdmin($id, $byRole) {
        return $this->model->setAdminAlert($id, $byRole);
    }

    public function clearAdminAlert($id) {
        return $this->model->clearAdminAlert($id);
    }
}
