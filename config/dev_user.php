<?php

require_once __DIR__ . "/database.php";

// Temporary manual user switch for communication testing.
// Change only this id now; remove this file later when real auth is ready.
$selectedUserId = 5 ;

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT id, nom, prenom, role FROM user WHERE id = ?");
$stmt->execute([$selectedUserId]);
$user = $stmt->fetch();

if (!$user) {
    $stmt = $conn->prepare("SELECT id, nom, prenom, role FROM user ORDER BY id ASC LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch();
}

$userId = $user ? (int) $user->id : 0;
$userRole = $user->role ?? 'parent';
$displayName = trim(($user->prenom ?? '') . ' ' . ($user->nom ?? ''));

return [
    'id' => $userId,
    'name' => $displayName !== '' ? $displayName : 'User test',
    'role' => $userRole,
    'page' => $userRole === 'admin' ? 'back' : 'front',
];
