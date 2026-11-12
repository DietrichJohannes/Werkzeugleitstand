<?php
declare(strict_types=1);

/*
 * ============================================================
 *  Session – gemeinsam für API und HTML-Seiten
 * ============================================================
 *  Gleiche Cookie-Einstellungen überall, damit API und Seiten
 *  dieselbe Anmeldung sehen. Benötigt config() (config.php).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'cookie_secure'   => (bool)config('session.cookie_secure', false),
    ]);
}
