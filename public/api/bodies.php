<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';
require_once __DIR__ . '/../../src/documents.php';

/*
 * ============================================================
 *  Grundkörper-API
 * ============================================================
 *  GET    /api/bodies.php          alle Grundkörper
 *  GET    /api/bodies.php?id=1     ein Grundkörper
 *  POST   /api/bodies.php          anlegen   (Einrichter)
 *  PATCH  /api/bodies.php?id=1     ändern    (Einrichter)
 *  DELETE /api/bodies.php?id=1     löschen   (Einrichter, nur wenn kein Werkzeug ihn nutzt)
 *
 *  Felder: name (Pflicht), bom_file_name (optional, Name einer Datei aus /api/documents.php)
 */

function optionalBomFile(mixed $value): ?string
{
    $name = optionalString($value, 'BoM-Datei', 255);

    if ($name !== null && !documentExists($name)) {
        sendError(400, 'Die BoM-Datei ist auf dem Server nicht vorhanden');
    }

    return $name;
}

function bodyFields(): array
{
    return [
        'name'          => ['name',          fn($v) => requireString($v, 'Name', 100)],
        'bom_file_name' => ['bom_file_name', fn($v) => optionalBomFile($v)],
    ];
}

function formatBody(array $row): array
{
    return [
        'id'            => (int)$row['id'],
        'name'          => $row['name'],
        'bom_file_name' => $row['bom_file_name'],
    ];
}

function handleGet(): void
{
    if (isset($_GET['id'])) {
        $id   = requirePositiveInt($_GET['id'], 'Ungültige id');
        $stmt = db()->prepare('SELECT id, name, bom_file_name FROM bodies WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: sendError(404, 'Grundkörper nicht gefunden');

        sendJson(['success' => true, 'body' => formatBody($row)]);
    }

    $rows = db()->query('SELECT id, name, bom_file_name FROM bodies ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    sendJson(['success' => true, 'bodies' => array_map('formatBody', $rows)]);
}

function handleCreate(): void
{
    $values = validateFields(readJsonBody(), bodyFields(), false);
    $id     = insertRow('bodies', $values);

    sendJson(['success' => true, 'message' => 'Grundkörper wurde erstellt', 'body_id' => $id], 201);
}

function handleUpdate(): void
{
    $id     = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');
    $values = validateFields(readJsonBody(), bodyFields(), true);

    requireExists('bodies', $id, 'Grundkörper nicht gefunden');
    updateRow('bodies', $id, $values);

    sendJson(['success' => true, 'message' => 'Grundkörper wurde aktualisiert']);
}

function handleDelete(): void
{
    $id = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');

    requireExists('bodies', $id, 'Grundkörper nicht gefunden');
    ensureNotReferenced('tools', 'body_id', $id, 'Werkzeug(en)');
    deleteRow('bodies', $id);

    sendJson(['success' => true, 'message' => 'Grundkörper wurde gelöscht']);
}

handleRequest([
    'GET'    => ['bodies.read',  'handleGet'],
    'POST'   => ['bodies.write', 'handleCreate'],
    'PATCH'  => ['bodies.write', 'handleUpdate'],
    'DELETE' => ['bodies.write', 'handleDelete'],
]);