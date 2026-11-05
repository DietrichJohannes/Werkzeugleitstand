<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Bauteil-API
 * ============================================================
 *  GET    /api/parts.php                 alle Bauteile
 *  GET    /api/parts.php?tool_id=5       Bauteile des Backensets eines Werkzeugs
 *  GET    /api/parts.php?jaw_set_id=2    Bauteile eines Backensets
 *  POST   /api/parts.php                 anlegen   (Einrichter)
 *  PATCH  /api/parts.php?id=3            ändern    (Einrichter)
 *  DELETE /api/parts.php?id=3            löschen   (Einrichter)
 *
 *  Felder: jaw_set_id, name, hibe_number, manufacturer_number (alle Pflicht)
 */

function partFields(): array
{
    return [
        'jaw_set_id'          => ['jaw_set_id',          fn($v) => requirePositiveInt($v, 'Ungültiges Backenset')],
        'name'                => ['name',                fn($v) => requireString($v, 'Bauteilname', 100)],
        'hibe_number'         => ['hibe_number',         fn($v) => requireString($v, 'HiBe-Nummer', 50)],
        'manufacturer_number' => ['manufacturer_number', fn($v) => requireString($v, 'Herstellernummer', 50)],
    ];
}

function fetchParts(string $join = '', string $where = '', array $params = []): array
{
    $stmt = db()->prepare("
        SELECT
            parts.id,
            parts.jaw_set_id,
            parts.name,
            parts.hibe_number,
            parts.manufacturer_number,
            jaw_sets.name AS jaw_set_name
        FROM parts
        JOIN jaw_sets ON parts.jaw_set_id = jaw_sets.id
        $join
        $where
        ORDER BY parts.name
    ");
    $stmt->execute($params);

    return array_map(fn($row) => [
        'id'                  => (int)$row['id'],
        'jaw_set_id'          => (int)$row['jaw_set_id'],
        'jaw_set_name'        => $row['jaw_set_name'],
        'name'                => $row['name'],
        'hibe_number'         => $row['hibe_number'],
        'manufacturer_number' => $row['manufacturer_number'],
    ], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// ---------- GET ----------

function handleGet(): void
{
    if (isset($_GET['tool_id'])) {
        $toolId = requirePositiveInt($_GET['tool_id'], 'Ungültige tool_id');
        requireExists('tools', $toolId, 'Werkzeug nicht gefunden');

        sendJson(['success' => true, 'parts' => fetchParts(
            'JOIN tools ON tools.jaw_set_id = parts.jaw_set_id',
            'WHERE tools.id = ?',
            [$toolId]
        )]);
    }

    if (isset($_GET['jaw_set_id'])) {
        $jawSetId = requirePositiveInt($_GET['jaw_set_id'], 'Ungültige jaw_set_id');
        requireExists('jaw_sets', $jawSetId, 'Backenset nicht gefunden');

        sendJson(['success' => true, 'parts' => fetchParts('', 'WHERE parts.jaw_set_id = ?', [$jawSetId])]);
    }

    sendJson(['success' => true, 'parts' => fetchParts()]);
}

// ---------- POST ----------

function handleCreate(): void
{
    $values = validateFields(readJsonBody(), partFields(), false);

    requireExists('jaw_sets', $values['jaw_set_id'], 'Backenset nicht gefunden');
    $id = insertRow('parts', $values);

    sendJson(['success' => true, 'message' => 'Bauteil wurde erstellt', 'part_id' => $id], 201);
}

// ---------- PATCH ----------

function handleUpdate(): void
{
    $id     = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');
    $values = validateFields(readJsonBody(), partFields(), true);

    requireExists('parts', $id, 'Bauteil nicht gefunden');
    if (isset($values['jaw_set_id'])) {
        requireExists('jaw_sets', $values['jaw_set_id'], 'Backenset nicht gefunden');
    }
    updateRow('parts', $id, $values);

    sendJson(['success' => true, 'message' => 'Bauteil wurde aktualisiert']);
}

// ---------- DELETE ----------

function handleDelete(): void
{
    $id = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');

    requireExists('parts', $id, 'Bauteil nicht gefunden');
    deleteRow('parts', $id);

    sendJson(['success' => true, 'message' => 'Bauteil wurde gelöscht']);
}

// ---------- Start ----------

handleRequest([
    'GET'    => ['parts.read',  'handleGet'],
    'POST'   => ['parts.write', 'handleCreate'],
    'PATCH'  => ['parts.write', 'handleUpdate'],
    'DELETE' => ['parts.write', 'handleDelete'],
]);