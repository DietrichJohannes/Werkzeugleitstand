<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Rechte-API
 * ============================================================
 *  GET    /api/permissions.php     Rechte des aktuellen Benutzers
 *
 *  Nur zum Ausblenden von Optionen im Frontend. Die Endpunkte
 *  prüfen ihre Rechte immer selbst.
 */

function handleGet(): void
{
    sendJson(['success' => true, 'permissions' => currentPermissions()]);
}

handleRequest([
    'GET' => ['dashboard.read', 'handleGet'],
]);
