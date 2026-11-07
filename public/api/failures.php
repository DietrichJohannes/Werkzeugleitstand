<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Ausfälle-API
 * ============================================================
 *  GET    /api/failures.php                      alle Ausfälle (neueste zuerst)
 *  GET    /api/failures.php?tool_id=5&open=1     offene Ausfälle eines Werkzeugs
 *                                                (= noch ohne Reparatur)
 *  POST   /api/failures.php                      anlegen   (Benutzer)
 *  DELETE /api/failures.php?id=1                 löschen   (Einrichter)
 *
 *  Felder: tool_id (Pflicht), cause_id (Pflicht, aktive Ursache),
 *          part_id (optional, Bauteil aus dem Backenset des Werkzeugs),
 *          description (optional, Bemerkung)
 *  created_by setzt der Server.
 *
 *  Nach dem Anlegen wird das Werkzeug automatisch auf "Wartung"
 *  gesetzt – alles in einer Transaktion.
 */

const FAILURE_DESCRIPTION_MAX = 500;

function failureFields(): array
{
    return [
        'tool_id'     => ['tool_id',     fn($v) => requirePositiveInt($v, 'Ungültige Werkzeug-ID')],
        'cause_id'    => ['cause_id',    fn($v) => requirePositiveInt($v, 'Bitte eine Ausfallursache wählen')],
        'part_id'     => ['part_id',     fn($v) => optionalPositiveInt($v, 'Ungültiges Bauteil')],
        'description' => ['description', fn($v) => optionalString($v, 'Bemerkung', FAILURE_DESCRIPTION_MAX)],
    ];
}

function formatFailure(array $row): array
{
    return [
        'id'          => (int)$row['id'],
        'tool_id'     => (int)$row['tool_id'],
        'tool_name'   => $row['tool_name'],
        'cause_id'    => $row['cause_id'] !== null ? (int)$row['cause_id'] : null,
        'cause_name'  => $row['cause_name'],
        'part_id'     => $row['part_id'] !== null ? (int)$row['part_id'] : null,
        'part_name'   => $row['part_name'],
        'description' => $row['description'],
        'created_by'  => $row['created_by'] !== null ? (int)$row['created_by'] : null,
        'created_at'  => $row['created_at'],
        'repaired'    => (bool)$row['repaired'],
    ];
}

function fetchFailures(string $where = '', array $params = []): array
{
    $stmt = db()->prepare("
        SELECT
            failures.id,
            failures.tool_id,
            failures.cause_id,
            failures.part_id,
            failures.description,
            failures.created_by,
            failures.created_at,
            tools.name          AS tool_name,
            failure_causes.name AS cause_name,
            parts.name          AS part_name,
            EXISTS (SELECT 1 FROM repairs WHERE repairs.failure_id = failures.id) AS repaired
        FROM failures
        JOIN tools               ON tools.id = failures.tool_id
        LEFT JOIN failure_causes ON failure_causes.id = failures.cause_id
        LEFT JOIN parts          ON parts.id = failures.part_id
        $where
        ORDER BY failures.created_at DESC, failures.id DESC
    ");
    $stmt->execute($params);

    return array_map('formatFailure', $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function handleGet(): void
{
    if (isset($_GET['tool_id'])) {
        $toolId = requirePositiveInt($_GET['tool_id'], 'Ungültige tool_id');
        $where  = 'WHERE failures.tool_id = ?';

        if (($_GET['open'] ?? '') === '1') {
            $where .= ' AND NOT EXISTS (SELECT 1 FROM repairs WHERE repairs.failure_id = failures.id)';
        }

        sendJson(['success' => true, 'failures' => fetchFailures($where, [$toolId])]);
    }

    sendJson(['success' => true, 'failures' => fetchFailures()]);
}

function handleCreate(): void
{
    $values = validateFields(readJsonBody(), failureFields(), false);

    requireExists('tools', $values['tool_id'], 'Werkzeug nicht gefunden');
    requireScrapPermissionForChange($values['tool_id'], TOOL_STATUS_MAINTENANCE);
    requireActiveCause($values['cause_id']);
    if ($values['part_id'] !== null) {
        requirePartOfTool($values['part_id'], $values['tool_id']);
    }

    $values['created_by'] = currentUserId();

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $id = insertRow('failures', $values);
        updateRow('tools', $values['tool_id'], ['status_id' => TOOL_STATUS_MAINTENANCE]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;   // zentrale Fehlerbehandlung
    }

    sendJson(['success' => true, 'message' => 'Ausfall wurde erstellt, Werkzeug ist in Wartung', 'failure_id' => $id], 201);
}

function requireActiveCause(int $causeId): void
{
    $stmt = db()->prepare('SELECT active FROM failure_causes WHERE id = ?');
    $stmt->execute([$causeId]);
    $active = $stmt->fetchColumn();

    if ($active === false) {
        sendError(400, 'Ausfallursache nicht gefunden');
    }
    if ((int)$active !== 1) {
        sendError(400, 'Diese Ausfallursache ist nicht mehr aktiv');
    }
}

function handleDelete(): void
{
    $id = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');

    requireExists('failures', $id, 'Ausfall nicht gefunden');
    deleteRow('failures', $id);   // verknüpfte Reparaturen bleiben (failure_id → NULL)

    sendJson(['success' => true, 'message' => 'Ausfall wurde gelöscht']);
}

handleRequest([
    'GET'    => ['failures.read',   'handleGet'],
    'POST'   => ['failures.write',  'handleCreate'],
    'DELETE' => ['failures.delete', 'handleDelete'],
]);
