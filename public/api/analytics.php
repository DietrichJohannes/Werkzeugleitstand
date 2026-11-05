<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';

/*
 * ============================================================
 *  Auswertungs-API für die Management-Ansicht (nur lesen)
 * ============================================================
 *  GET /api/analytics.php?days=90
 *
 *  days: 30, 90 oder 365 (Standard 90). Alle Zahlen beziehen sich
 *  auf Ausfälle bzw. Reparaturen, die in diesem Zeitraum erfasst
 *  wurden – außer "open_failures" (aktueller Stand).
 *
 *  Antwort: { success, data: {
 *      period:   { days, from, bucket: "week"|"month" },
 *      summary:  { failures, repairs, open_failures, avg_downtime_hours },
 *      by_cause: [ { name, count } ],
 *      by_part:  [ { name, hibe_number, jaw_set, count } ]        (Top 10)
 *      by_tool:  [ { name, location, count } ]                    (Top 10)
 *      downtime_by_location: [ { location, avg_hours, repairs } ]
 *      timeline: [ { start, failures, repairs } ]                 (lückenlos)
 *  } }
 *
 *  Ausfallzeit = Reparatur.created_at - Ausfall.created_at
 *  (nur Reparaturen, die einem Ausfall zugeordnet sind).
 */

const ANALYTICS_PERIODS = [30, 90, 365];
const ANALYTICS_TOP     = 10;

function handleGet(): void
{
    $days = (int)($_GET['days'] ?? 90);
    if (!in_array($days, ANALYTICS_PERIODS, true)) {
        sendError(400, 'Zeitraum muss 30, 90 oder 365 Tage sein');
    }

    $from   = (new DateTimeImmutable('today'))->modify("-$days days");
    $since  = $from->format('Y-m-d H:i:s');
    $bucket = $days > 90 ? 'month' : 'week';

    sendJson([
        'success' => true,
        'data'    => [
            'period'               => ['days' => $days, 'from' => $from->format('Y-m-d'), 'bucket' => $bucket],
            'summary'              => summary($since),
            'by_cause'             => failuresByCause($since),
            'by_part'              => failuresByPart($since),
            'by_tool'              => failuresByTool($since),
            'downtime_by_location' => downtimeByLocation($since),
            'timeline'             => timeline($from, $bucket),
        ],
    ]);
}

// ---------- Abfragen ----------

function queryAll(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function queryValue(string $sql, array $params = []): mixed
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function summary(string $since): array
{
    $avgMinutes = queryValue('
        SELECT AVG(TIMESTAMPDIFF(MINUTE, failures.created_at, repairs.created_at))
        FROM repairs
        JOIN failures ON failures.id = repairs.failure_id
        WHERE repairs.created_at >= ?
    ', [$since]);

    return [
        'failures' => (int)queryValue('SELECT COUNT(*) FROM failures WHERE created_at >= ?', [$since]),
        'repairs'  => (int)queryValue('SELECT COUNT(*) FROM repairs WHERE created_at >= ?', [$since]),

        // aktueller Stand, unabhängig vom Zeitraum; verschrottete Werkzeuge zählen nicht
        'open_failures' => (int)queryValue('
            SELECT COUNT(*)
            FROM failures
            JOIN tools ON tools.id = failures.tool_id
            WHERE tools.status_id <> ?
              AND NOT EXISTS (SELECT 1 FROM repairs WHERE repairs.failure_id = failures.id)
        ', [TOOL_STATUS_SCRAPPED]),

        'avg_downtime_hours' => $avgMinutes !== null ? round((float)$avgMinutes / 60, 1) : null,
    ];
}

function failuresByCause(string $since): array
{
    $rows = queryAll('
        SELECT
            COALESCE(failure_causes.name, \'nicht kategorisiert\') AS name,
            COUNT(*) AS count
        FROM failures
        LEFT JOIN failure_causes ON failure_causes.id = failures.cause_id
        WHERE failures.created_at >= ?
        GROUP BY failure_causes.id, failure_causes.name
        ORDER BY count DESC, name
    ', [$since]);

    return array_map(fn($r) => ['name' => $r['name'], 'count' => (int)$r['count']], $rows);
}

function failuresByPart(string $since): array
{
    $rows = queryAll('
        SELECT
            parts.name,
            parts.hibe_number,
            jaw_sets.name AS jaw_set,
            COUNT(*) AS count
        FROM failures
        JOIN parts    ON parts.id = failures.part_id
        JOIN jaw_sets ON jaw_sets.id = parts.jaw_set_id
        WHERE failures.created_at >= ?
        GROUP BY parts.id, parts.name, parts.hibe_number, jaw_sets.name
        ORDER BY count DESC, parts.name
        LIMIT ' . ANALYTICS_TOP, [$since]);

    return array_map(fn($r) => [
        'name'        => $r['name'],
        'hibe_number' => $r['hibe_number'],
        'jaw_set'     => $r['jaw_set'],
        'count'       => (int)$r['count'],
    ], $rows);
}

function failuresByTool(string $since): array
{
    $rows = queryAll('
        SELECT
            tools.name,
            locations.name AS location,
            COUNT(*) AS count
        FROM failures
        JOIN tools     ON tools.id = failures.tool_id
        JOIN locations ON locations.id = tools.location_id
        WHERE failures.created_at >= ?
        GROUP BY tools.id, tools.name, locations.name
        ORDER BY count DESC, tools.name
        LIMIT ' . ANALYTICS_TOP, [$since]);

    return array_map(fn($r) => [
        'name'     => $r['name'],
        'location' => $r['location'],
        'count'    => (int)$r['count'],
    ], $rows);
}

// Standort = aktueller Standort des Werkzeugs
function downtimeByLocation(string $since): array
{
    $rows = queryAll('
        SELECT
            locations.name AS location,
            AVG(TIMESTAMPDIFF(MINUTE, failures.created_at, repairs.created_at)) AS avg_minutes,
            COUNT(*) AS repairs
        FROM repairs
        JOIN failures  ON failures.id = repairs.failure_id
        JOIN tools     ON tools.id = repairs.tool_id
        JOIN locations ON locations.id = tools.location_id
        WHERE repairs.created_at >= ?
        GROUP BY locations.id, locations.name
        ORDER BY avg_minutes DESC
    ', [$since]);

    return array_map(fn($r) => [
        'location'  => $r['location'],
        'avg_hours' => round((float)$r['avg_minutes'] / 60, 1),
        'repairs'   => (int)$r['repairs'],
    ], $rows);
}

/*
 * Ausfälle und Reparaturen je Woche (Montag) bzw. Monat.
 * Leere Wochen/Monate werden mit 0 aufgefüllt.
 */
function timeline(DateTimeImmutable $from, string $bucket): array
{
    $bucketSql = $bucket === 'month'
        ? "DATE_FORMAT(created_at, '%Y-%m-01')"
        : 'DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY)';

    $counts = [];
    foreach (['failures', 'repairs'] as $table) {
        // $table und $bucketSql kommen aus dem Code, nie vom Client
        $rows = queryAll("
            SELECT $bucketSql AS bucket_start, COUNT(*) AS count
            FROM $table
            WHERE created_at >= ?
            GROUP BY bucket_start
        ", [$from->format('Y-m-d H:i:s')]);

        foreach ($rows as $row) {
            $counts[$row['bucket_start']][$table] = (int)$row['count'];
        }
    }

    $start = $bucket === 'month'
        ? $from->modify('first day of this month')
        : $from->modify('monday this week');
    $step  = $bucket === 'month' ? '+1 month' : '+1 week';
    $today = new DateTimeImmutable('today');

    $result = [];
    for ($day = $start; $day <= $today; $day = $day->modify($step)) {
        $key = $day->format('Y-m-d');
        $result[] = [
            'start'    => $key,
            'failures' => $counts[$key]['failures'] ?? 0,
            'repairs'  => $counts[$key]['repairs'] ?? 0,
        ];
    }

    return $result;
}

handleRequest([
    'GET' => ['dashboard.read', 'handleGet'],
]);
