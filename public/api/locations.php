<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Standort-API
 * ============================================================
 *  GET    /api/locations.php          alle Standorte
 *  GET    /api/locations.php?id=1     ein Standort
 *  POST   /api/locations.php          anlegen   (Einrichter)
 *  PATCH  /api/locations.php?id=1     ändern    (Einrichter)
 *  DELETE /api/locations.php?id=1     löschen   (Einrichter, nur wenn keine Werkzeuge dort sind)
 *
 *  Felder: name (Pflicht),
 *          min_stock (optional, Mindestbestand: ab dieser Anzahl Werkzeuge
 *                         oder weniger gilt der Standort als kritisch)
 */

function validateMinimumStock(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100000]]);

    if ($int === false) {
        sendError(400, 'Mindestbestand muss eine ganze Zahl ab 0 sein');
    }

    return $int;
}

function locationFields(): array
{
    return [
        'name'          => ['name',          fn($v) => requireString($v, 'Standortname', 100)],
        'min_stock'     => ['min_stock',     fn($v) => validateMinimumStock($v)],
    ];
}

function formatLocation(array $row): array
{
    return [
        'id'            => (int)$row['id'],
        'name'          => $row['name'],
        'min_stock' => $row['min_stock'] !== null ? (int)$row['min_stock'] : null,
    ];
}

function handleGet(): void
{
    if (isset($_GET['id'])) {
        $id   = requirePositiveInt($_GET['id'], 'Ungültige id');
        $stmt = db()->prepare('SELECT id, name, min_stock FROM locations WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: sendError(404, 'Standort nicht gefunden');

        sendJson(['success' => true, 'location' => formatLocation($row)]);
    }

    $rows = db()->query('SELECT id, name, min_stock FROM locations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    sendJson(['success' => true, 'locations' => array_map('formatLocation', $rows)]);
}

function handleCreate(): void
{
    $values = validateFields(readJsonBody(), locationFields(), false);
    $id     = insertRow('locations', $values);

    sendJson(['success' => true, 'message' => 'Standort wurde erstellt', 'location_id' => $id], 201);
}

function handleUpdate(): void
{
    $id     = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');
    $values = validateFields(readJsonBody(), locationFields(), true);

    requireExists('locations', $id, 'Standort nicht gefunden');
    updateRow('locations', $id, $values);

    sendJson(['success' => true, 'message' => 'Standort wurde aktualisiert']);
}

function handleDelete(): void
{
    $id = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');

    requireExists('locations', $id, 'Standort nicht gefunden');
    ensureNotReferenced('tools', 'location_id', $id, 'Werkzeug(en)');
    deleteRow('locations', $id);

    sendJson(['success' => true, 'message' => 'Standort wurde gelöscht']);
}

handleRequest([
    'GET'    => ['locations.read',  'handleGet'],
    'POST'   => ['locations.write', 'handleCreate'],
    'PATCH'  => ['locations.write', 'handleUpdate'],
    'DELETE' => ['locations.write', 'handleDelete'],
]);