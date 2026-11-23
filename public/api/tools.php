<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Werkzeug-API
 * ============================================================
 *  GET    /api/tools.php                  alle Werkzeuge
 *  GET    /api/tools.php?id=5             ein Werkzeug
 *  GET    /api/tools.php?location_id=2    Werkzeuge eines Standorts
 *  POST   /api/tools.php                  Werkzeug anlegen      (Einrichter)
 *  PATCH  /api/tools.php?id=5             Werkzeug ändern       (User: Status/Standort,
 *                                                                Einrichter: alles;
 *                                                                "Verschrottet" nur Einrichter)
 *  DELETE /api/tools.php?id=5             Werkzeug löschen      (Einrichter)
 */

// ---------- Felder ----------
// JSON-Feld => DB-Spalte. Dient im PATCH als Whitelist: Spaltennamen
// kommen dadurch nie vom Client.

// Felder, die jeder mit 'tools.update_state' ändern darf
const TOOL_STATE_FIELDS = [
    'status_id'   => 'status_id',
    'location_id' => 'location_id',
];

// Felder, die zusätzlich 'tools.edit_masterdata' brauchen
const TOOL_MASTERDATA_FIELDS = [
    'name'       => 'name',
    'body_id'    => 'body_id',
    'jaw_set_id' => 'jaw_set_id',
];

// ---------- Formatierung ----------

function formatTool(array $row): array
{
    return [
        'id'           => (int)$row['id'],
        'name'         => $row['name'],
        'status_id'    => (int)$row['status_id'],
        'status'       => toolStatusLabel((int)$row['status_id']),
        'location_id'  => (int)$row['location_id'],
        'location'     => $row['tool_location'],
        'body_id'      => (int)$row['body_id'],
        'body'         => $row['tool_body'],
        'jaw_set_id'   => (int)$row['jaw_set_id'],
        'jaw_set_name' => $row['tool_jaw_set_name'],
        'connection'   => $row['tool_connection'],
        'lokring'      => $row['tool_lokring'],
        'last_cleaning_at' => $row['last_cleaning_at'],
    ];
}

function fetchTools(string $where = '', array $params = []): array
{
    $stmt = db()->prepare("
        SELECT
            tools.id,
            tools.name,
            tools.status_id,
            tools.location_id,
            tools.body_id,
            tools.jaw_set_id,
            locations.name      AS tool_location,
            connections.name    AS tool_connection,
            connections.lokring AS tool_lokring,
            jaw_sets.name       AS tool_jaw_set_name,
            bodies.name         AS tool_body,
            (SELECT MAX(created_at) FROM cleanings WHERE cleanings.tool_id = tools.id) AS last_cleaning_at
        FROM tools
        JOIN locations   ON tools.location_id      = locations.id
        JOIN jaw_sets    ON tools.jaw_set_id       = jaw_sets.id
        JOIN connections ON jaw_sets.connection_id = connections.id
        JOIN bodies      ON tools.body_id          = bodies.id
        $where
        ORDER BY tools.name
    ");
    $stmt->execute($params);

    return array_map('formatTool', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

/*
 * Ein Werkzeug darf die Wartung nur verlassen, wenn sein Ausfall
 * per Reparatur abgeschlossen ist (POST /api/repairs.php).
 * Ausnahme: Verschrotten – ausgemusterte Werkzeuge repariert man nicht.
 */
function requireMaintenanceClosed(int $toolId, int $newStatusId): void
{
    if (in_array($newStatusId, [TOOL_STATUS_MAINTENANCE, TOOL_STATUS_SCRAPPED], true)) {
        return;
    }

    $stmt = db()->prepare('
        SELECT COUNT(*)
        FROM failures
        JOIN tools ON tools.id = failures.tool_id
        WHERE failures.tool_id = ?
          AND tools.status_id = ?
          AND NOT EXISTS (SELECT 1 FROM repairs WHERE repairs.failure_id = failures.id)
    ');
    $stmt->execute([$toolId, TOOL_STATUS_MAINTENANCE]);
    $openFailures = (int)$stmt->fetchColumn();

    if ($openFailures > 0) {
        sendError(409, 'Das Werkzeug hat noch einen offenen Ausfall – bitte zuerst eine Reparatur erfassen');
    }
}

function requireToolExists(int $id): void
{
    $stmt = db()->prepare('SELECT 1 FROM tools WHERE id = ?');
    $stmt->execute([$id]);

    if (!$stmt->fetchColumn()) {
        sendError(404, 'Werkzeug nicht gefunden');
    }
}

// ---------- Validierung ----------

function validateToolField(string $field, mixed $value): string|int
{
    return match ($field) {
        'name'        => requireString($value, 'Werkzeugname', 100),
        'status_id'   => requireToolStatus($value),
        'location_id' => requirePositiveInt($value, 'Ungültiger Standort'),
        'body_id'     => requirePositiveInt($value, 'Ungültiger Körper'),
        'jaw_set_id'  => requirePositiveInt($value, 'Ungültiges Backenset'),
    };
}

// ---------- GET ----------

function handleGet(): void
{
    if (isset($_GET['id'])) {
        $id = requirePositiveInt($_GET['id'], 'Ungültige id');
        $tools = fetchTools('WHERE tools.id = ?', [$id]);

        if (!$tools) {
            sendError(404, 'Werkzeug nicht gefunden');
        }

        sendJson(['success' => true, 'tool' => $tools[0]]);
    }

    if (isset($_GET['location_id'])) {
        $locationId = requirePositiveInt($_GET['location_id'], 'Ungültige location_id');
        sendJson(['success' => true, 'tools' => fetchTools('WHERE tools.location_id = ?', [$locationId])]);
    }

    sendJson(['success' => true, 'tools' => fetchTools()]);
}

// ---------- POST ----------

function handleCreate(): void
{
    $data = readJsonBody();

    $name       = validateToolField('name',        $data['name'] ?? null);
    $bodyId     = validateToolField('body_id',     $data['body_id'] ?? null);
    $jawSetId   = validateToolField('jaw_set_id',  $data['jaw_set_id'] ?? null);
    $locationId = validateToolField('location_id', $data['location_id'] ?? null);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $id = insertRow('tools', [
            'name'        => $name,
            'body_id'     => $bodyId,
            'jaw_set_id'  => $jawSetId,
            'location_id' => $locationId,
            'status_id'   => TOOL_STATUS_READY,   // neue Werkzeuge sind immer "Bereit"
        ]);
        recordToolEvent($id, TOOL_EVENT_CREATED, null, (string)$locationId, 'Werkzeug angelegt');

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;   // zentrale Fehlerbehandlung
    }

    sendJson([
        'success' => true,
        'message' => 'Werkzeug wurde erstellt',
        'tool_id' => $id,
    ], 201);
}

// ---------- PATCH ----------

/*
 * Es werden nur die Felder geändert, die im JSON mitgeschickt werden.
 * Schickt jemand Stammdaten ohne Recht darauf, gibt es 403
 * (statt sie still zu ignorieren – so merkt das Frontend den Fehler).
 */
function handleUpdate(): void
{
    $id   = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');
    $data = readJsonBody();

    $allowedFields = TOOL_STATE_FIELDS;
    if (can('tools.edit_masterdata')) {
        $allowedFields += TOOL_MASTERDATA_FIELDS;
    }

    // Unbekannte Felder oder Felder ohne Recht abfangen
    foreach (array_keys($data) as $field) {
        if (isset(TOOL_MASTERDATA_FIELDS[$field]) && !isset($allowedFields[$field])) {
            sendError(403, "Keine Berechtigung, '$field' zu ändern");
        }
        if (!isset($allowedFields[$field])) {
            sendError(400, "Unbekanntes Feld '$field'");
        }
    }

    if (!$data) {
        sendError(400, 'Keine Felder zum Ändern übergeben');
    }

    $values = [];
    foreach ($data as $field => $value) {
        // Spaltenname kommt aus der Whitelist, nie vom Client
        $values[$allowedFields[$field]] = validateToolField($field, $value);
    }

    requireToolExists($id);

    if (isset($values['status_id'])) {
        requireScrapPermissionForChange($id, $values['status_id']);
        requireMaintenanceClosed($id, $values['status_id']);
    }

    // ändert nur, was sich wirklich unterscheidet, und schreibt den Verlauf
    updateToolTracked($id, $values);

    sendJson([
        'success' => true,
        'message' => 'Werkzeug wurde aktualisiert',
        'tool'    => fetchTools('WHERE tools.id = ?', [$id])[0],
    ]);
}

// ---------- DELETE ----------

function handleDelete(): void
{
    $id = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');

    requireToolExists($id);

    // Werkzeuge mit Historie nicht löschen, sondern verschrotten
    foreach (['failures' => 'Ausfall/Ausfällen', 'repairs' => 'Reparatur(en)', 'cleanings' => 'Reinigung(en)'] as $table => $label) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM $table WHERE tool_id = ?");
        $stmt->execute([$id]);
        $count = (int)$stmt->fetchColumn();

        if ($count > 0) {
            sendError(409, "Das Werkzeug hat $count $label und kann nicht gelöscht werden – bitte stattdessen auf „Verschrottet“ setzen");
        }
    }

    // Nur Werkzeuge ohne Ausfälle/Reparaturen/Reinigungen kommen hier an
    // (z. B. versehentlich angelegte) – ihr Verlauf wird mitgelöscht.
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM tool_events WHERE tool_id = ?')->execute([$id]);
        deleteRow('tools', $id);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;   // zentrale Fehlerbehandlung
    }

    sendJson(['success' => true, 'message' => 'Werkzeug wurde gelöscht']);
}

// ---------- Start ----------
// Steht am Ende, damit die Konstanten oben schon definiert sind.

handleRequest([
    'GET'    => ['tools.read',         'handleGet'],
    'POST'   => ['tools.create',       'handleCreate'],
    'PATCH'  => ['tools.update_state', 'handleUpdate'],
    'DELETE' => ['tools.delete',       'handleDelete'],
]);
