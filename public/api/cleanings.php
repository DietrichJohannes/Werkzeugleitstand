<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Reinigungs-API
 * ============================================================
 *  GET    /api/cleanings.php             alle Reinigungen (neueste zuerst)
 *  GET    /api/cleanings.php?tool_id=5   Reinigungen eines Werkzeugs
 *  POST   /api/cleanings.php             Reinigung quittieren (Benutzer)
 *
 *  Felder: tool_id (Pflicht)
 *  created_by setzt der Server.
 */

function fetchCleanings(string $where = '', array $params = []): array
{
    $stmt = db()->prepare("
        SELECT
            cleanings.id,
            cleanings.tool_id,
            cleanings.created_by,
            cleanings.created_at,
            tools.name AS tool_name
        FROM cleanings
        JOIN tools ON tools.id = cleanings.tool_id
        $where
        ORDER BY cleanings.created_at DESC, cleanings.id DESC
    ");
    $stmt->execute($params);

    return array_map(fn($row) => [
        'id'         => (int)$row['id'],
        'tool_id'    => (int)$row['tool_id'],
        'tool_name'  => $row['tool_name'],
        'created_by' => $row['created_by'] !== null ? (int)$row['created_by'] : null,
        'created_at' => $row['created_at'],
    ], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

function handleGet(): void
{
    if (isset($_GET['tool_id'])) {
        $toolId = requirePositiveInt($_GET['tool_id'], 'Ungültige tool_id');
        sendJson(['success' => true, 'cleanings' => fetchCleanings('WHERE cleanings.tool_id = ?', [$toolId])]);
    }

    sendJson(['success' => true, 'cleanings' => fetchCleanings()]);
}

function handleCreate(): void
{
    $values = validateFields(readJsonBody(), [
        'tool_id' => ['tool_id', fn($v) => requirePositiveInt($v, 'Bitte ein Werkzeug wählen')],
    ], false);

    requireExists('tools', $values['tool_id'], 'Werkzeug nicht gefunden');

    $values['created_by'] = currentUserId();
    $id = insertRow('cleanings', $values);

    sendJson(['success' => true, 'message' => 'Reinigung wurde quittiert', 'cleaning_id' => $id], 201);
}

handleRequest([
    'GET'  => ['cleanings.read',  'handleGet'],
    'POST' => ['cleanings.write', 'handleCreate'],
]);
