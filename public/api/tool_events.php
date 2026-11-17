<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Werkzeugverlauf-API (nur lesen)
 * ============================================================
 *  GET    /api/tool_events.php?tool_id=5    Verlauf eines Werkzeugs (neueste zuerst)
 *
 *  Geschrieben wird der Verlauf ausschließlich vom Server
 *  (src/tool_events.php) bei Änderungen über tools.php,
 *  failures.php und repairs.php.
 *
 *  Antwort: { success, events: [ {
 *      id, tool_id, field, field_label,
 *      old_value, new_value,            Rohwerte (IDs bzw. Name)
 *      old_label, new_label,            Anzeigetexte (z. B. "Linie 2")
 *      description, created_by, created_by_name, created_at
 *  } ] }
 */

const TOOL_EVENT_FIELD_LABELS = [
    TOOL_EVENT_CREATED => 'Angelegt',
    'name'             => 'Name',
    'status_id'        => 'Status',
    'location_id'      => 'Standort',
    'body_id'          => 'Grundkörper',
    'jaw_set_id'       => 'Backenset',
];

/*
 * Namen für ID-Felder einmal laden (kleine Stammdatentabellen),
 * statt pro Feld zu joinen. Gelöschte Einträge zeigen "#<id>".
 */
function loadNameMap(string $table): array
{
    return db()->query("SELECT id, name FROM $table")->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Beim Eintrag "Angelegt" steht in new_value der Standort (siehe tools.php POST).
function eventValueLabel(string $field, ?string $value, array $maps): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    return match ($field) {
        'status_id'                 => toolStatusLabel((int)$value),
        'location_id', TOOL_EVENT_CREATED
                                    => $maps['locations'][$value] ?? "#$value",
        'body_id'                   => $maps['bodies'][$value]    ?? "#$value",
        'jaw_set_id'                => $maps['jaw_sets'][$value]  ?? "#$value",
        default                     => $value,
    };
}

function handleGet(): void
{
    $toolId = requirePositiveInt($_GET['tool_id'] ?? null, 'Ungültige oder fehlende tool_id');
    requireExists('tools', $toolId, 'Werkzeug nicht gefunden');

    $stmt = db()->prepare('
        SELECT
            tool_events.*,
            COALESCE(users.display_name, users.username) AS created_by_name
        FROM tool_events
        LEFT JOIN users ON users.id = tool_events.created_by
        WHERE tool_events.tool_id = ?
        ORDER BY tool_events.created_at DESC, tool_events.id DESC
    ');
    $stmt->execute([$toolId]);

    $maps = [
        'locations' => loadNameMap('locations'),
        'bodies'    => loadNameMap('bodies'),
        'jaw_sets'  => loadNameMap('jaw_sets'),
    ];

    $events = array_map(fn($row) => [
        'id'              => (int)$row['id'],
        'tool_id'         => (int)$row['tool_id'],
        'field'           => $row['field'],
        'field_label'     => TOOL_EVENT_FIELD_LABELS[$row['field']] ?? $row['field'],
        'old_value'       => $row['old_value'],
        'new_value'       => $row['new_value'],
        'old_label'       => eventValueLabel($row['field'], $row['old_value'], $maps),
        'new_label'       => eventValueLabel($row['field'], $row['new_value'], $maps),
        'description'     => $row['description'],
        'created_by'      => $row['created_by'] !== null ? (int)$row['created_by'] : null,
        'created_by_name' => $row['created_by_name'],
        'created_at'      => $row['created_at'],
    ], $stmt->fetchAll(PDO::FETCH_ASSOC));

    sendJson(['success' => true, 'events' => $events]);
}

handleRequest([
    'GET' => ['tools.read', 'handleGet'],
]);
