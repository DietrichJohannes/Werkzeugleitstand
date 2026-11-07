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
 *  Erwartet nach dem Login in der Session:
 *    $_SESSION['user_id'] = 42;
 *    $_SESSION['role']    = 'user';   // 'beobachter' | 'user' | 'einrichter'
 */

const ROLE_BEOBACHTER = 'beobachter';
const ROLE_USER       = 'user';
const ROLE_EINRICHTER = 'einrichter';

/*
 * Rechteverwaltung pro API
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

    // Übersicht / Statistik (nur lesen, aber nur für angemeldete Nutzer)
    'dashboard.read'        => [ROLE_BEOBACHTER, ROLE_USER, ROLE_EINRICHTER],
];

/*
 * ============================================================
 *  NUR ZUM TESTEN – solange es noch keine User-Datenbank gibt
 * ============================================================
 *  Ist in config/config.php 'auth.dev_fake_role' gesetzt, gilt
 *  JEDER Aufruf als angemeldet mit dieser Rolle.
 *  Aus Sicherheitsgründen wirkt das nur bei
 *  'environment' => 'development'.
 */
function devFakeRole(): ?string
{
    $role = config('auth.dev_fake_role');

    if ($role === null || !isDevelopment()) {
        return null;
    }

    return (string)$role;
}

function currentUser(): ?array
{
    $fakeRole = devFakeRole();
    if ($fakeRole !== null) {
        return ['id' => 0, 'role' => $fakeRole];
    }

    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        return null;
    }

    return [
        'id'   => (int)$_SESSION['user_id'],
        'role' => (string)$_SESSION['role'],
    ];
}

/*
 * ID des angemeldeten Benutzers für created_by-Spalten.
 * null, solange es keine echten Benutzer gibt (Test-Login hat id 0).
 */
function currentUserId(): ?int
{
    $id = currentUser()['id'] ?? 0;
    return $id > 0 ? $id : null;
}

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