<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Verbindungs-API
 * ============================================================
 *  GET    /api/connections.php           alle Verbindungen
 *  GET    /api/connections.php?id=1      eine Verbindung
 *  POST   /api/connections.php           anlegen   (Einrichter)
 *  PATCH  /api/connections.php?id=1      ändern    (Einrichter)
 *  DELETE /api/connections.php?id=1      löschen   (Einrichter, nur wenn kein Backenset sie nutzt)
 *
 *  Felder: name (Pflicht), lokring (optional)
 */

function connectionsFields(): array
{
    return [
        'name'    => ['name',    fn($v) => requireString($v, 'Name', 100)],
        'lokring' => ['lokring', fn($v) => optionalString($v, 'Lokring', 100)],
    ];
}

function formatconnections(array $row): array
{
    return [
        'id'      => (int)$row['id'],
        'name'    => $row['name'],
        'lokring' => $row['lokring'],
    ];
}

function handleGet(): void
{
    if (isset($_GET['id'])) {
        $id   = requirePositiveInt($_GET['id'], 'Ungültige id');
        $stmt = db()->prepare('SELECT id, name, lokring FROM connections WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: sendError(404, 'Verbindung nicht gefunden');

        sendJson(['success' => true, 'connections' => formatconnections($row)]);
    }

    $rows = db()->query('SELECT id, name, lokring FROM connections ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    sendJson(['success' => true, 'connections' => array_map('formatconnections', $rows)]);
}

function handleCreate(): void
{
    $values = validateFields(readJsonBody(), connectionsFields(), false);
    $id     = insertRow('connections', $values);

    sendJson(['success' => true, 'message' => 'Verbindung wurde erstellt', 'connections_id' => $id], 201);
}

function handleUpdate(): void
{
    $id     = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');
    $values = validateFields(readJsonBody(), connectionsFields(), true);

    requireExists('connections', $id, 'Verbindung nicht gefunden');
    updateRow('connections', $id, $values);

    sendJson(['success' => true, 'message' => 'Verbindung wurde aktualisiert']);
}

function handleDelete(): void
{
    $id = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');

    requireExists('connections', $id, 'Verbindung nicht gefunden');
    ensureNotReferenced('jaw_sets', 'connection_id', $id, 'Backenset(s)');
    deleteRow('connections', $id);

    sendJson(['success' => true, 'message' => 'Verbindung wurde gelöscht']);
}

handleRequest([
    'GET'    => ['connections.read',  'handleGet'],
    'POST'   => ['connections.write', 'handleCreate'],
    'PATCH'  => ['connections.write', 'handleUpdate'],
    'DELETE' => ['connections.write', 'handleDelete'],
]);