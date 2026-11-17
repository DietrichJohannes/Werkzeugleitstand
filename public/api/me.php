<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Angemeldeter Benutzer
 * ============================================================
 *  GET    /api/me.php     Name, Rolle und Rechte des Benutzers
 *
 *  Wird von der Kopfzeile jeder Seite geladen
 *  (assets/js/app-header.js). Bewusst ohne handleRequest():
 *  Jeder Angemeldete darf das abfragen, ein eigenes Recht gibt
 *  es dafür nicht. 401 = nicht angemeldet → Frontend blendet
 *  alle Elemente mit data-permission aus.
 */

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