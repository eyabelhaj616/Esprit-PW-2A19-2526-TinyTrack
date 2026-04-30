<?php

require_once __DIR__ . "/MessageController.php";

$devUser = require __DIR__ . "/../config/dev_user.php";
$controller = new MessageController();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : '../view/front/communication.php';
$byRole = $devUser['role'] ?? 'parent';

if (!$id || !in_array($action, ['claim', 'clear'], true)) {
    die('Invalid input');
}

if ($action === 'claim') {
    $controller->claimForAdmin($id, $byRole);
} else {
    $controller->clearAdminAlert($id);
}

header("Location: " . $redirect);
exit;
