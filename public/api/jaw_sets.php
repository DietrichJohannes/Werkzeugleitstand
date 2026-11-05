<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Backenset-API
 * ============================================================
 *  GET    /api/jaw_sets.php          alle Backensets (mit Verbindung + Lokring)
 *  GET    /api/jaw_sets.php?id=1     ein Backenset
 *  POST   /api/jaw_sets.php          anlegen   (Einrichter)
 *  PATCH  /api/jaw_sets.php?id=1     ändern    (Einrichter)
 *  DELETE /api/jaw_sets.php?id=1     löschen   (Einrichter, nur wenn weder
 *                                               Werkzeuge noch Bauteile es nutzen)
 *
 *  Felder: name (Pflicht), connection_id (Pflicht)
 */

function jawSetFields(): array
{
    return [
        'name'          => ['name',          fn($v) => requireString($v, 'Name', 100)],
        'connection_id' => ['connection_id', fn($v) => requirePositiveInt($v, 'Ungültige Verbindung')],
    ];
}

function fetchJawSets(string $where = '', array $params = []): array
{
    $stmt = db()->prepare("
        SELECT
            jaw_sets.id,
            jaw_sets.name,
            jaw_sets.connection_id,
            connections.name    AS connection,
            connections.lokring AS lokring
        FROM jaw_sets
        JOIN connections ON jaw_sets.connection_id = connections.id
        $where
        ORDER BY jaw_sets.name
    ");
    $stmt->execute($params);

    return array_map(fn($row) => [
        'id'            => (int)$row['id'],
        'name'          => $row['name'],
        'connection_id' => (int)$row['connection_id'],
        'connection'    => $row['connection'],
        'lokring'       => $row['lokring'],
    ], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function handleGet(): void
{
    if (isset($_GET['id'])) {
        $id       = requirePositiveInt($_GET['id'], 'Ungültige id');
        $jawSets  = fetchJawSets('WHERE jaw_sets.id = ?', [$id]) ?: sendError(404, 'Backenset nicht gefunden');

        sendJson(['success' => true, 'jaw_set' => $jawSets[0]]);
    }

    sendJson(['success' => true, 'jaw_sets' => fetchJawSets()]);
}

function handleCreate(): void
{
    $values = validateFields(readJsonBody(), jawSetFields(), false);

    requireExists('connections', $values['connection_id'], 'Verbindung nicht gefunden');
    $id = insertRow('jaw_sets', $values);

    sendJson(['success' => true, 'message' => 'Backenset wurde erstellt', 'jaw_set_id' => $id], 201);
}

function handleUpdate(): void
{
    $id     = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');
    $values = validateFields(readJsonBody(), jawSetFields(), true);

    requireExists('jaw_sets', $id, 'Backenset nicht gefunden');
    if (isset($values['connection_id'])) {
        requireExists('connections', $values['connection_id'], 'Verbindung nicht gefunden');
    }
    updateRow('jaw_sets', $id, $values);

    sendJson(['success' => true, 'message' => 'Backenset wurde aktualisiert']);
}

function handleDelete(): void
{
    $id = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');

    requireExists('jaw_sets', $id, 'Backenset nicht gefunden');
    ensureNotReferenced('tools', 'jaw_set_id', $id, 'Werkzeug(en)');
    ensureNotReferenced('parts', 'jaw_set_id', $id, 'Bauteil(en)');
    deleteRow('jaw_sets', $id);

    sendJson(['success' => true, 'message' => 'Backenset wurde gelöscht']);
}

handleRequest([
    'GET'    => ['jaw_sets.read',  'handleGet'],
    'POST'   => ['jaw_sets.write', 'handleCreate'],
    'PATCH'  => ['jaw_sets.write', 'handleUpdate'],
    'DELETE' => ['jaw_sets.write', 'handleDelete'],
]);