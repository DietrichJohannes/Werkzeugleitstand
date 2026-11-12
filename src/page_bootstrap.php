<?php
declare(strict_types=1);

/*
 * ============================================================
 *  Zentrale Datei für geschützte HTML-Seiten
 * ============================================================
 *  Gegenstück zu api_bootstrap.php, aber ohne JSON-Header und
 *  JSON-Fehlerbehandlung. Ganz oben in der Seite einbinden,
 *  BEVOR HTML ausgegeben wird:
 *
 *    <?php
 *    require_once __DIR__ . '/../../../src/page_bootstrap.php';
 *    requirePagePermission('admin.access');
 *    ?>
 *
 *  Die Seite ist damit nur noch für Berechtigte erreichbar.
 *  Die API-Endpunkte prüfen ihre Rechte trotzdem weiterhin selbst.
 */

require_once __DIR__ . '/database.php'; // stellt db() und config() bereit (AD-Login speichert in `users`)
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/session.php';

/*
 * Ohne Recht (oder ohne Anmeldung) → Weiterleitung zur Startseite.
 */
function requirePagePermission(string $permission): void
{
    if (can($permission)) {
        return;
    }

    header('Cache-Control: no-store, private');
    header('Location: /', true, 303);
    exit;
}
