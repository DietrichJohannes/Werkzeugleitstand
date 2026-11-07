<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Reparatur-API
 * ============================================================
 *  GET    /api/repairs.php          alle Reparaturen (neueste zuerst)
 *  POST   /api/repairs.php          anlegen   (Benutzer)
 *  DELETE /api/repairs.php?id=1     löschen   (Einrichter)
 *
 *  Felder: tool_id (Pflicht),
 *          failure_id (optional, offener Ausfall dieses Werkzeugs),
 *          part_ids (optional, Liste getauschter Bauteile aus dem
 *                    Backenset des Werkzeugs),
 *          description (optional, Bemerkung)
 *  created_by setzt der Server.
 *
 *  Nach dem Anlegen wird das Werkzeug automatisch auf "Bereit"
 *  gesetzt – alles in einer Transaktion.
 */

const REPAIR_DESCRIPTION_MAX = 500;

function repairFields(): array
{
    return [
        'tool_id'     => ['tool_id',     fn($v) => requirePositiveInt($v, 'Ungültige Werkzeug-ID')],
        'failure_id'  => ['failure_id',  fn($v) => optionalPositiveInt($v, 'Ungültiger Ausfall')],
        'description' => ['description', fn($v) => optionalString($v, 'Bemerkung', REPAIR_DESCRIPTION_MAX)],
    ];
}

// ---------- GET ----------

function handleGet(): void
{
    $rows = db()->query("
        SELECT
            repairs.id,
            repairs.tool_id,
            repairs.failure_id,
            repairs.description,
            repairs.created_by,
            repairs.created_at,
            tools.name AS tool_name,
            failures.created_at AS failure_created_at,
            failure_causes.name AS cause_name,
            (
                SELECT GROUP_CONCAT(parts.name ORDER BY parts.name SEPARATOR ', ')
                FROM repair_parts
                JOIN parts ON parts.id = repair_parts.part_id
                WHERE repair_parts.repair_id = repairs.id
            ) AS part_names
        FROM repairs
        JOIN tools               ON tools.id = repairs.tool_id
        LEFT JOIN failures       ON failures.id = repairs.failure_id
        LEFT JOIN failure_causes ON failure_causes.id = failures.cause_id
        ORDER BY repairs.created_at DESC, repairs.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    sendJson([
        'success' => true,
        'repairs' => array_map(fn($row) => [
            'id'                 => (int)$row['id'],
            'tool_id'            => (int)$row['tool_id'],
            'tool_name'          => $row['tool_name'],
            'failure_id'         => $row['failure_id'] !== null ? (int)$row['failure_id'] : null,
            'failure_created_at' => $row['failure_created_at'],
            'cause_name'         => $row['cause_name'],
            'part_names'         => $row['part_names'],
            'description'        => $row['description'],
            'created_by'         => $row['created_by'] !== null ? (int)$row['created_by'] : null,
            'created_at'         => $row['created_at'],
        ], $rows),
    ]);
}

// ---------- POST ----------

function handleCreate(): void
{
    $data = readJsonBody();

    // part_ids ist eine Liste → nicht über validateFields
    $partIds = readPartIds($data['part_ids'] ?? []);
    unset($data['part_ids']);

    $values = validateFields($data, repairFields(), false);
    $toolId = $values['tool_id'];

    requireExists('tools', $toolId, 'Werkzeug nicht gefunden');
    requireScrapPermissionForChange($toolId, TOOL_STATUS_READY);
    if ($values['failure_id'] !== null) {
        requireOpenFailureOfTool($values['failure_id'], $toolId);
    }
    foreach ($partIds as $partId) {
        requirePartOfTool($partId, $toolId);
    }

    $values['created_by'] = currentUserId();

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $id = insertRow('repairs', $values);

        $stmt = $pdo->prepare('INSERT INTO repair_parts (repair_id, part_id) VALUES (?, ?)');
        foreach ($partIds as $partId) {
            $stmt->execute([$id, $partId]);
        }

        updateRow('tools', $toolId, ['status_id' => TOOL_STATUS_READY]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;   // zentrale Fehlerbehandlung
    }

    sendJson([
        'success'   => true,
        'message'   => 'Reparatur wurde erstellt, Werkzeug ist wieder bereit',
        'repair_id' => $id,
    ], 201);
}

/*
 * [3, "5", 3] → [3, 5]  (doppelte entfernt)
 */
function readPartIds(mixed $value): array
{
    if (!is_array($value) || !array_is_list($value)) {
        sendError(400, 'part_ids muss eine Liste sein');
    }

    $ids = array_map(fn($v) => requirePositiveInt($v, 'Ungültiges Bauteil'), $value);

    return array_values(array_unique($ids));
}

function requireOpenFailureOfTool(int $failureId, int $toolId): void
{
    $stmt = db()->prepare('
        SELECT
            failures.tool_id,
            EXISTS (SELECT 1 FROM repairs WHERE repairs.failure_id = failures.id) AS repaired
        FROM failures
        WHERE failures.id = ?
    ');
    $stmt->execute([$failureId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        sendError(400, 'Ausfall nicht gefunden');
    }
    if ((int)$row['tool_id'] !== $toolId) {
        sendError(400, 'Der Ausfall gehört nicht zu diesem Werkzeug');
    }
    if ((int)$row['repaired'] === 1) {
        sendError(409, 'Dieser Ausfall wurde bereits repariert');
    }
}

// ---------- DELETE ----------

function handleDelete(): void
{
    $id = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');

    requireExists('repairs', $id, 'Reparatur nicht gefunden');
    deleteRow('repairs', $id);   // repair_parts werden per Fremdschlüssel mitgelöscht

    sendJson(['success' => true, 'message' => 'Reparatur wurde gelöscht']);
}

handleRequest([
    'GET'    => ['repairs.read',   'handleGet'],
    'POST'   => ['repairs.write',  'handleCreate'],
    'DELETE' => ['repairs.delete', 'handleDelete'],
]);
