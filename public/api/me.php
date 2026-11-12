<?php
// public/api/me.php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, private');

$user = currentUser();

if ($user === null) {
    sendError(401, 'Nicht angemeldet');
}

echo json_encode([
    'user' => [
        'name'       => $user['name'],
        'role'       => $user['role'],
        'role_label' => ROLE_LABELS[$user['role']] ?? $user['role'],
    ],
    'permissions' => currentPermissions(),
], JSON_UNESCAPED_UNICODE);