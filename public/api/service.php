<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Übersichts-/Statistik-API (nur lesen)
 * ============================================================
 *  GET /api/service.php?service=<name>
 *
 *  Alle Antworten: { "success": true, "data": ... }
 *
 *  service                     alt   Ergebnis
 *  --------------------------  ----  -------------------------------------
 *  tool_counter                 1    Anzahl Werkzeuge gesamt/aktiv/Wartung
 *  jaw_sets                     2    alle Backensets mit Anschluss
 *  bodies                       3    alle Körper
 *  parts_counter                4    Anzahl Teile
 *  location_counter             5    Anzahl Bereiche
 *  body_counter                 6    Anzahl Körper
 *  jaw_set_counter              7    Anzahl Backensets
 *  parts                        8    alle Teile mit Backenset-Name
 *  connections                  9    alle Anschlüsse
 *  connection_counter          10    Anzahl Anschlüsse
 *  failure_cause_counter       13    Anzahl Ausfallursachen
 *  tool_counter_by_location    11    Werkzeuge + Defektquote pro Bereich
 *  api_info                    12    Informationen über die API
 */

const DASHBOARD_SERVICES = [
    'tool_counter'             => 'serviceToolCounter',
    'jaw_sets'                 => 'serviceJawSets',
    'bodies'                   => 'serviceBodies',
    'parts_counter'            => 'servicePartsCounter',
    'location_counter'         => 'serviceLocationCounter',
    'body_counter'             => 'serviceBodyCounter',
    'jaw_set_counter'          => 'serviceJawSetCounter',
    'parts'                    => 'serviceParts',
    'connections'              => 'serviceConnections',
    'connection_counter'       => 'serviceConnectionCounter',
    'failure_cause_counter'    => 'serviceFailureCauseCounter',
    'tool_counter_by_location' => 'serviceToolCounterByLocation',
    'api_info'                 => 'api_info',
];

// ---------- Helfer ----------

function countRows(string $table): int
{
    // $table kommt nur aus dem Code, nie vom Client
    return (int)db()->query("SELECT COUNT(*) FROM $table")->fetchColumn();
}

function fetchAllRows(string $sql): array
{
    return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// ---------- Services ----------

function serviceToolCounter(): array
{
    $row = db()->query('
        SELECT
            COUNT(id) AS tool_quantity,
            SUM(CASE WHEN status_id = ' . TOOL_STATUS_READY . ' THEN 1 ELSE 0 END) AS tool_ready,
            SUM(CASE WHEN status_id = ' . TOOL_STATUS_ACTIVE . ' THEN 1 ELSE 0 END) AS tool_active,
            SUM(CASE WHEN status_id = ' . TOOL_STATUS_MAINTENANCE . ' THEN 1 ELSE 0 END) AS tool_maintenance,
            SUM(CASE WHEN status_id = ' . TOOL_STATUS_SCRAPPED . ' THEN 1 ELSE 0 END) AS tool_scrapped
        FROM tools
    ')->fetch(PDO::FETCH_ASSOC);

    // SUM liefert bei leerer Tabelle NULL -> (int) macht daraus 0
    return [
        'tool_quantity'    => (int)$row['tool_quantity'],
        'tool_ready'       => (int)$row['tool_ready'],
        'tool_active'      => (int)$row['tool_active'],
        'tool_maintenance' => (int)$row['tool_maintenance'],
        'tool_scrapped'    => (int)$row['tool_scrapped']
    ];
}

function serviceJawSets(): array
{
    $rows = fetchAllRows('
        SELECT
            jaw_sets.id,
            jaw_sets.name,
            connections.name    AS connection,
            connections.lokring AS lokring
        FROM jaw_sets
        JOIN connections ON jaw_sets.connection_id = connections.id
        ORDER BY jaw_sets.name
    ');

    return array_map(fn($row) => [
        'id'         => (int)$row['id'],
        'name'       => $row['name'],
        'connection' => $row['connection'],
        'lokring'    => $row['lokring'],
    ], $rows);
}

function serviceBodies(): array
{
    $rows = fetchAllRows('SELECT id, name, bom_file_name FROM bodies ORDER BY name');

    return array_map(fn($row) => [
        'id'            => (int)$row['id'],
        'name'          => $row['name'],
        'bom_file_name' => $row['bom_file_name'],
    ], $rows);
}

function servicePartsCounter(): array
{
    return ['parts_quantity' => countRows('parts')];
}

function serviceLocationCounter(): array
{
    return ['location_quantity' => countRows('locations')];
}

function serviceBodyCounter(): array
{
    return ['body_quantity' => countRows('bodies')];
}

function serviceJawSetCounter(): array
{
    return ['jaw_set_quantity' => countRows('jaw_sets')];
}

function serviceParts(): array
{
    $rows = fetchAllRows('
        SELECT
            parts.id,
            parts.jaw_set_id,
            parts.name,
            parts.hibe_number,
            parts.manufacturer_number,
            jaw_sets.name AS jaw_set_name
        FROM parts
        JOIN jaw_sets ON parts.jaw_set_id = jaw_sets.id
        ORDER BY parts.name
    ');

    return array_map(fn($row) => [
        'id'                  => (int)$row['id'],
        'jaw_set_id'          => (int)$row['jaw_set_id'],
        'jaw_set_name'        => $row['jaw_set_name'],
        'name'                => $row['name'],
        'hibe_number'         => $row['hibe_number'],
        'manufacturer_number' => $row['manufacturer_number'],
    ], $rows);
}

function serviceConnections(): array
{
    $rows = fetchAllRows('SELECT id, name, lokring FROM connections ORDER BY name');

    return array_map(fn($row) => [
        'id'      => (int)$row['id'],
        'name'    => $row['name'],
        'lokring' => $row['lokring'],
    ], $rows);
}

function serviceFailureCauseCounter(): array
{
    return ['failure_cause_quantity' => countRows('failure_causes')];
}

function serviceConnectionCounter(): array
{
    return ['connection_quantity' => countRows('connections')];
}

function api_info(): array
{
    return [
        'api_name'              => API_NAME,
        'api_version'           => API_VERSION,
        'api_author'            => API_AUTHOR,
        'api_last_modified'     => API_LAST_MODIFIED,
        'api_description'       => API_DESCRIPTION,
        'api_environment'       => config('environment'),
        'api_debug_mode'        => isDevelopment(),
        'api_timezone'          => API_TIMEZONE
    ];
}

function serviceToolCounterByLocation(): array
{
    $rows = fetchAllRows('
        SELECT
            locations.id            AS location_id,
            locations.name          AS location,
            locations.min_stock AS min_stock,
            COUNT(tools.id)         AS tools_all,
            SUM(CASE WHEN tools.status_id = ' . TOOL_STATUS_MAINTENANCE . ' THEN 1 ELSE 0 END) AS tools_defect
        FROM locations
        LEFT JOIN tools ON tools.location_id = locations.id
        GROUP BY locations.id, locations.name, locations.min_stock
        ORDER BY locations.id
    ');

    return array_map(function ($row) {
        $toolsAll    = (int)$row['tools_all'];
        $toolsDefect = (int)$row['tools_defect'];

        return [
            'location_id'    => (int)$row['location_id'],
            'location'       => $row['location'],
            'tools_all'      => $toolsAll,
            'tools_defect'   => $toolsDefect,
            'defect_percent' => $toolsAll > 0 ? round($toolsDefect / $toolsAll * 100, 1) : 0,
            'min_stock'  => $row['min_stock'] !== null ? (float)$row['min_stock'] : null,
        ];
    }, $rows);
}

// ---------- GET ----------

function handleGet(): void
{
    $service = $_GET['service'] ?? null;

    if (!is_string($service) || $service === '') {
        sendError(400, 'Es wurde kein Service angefordert');
    }

    if (!isset(DASHBOARD_SERVICES[$service])) {
        sendError(404, "Unbekannter Service '$service'");
    }

    sendJson([
        'success' => true,
        'data'    => (DASHBOARD_SERVICES[$service])(),
    ]);
}

// ---------- Start ----------

handleRequest([
    'GET' => ['dashboard.read', 'handleGet'],
]);