<?php
declare(strict_types=1);

/*
 * ============================================================
 * API INFORMATION
 * ============================================================
 */

const API_NAME              = 'BSH Werkzeugleitstand';
const API_VERSION           = '1.0.0';
const API_AUTHOR            = 'Johannes Dietrich';
const API_LAST_MODIFIED     = '06.11.2026';
const API_DESCRIPTION       = 'REST-API zur Verwaltung von Werkzeugen, Reparaturen und Ersatzteilen.';

/*
 * ============================================================
 * API CONFIGURATION
 * ============================================================
 */

// Umgebung (development/production) und Zugangsdaten: config/config.php
const API_TIMEZONE          = 'Europe/Berlin';

/*
 * ============================================================
 *  Zentrale Datei für ALLE API-Endpunkte
 * ============================================================
 *  Jeder Endpunkt (tools.php, locations.php, ...) bindet nur
 *  diese Datei ein und bekommt damit:
 *   - Datenbank (db())
 *   - Session + Login/Rechte (auth.php)
 *   - einheitliche JSON-Antworten und Fehlerbehandlung
 *   - Validierungs-Helfer
 *   - Router mit Rechteprüfung pro HTTP-Methode
 */

header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set(API_TIMEZONE);

require_once __DIR__ . '/database.php'; // stellt db() und config() bereit
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/tool_status.php';

// ---------- Session ----------

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure'   => (bool)config('session.cookie_secure', false),
    ]);
}

// ---------- Zentrale Fehlerbehandlung ----------

set_exception_handler(function (Throwable $e) {
    // SQLSTATE 23xxx = eine Datenbankregel wurde verletzt.
    // MariaDB/MySQL meldet dafür immer 23000, die genaue Ursache
    // steht im Fehlercode des Treibers (errorInfo[1]).
    if ($e instanceof PDOException && str_starts_with((string)$e->getCode(), '23')) {
        match ((int)($e->errorInfo[1] ?? 0)) {
            1062    => sendError(409, 'Ein Eintrag mit diesen Daten existiert bereits'),
            1048    => sendError(400, 'Ein Pflichtfeld fehlt'),
            1451    => sendError(409, 'Wird noch von anderen Einträgen verwendet – bitte zuerst dort ändern'),
            1452    => sendError(400, 'Ungültige Referenz – eine der übergebenen IDs existiert nicht'),
            default => sendError(400, 'Die Daten verletzen eine Datenbankregel'),
        };
    }

    error_log((string)$e);
    sendError(500, 'Interner Serverfehler');
});

// ---------- Antworten ----------

function sendJson(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function sendError(int $statusCode, string $message): never
{
    sendJson(['success' => false, 'message' => $message], $statusCode);
}

// ---------- Eingaben ----------

function readJsonBody(): array
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        sendError(400, 'Ungültige JSON-Daten');
    }

    return $data;
}

function requirePositiveInt(mixed $value, string $errorMessage): int
{
    $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($int === false) {
        sendError(400, $errorMessage);
    }

    return $int;
}

function requireString(mixed $value, string $fieldLabel, int $maxLength): string
{
    if (!is_string($value) || trim($value) === '') {
        sendError(400, "$fieldLabel fehlt");
    }

    $value = trim($value);

    if (mb_strlen($value) > $maxLength) {
        sendError(400, "$fieldLabel ist zu lang (max. $maxLength Zeichen)");
    }

    return $value;
}

function optionalString(mixed $value, string $fieldLabel, int $maxLength): ?string
{
    if ($value === null || (is_string($value) && trim($value) === '')) {
        return null;
    }

    return requireString($value, $fieldLabel, $maxLength);
}

function optionalPositiveInt(mixed $value, string $errorMessage): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    return requirePositiveInt($value, $errorMessage);
}

function optionalNumber(mixed $value, string $fieldLabel, float $min, float $max): ?float
{
    if ($value === null || $value === '') {
        return null;
    }

    $number = filter_var($value, FILTER_VALIDATE_FLOAT);

    if ($number === false || $number < $min || $number > $max) {
        sendError(400, "$fieldLabel muss eine Zahl zwischen $min und $max sein");
    }

    return $number;
}

// ---------- Datenbank-Helfer für einfache Stammdaten-Tabellen ----------
// Tabellen- und Spaltennamen kommen immer aus dem Code, nie vom Client.

function requireExists(string $table, int $id, string $notFoundMessage): void
{
    $stmt = db()->prepare("SELECT 1 FROM $table WHERE id = ?");
    $stmt->execute([$id]);

    if (!$stmt->fetchColumn()) {
        sendError(404, $notFoundMessage);
    }
}

/*
 * Verhindert das Löschen, solange noch andere Datensätze darauf verweisen.
 * Beispiel: ensureNotReferenced('tools', 'body_id', 5, 'Werkzeug(e)')
 */
function ensureNotReferenced(string $table, string $column, int $id, string $label): void
{
    $stmt = db()->prepare("SELECT COUNT(*) FROM $table WHERE $column = ?");
    $stmt->execute([$id]);
    $count = (int)$stmt->fetchColumn();

    if ($count > 0) {
        sendError(409, "Wird noch von $count $label verwendet – bitte zuerst dort ändern");
    }
}

/*
 * Prüft die Eingaben gegen eine Feldliste und gibt [Spalte => Wert] zurück.
 *
 * $fields = [
 *     'json_feld' => ['db_spalte', fn($wert) => validierter Wert],
 * ];
 *
 * $partial = false (Anlegen):  alle Felder werden geprüft, fehlende = null
 *                              (der Validator entscheidet, ob das erlaubt ist)
 * $partial = true  (Ändern):   nur mitgeschickte Felder werden geprüft
 */
function validateFields(array $data, array $fields, bool $partial): array
{
    foreach (array_keys($data) as $key) {
        if (!isset($fields[$key])) {
            sendError(400, "Unbekanntes Feld '$key'");
        }
    }

    $values = [];

    foreach ($fields as $key => [$column, $validate]) {
        if (!array_key_exists($key, $data) && $partial) {
            continue;
        }
        $values[$column] = $validate($data[$key] ?? null);
    }

    if (!$values) {
        sendError(400, 'Keine Felder zum Ändern übergeben');
    }

    return $values;
}

function insertRow(string $table, array $values): int
{
    $columns      = implode(', ', array_keys($values));
    $placeholders = implode(', ', array_fill(0, count($values), '?'));

    $stmt = db()->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");
    $stmt->execute(array_values($values));

    return (int)db()->lastInsertId();
}

function updateRow(string $table, int $id, array $values): void
{
    $set = implode(', ', array_map(fn($column) => "$column = ?", array_keys($values)));

    $stmt = db()->prepare("UPDATE $table SET $set WHERE id = ?");
    $stmt->execute([...array_values($values), $id]);
}

function deleteRow(string $table, int $id): void
{
    $stmt = db()->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->execute([$id]);
}

// ---------- Fachliche Prüfungen ----------

/*
 * Ein Bauteil gehört zu einem Werkzeug, wenn es im Backenset
 * dieses Werkzeugs steckt.
 */
function requirePartOfTool(int $partId, int $toolId): void
{
    $stmt = db()->prepare('
        SELECT 1
        FROM parts
        JOIN tools ON tools.jaw_set_id = parts.jaw_set_id
        WHERE parts.id = ? AND tools.id = ?
    ');
    $stmt->execute([$partId, $toolId]);

    if (!$stmt->fetchColumn()) {
        sendError(400, 'Das Bauteil gehört nicht zu diesem Werkzeug');
    }
}

/*
 * Ein Werkzeug auf "Verschrottet" setzen oder von dort zurückholen darf
 * nur, wer das Recht 'tools.scrap' hat. Der Aufrufer prüft vorher, dass
 * das Werkzeug existiert.
 */
function requireScrapPermissionForChange(int $toolId, int $newStatusId): void
{
    $stmt = db()->prepare('SELECT status_id FROM tools WHERE id = ?');
    $stmt->execute([$toolId]);
    $currentStatusId = (int)$stmt->fetchColumn();

    $touchesScrapped = $currentStatusId !== $newStatusId
        && in_array(TOOL_STATUS_SCRAPPED, [$currentStatusId, $newStatusId], true);

    if ($touchesScrapped && !can('tools.scrap')) {
        sendError(403, 'Nur Einrichter dürfen Werkzeuge verschrotten oder zurückholen');
    }
}

// ---------- Router ----------

/*
 * $routes = [
 *     'GET'   => ['recht.name', 'handlerFunktion'],
 *     'POST'  => [...],
 * ];
 *
 * Prüft Methode + Login + Grundrecht, bevor der Handler läuft.
 */
function handleRequest(array $routes): void
{
    $method = $_SERVER['REQUEST_METHOD'];

    if (!isset($routes[$method])) {
        header('Allow: ' . implode(', ', array_keys($routes)));
        sendError(405, 'Methode nicht erlaubt');
    }

    [$permission, $handler] = $routes[$method];

    requirePermission($permission);

    // Session-Sperre freigeben, damit parallele Requests nicht warten.
    // $_SESSION bleibt danach weiterhin lesbar.
    session_write_close();

    $handler();
}