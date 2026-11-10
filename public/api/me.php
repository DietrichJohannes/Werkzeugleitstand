<?php
// public/api/me.php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, private');

if (currentUser() === null) {
    sendError(401, 'Nicht angemeldet');
}

echo json_encode(['permissions' => currentPermissions()]);