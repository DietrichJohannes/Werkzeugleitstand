<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Ausfallursachen-API (Auswahlliste für "Ausfall melden")
 * ============================================================
 *  GET    /api/failure_causes.php             alle Ursachen
 *  GET    /api/failure_causes.php?active=1    nur aktive (für Auswahllisten)
 *  POST   /api/failure_causes.php             anlegen   (Einrichter)
 *  PATCH  /api/failure_causes.php?id=1        ändern    (Einrichter)
 *  DELETE /api/failure_causes.php?id=1        löschen   (Einrichter, nur wenn kein Ausfall sie nutzt)
 *
 *  Felder: name (Pflicht), sort_order (Zahl, Standard 0),
 *          active (1 = in der Auswahl anzeigen, 0 = ausblenden)
 */

function failureCauseFields(): array
{
    return [
        'name'       => ['name',       fn($v) => requireString($v, 'Name', 100)],
        'sort_order' => ['sort_order', fn($v) => (int)(optionalNumber($v, 'Sortierung', -100000, 100000) ?? 0)],
        'active'     => ['active',     fn($v) => requireActiveFlag($v)],
    ];
}

function requireActiveFlag(mixed $value): int
{
    // fehlt beim Anlegen → aktiv
    if ($value === null) {
        return 1;
    }

    if (!in_array($value, [0, 1, '0', '1', true, false], true)) {
        sendError(400, 'Ungültiger Wert für „Aktiv“');
    }

    return (int)$value;
}

function formatFailureCause(array $row): array
{
    return [
        'id'         => (int)$row['id'],
        'name'       => $row['name'],
        'sort_order' => (int)$row['sort_order'],
        'active'     => (int)$row['active'],
    ];
}

function handleGet(): void
{
    $where = ($_GET['active'] ?? '') === '1' ? 'WHERE active = 1' : '';

    $rows = db()->query("
        SELECT id, name, sort_order, active
        FROM failure_causes
        $where
        ORDER BY sort_order, name
    ")->fetchAll(PDO::FETCH_ASSOC);

    sendJson(['success' => true, 'failure_causes' => array_map('formatFailureCause', $rows)]);
}

function handleCreate(): void
{
    $values = validateFields(readJsonBody(), failureCauseFields(), false);
    $id     = insertRow('failure_causes', $values);

    sendJson(['success' => true, 'message' => 'Ausfallursache wurde erstellt', 'failure_cause_id' => $id], 201);
}

function handleUpdate(): void
{
    $id     = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');
    $values = validateFields(readJsonBody(), failureCauseFields(), true);

    requireExists('failure_causes', $id, 'Ausfallursache nicht gefunden');
    updateRow('failure_causes', $id, $values);

    sendJson(['success' => true, 'message' => 'Ausfallursache wurde aktualisiert']);
}

function handleDelete(): void
{
    $id = requirePositiveInt($_GET['id'] ?? null, 'Ungültige oder fehlende id');

    requireExists('failure_causes', $id, 'Ausfallursache nicht gefunden');

    $stmt = db()->prepare('SELECT COUNT(*) FROM failures WHERE cause_id = ?');
    $stmt->execute([$id]);
    $count = (int)$stmt->fetchColumn();

    if ($count > 0) {
        sendError(409, "Wird von $count Ausfall/Ausfällen verwendet – bitte stattdessen auf „nicht aktiv“ setzen");
    }

    deleteRow('failure_causes', $id);

    sendJson(['success' => true, 'message' => 'Ausfallursache wurde gelöscht']);
}

handleRequest([
    'GET'    => ['failure_causes.read',  'handleGet'],
    'POST'   => ['failure_causes.write', 'handleCreate'],
    'PATCH'  => ['failure_causes.write', 'handleUpdate'],
    'DELETE' => ['failure_causes.write', 'handleDelete'],
]);
