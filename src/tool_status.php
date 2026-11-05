<?php
declare(strict_types=1);

/*
 * ============================================================
 *  Werkzeug-Status
 * ============================================================
 *  Gegenstück zu public/assets/js/tool-status.js und zur
 *  Datenbank-Tabelle tool_statuses – bei Änderungen ALLE DREI
 *  anpassen.
 */

const TOOL_STATUS_READY       = 1;
const TOOL_STATUS_ACTIVE      = 2;
const TOOL_STATUS_MAINTENANCE = 3;
const TOOL_STATUS_SCRAPPED    = 4;

const TOOL_STATUS_LABELS = [
    TOOL_STATUS_READY       => 'Bereit',
    TOOL_STATUS_ACTIVE      => 'Aktiv',
    TOOL_STATUS_MAINTENANCE => 'Wartung',
    TOOL_STATUS_SCRAPPED    => 'Verschrottet',
];

function toolStatusLabel(int $statusId): string
{
    return TOOL_STATUS_LABELS[$statusId] ?? 'Nicht definiert!';
}

// Für Eingaben aus der API: nur bekannte Status-IDs sind erlaubt.
function requireToolStatus(mixed $value): int
{
    $statusId = requirePositiveInt($value, 'Ungültiger Status');

    if (!isset(TOOL_STATUS_LABELS[$statusId])) {
        sendError(400, 'Ungültiger Status');
    }

    return $statusId;
}
