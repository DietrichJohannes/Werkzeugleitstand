<?php
declare(strict_types=1);

/*
 * ============================================================
 *  Rollen & Rechte – EINE Stelle für alle APIs
 * ============================================================
 *  Die Endpunkte fragen nie "welche Rolle hat der User?",
 *  sondern immer "hat er Recht X?". Dadurch kannst du später
 *  Rollen umbauen oder neue hinzufügen, ohne die Endpunkte
 *  anzufassen.
 *
 *  Wer angemeldet ist und welche Rolle er hat, ermittelt
 *  src/ad_login.php (Kerberos + AD-Gruppen) und legt es in der
 *  Session ab ('user_id', 'username', 'role', 'role_checked_at').
 *  Rollen: 'beobachter' | 'user' | 'einrichter'
 */

const ROLE_BEOBACHTER = 'beobachter';
const ROLE_USER       = 'user';
const ROLE_EINRICHTER = 'einrichter';

// Anzeigenamen der Rollen (Kopfzeile, geliefert von api/me.php)
const ROLE_LABELS = [
    ROLE_BEOBACHTER => 'Beobachter',
    ROLE_USER       => 'Benutzer',
    ROLE_EINRICHTER => 'Einrichter',
];

/*
 * Recht => Rollen, die es haben.
 * Neues Recht: hier eintragen, im Endpoint per handleRequest() bzw.
 * requirePermission() prüfen und im Frontend ggf. per
 * data-permission="..." ausblenden.
 */
const PERMISSIONS = [
    // Werkzeuge
    'tools.read'            => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'tools.update_state'    => [ROLE_USER, ROLE_EINRICHTER],   // Status + Standort
    'tools.create'          => [ROLE_EINRICHTER],
    'tools.edit_masterdata' => [ROLE_EINRICHTER],              // Name, Körper, Backenset
    'tools.delete'          => [ROLE_EINRICHTER],
    'tools.scrap'           => [ROLE_EINRICHTER],              // auf "Verschrottet" setzen und zurückholen

    // Reparaturen
    'repairs.read'          => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'repairs.write'         => [ROLE_USER, ROLE_EINRICHTER],
    'repairs.delete'        => [ROLE_EINRICHTER],

    // Ausfälle
    'failures.read'         => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'failures.write'        => [ROLE_USER, ROLE_EINRICHTER],
    'failures.delete'       => [ROLE_EINRICHTER],

    // Reinigungen
    'cleanings.read'        => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'cleanings.write'       => [ROLE_USER, ROLE_EINRICHTER],

    // Ausfallursachen (Auswahlliste)
    'failure_causes.read'   => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'failure_causes.write'  => [ROLE_EINRICHTER],

    // Bereiche / Standorte
    'locations.read'        => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'locations.write'       => [ROLE_EINRICHTER],               // Stammdaten

    // Bauteile / Material
    'parts.read'            => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'parts.write'           => [ROLE_EINRICHTER],

    // Grundkörper
    'bodies.read'           => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'bodies.write'          => [ROLE_EINRICHTER],

    // Dokumente (BoM-PDFs)
    'documents.read'        => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'documents.write'       => [ROLE_EINRICHTER],

    // Backensets
    'jaw_sets.read'        => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'jaw_sets.write'        => [ROLE_EINRICHTER],

    // Verbindungen
    'connections.read'      => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
    'connections.write'     => [ROLE_EINRICHTER],

    // Seiten und Menüpunkte (requirePagePermission() / data-permission)
    'admin.access'          => [ROLE_EINRICHTER],               // Verwaltung
    'documents.access'      => [ROLE_USER, ROLE_EINRICHTER],    // Dokumente + Anleitungen

    // Übersicht / Statistik (nur lesen, aber nur für angemeldete Nutzer)
    'dashboard.read'        => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
];

/*
 * ============================================================
 *  NUR ZUM LOKALEN TESTEN ohne Kerberos/AD
 * ============================================================
 *  Ist in config/config.php 'auth.dev_fake_role' gesetzt, gilt
 *  JEDER Aufruf als angemeldet mit dieser Rolle.
 *  Aus Sicherheitsgründen wirkt das nur bei
 *  'environment' => 'development' UND mit dem PHP-Testserver
 *  (php -S …), nie unter Apache.
 */
function devFakeRole(): ?string
{
    $role = config('auth.dev_fake_role');

    // PHP_SAPI-Prüfung: schützt auch bei versehentlich auf dem
    // Server gesetzter Test-Rolle
    if ($role === null || !isDevelopment() || PHP_SAPI !== 'cli-server') {
        return null;
    }

    return (string)$role;
}

/*
 * Angemeldeter Benutzer: ['id' => int, 'role' => string, 'name' => string] oder null.
 * Ohne Test-Rolle kommt der Benutzer aus Kerberos + AD
 * (src/ad_login.php). Pro Request nur einmal ermittelt.
 */
function currentUser(): ?array
{
    $fakeRole = devFakeRole();
    if ($fakeRole !== null) {
        return ['id' => 0, 'role' => $fakeRole, 'name' => 'Test Benutzer'];
    }

    static $resolved = false;
    static $user = null;

    if (!$resolved) {
        require_once __DIR__ . '/ad_login.php';
        $user = directoryUser();
        $resolved = true;
    }

    return $user;
}

/*
 * ID des angemeldeten Benutzers für created_by-Spalten.
 * null beim Test-Login (id 0 – dazu gibt es keinen Eintrag in `users`).
 */
function currentUserId(): ?int
{
    $id = currentUser()['id'] ?? 0;
    return $id > 0 ? $id : null;
}

// Unbekannte Rechtenamen ergeben false (kein Tippfehler-Zugriff).
function can(string $permission): bool
{
    $user = currentUser();

    if ($user === null) {
        return false;
    }

    return in_array($user['role'], PERMISSIONS[$permission] ?? [], true);
}

/*
 * Alle Rechte des aktuellen Benutzers, damit das Frontend Optionen
 * ausblenden kann. Geprüft wird trotzdem immer auf dem Server.
 */
function currentPermissions(): array
{
    return array_values(array_filter(array_keys(PERMISSIONS), 'can'));
}

/*
 * 401 = nicht angemeldet
 * 403 = angemeldet, aber keine Berechtigung
 */
function requirePermission(string $permission): void
{
    if (currentUser() === null) {
        sendError(401, 'Nicht angemeldet');
    }

    if (!can($permission)) {
        sendError(403, 'Keine Berechtigung');
    }
}