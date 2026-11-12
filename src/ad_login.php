<?php
declare(strict_types=1);

/*
 * ============================================================
 *  Anmeldung über Kerberos (Apache) + Rollen aus dem AD (LDAP)
 * ============================================================
 *  1. Apache (mod_auth_gssapi) prüft das Kerberos-Ticket und setzt
 *     $_SERVER['REMOTE_USER'], z. B. "jdietrich@FIRMA.LOCAL".
 *     PHP sieht nie ein Passwort.
 *  2. Mit dem LDAP-Servicebenutzer werden Name, E-Mail und die
 *     Gruppen des Benutzers im AD nachgeschlagen (verschachtelte
 *     Gruppen werden mit aufgelöst).
 *  3. Rolle:  Gruppe Einrichter → einrichter
 *             Gruppe Benutzer   → user
 *             sonst             → beobachter (nur lesen)
 *  4. Der Benutzer wird in `users` angelegt/aktualisiert, das
 *     Ergebnis landet in der Session und wird alle
 *     'auth.role_refresh_minutes' neu aus dem AD geholt.
 *
 *  Einstellungen: config/config.php → 'auth' → 'ldap'
 *  Apache-Einrichtung: README.md → "Anmeldung (Kerberos / AD)"
 */

/*
 * Kerberos-Benutzername aus Apache, normalisiert auf den
 * sAMAccountName in Kleinbuchstaben.
 *   "jdietrich@FIRMA.LOCAL" → "jdietrich"
 *   "FIRMA\jdietrich"       → "jdietrich"
 */
function kerberosUsername(): ?string
{
    $remoteUser = $_SERVER['REMOTE_USER'] ?? $_SERVER['REDIRECT_REMOTE_USER'] ?? '';

    if (!is_string($remoteUser) || $remoteUser === '') {
        return null;
    }

    $name = $remoteUser;

    if (str_contains($name, '\\')) {
        $name = substr($name, strrpos($name, '\\') + 1);
    }

    if (str_contains($name, '@')) {
        $name = substr($name, 0, strpos($name, '@'));
    }

    $name = strtolower(trim($name));

    return $name !== '' ? $name : null;
}

/*
 * Benutzer im AD nachschlagen und Rolle bestimmen.
 * Ist das AD nicht erreichbar oder der Benutzer nicht auffindbar,
 * gibt es nur Leserechte (beobachter) – nie mehr Rechte als nötig.
 *
 * @return array{username: string, display_name: ?string, email: ?string, role: string}
 */
function lookupDirectoryUser(string $username): array
{
    $result = [
        'username'     => $username,
        'display_name' => null,
        'email'        => null,
        'role'         => ROLE_BEOBACHTER,
    ];

    if (!function_exists('ldap_connect')) {
        error_log('AD-Login: PHP-Erweiterung "ldap" fehlt (apt install php-ldap) – nur Leserechte.');
        return $result;
    }

    $ldap = ldapConnectAndBind();
    if ($ldap === null) {
        return $result;
    }

    try {
        $baseDn  = (string)config('auth.ldap.base_dn');
        $account = ldap_escape($username, '', LDAP_ESCAPE_FILTER);
        $userFilter = "(&(objectCategory=person)(objectClass=user)(sAMAccountName=$account))";

        $search = @ldap_search($ldap, $baseDn, $userFilter, ['displayName', 'mail']);
        $entries = $search ? ldap_get_entries($ldap, $search) : ['count' => 0];

        if (($entries['count'] ?? 0) < 1) {
            error_log("AD-Login: Benutzer '$username' nicht im AD gefunden – nur Leserechte.");
            return $result;
        }

        $result['display_name'] = $entries[0]['displayname'][0] ?? null;
        $result['email']        = $entries[0]['mail'][0] ?? null;

        if (isMemberOf($ldap, $baseDn, $account, (string)config('auth.ldap.group_einrichter'))) {
            $result['role'] = ROLE_EINRICHTER;
        } elseif (isMemberOf($ldap, $baseDn, $account, (string)config('auth.ldap.group_user'))) {
            $result['role'] = ROLE_USER;
        }

        return $result;
    } finally {
        ldap_unbind($ldap);
    }
}

/*
 * Verbindung zum AD mit dem Servicebenutzer.
 * null bei Fehler (wird geloggt).
 */
function ldapConnectAndBind(): ?\LDAP\Connection
{
    $uri = (string)config('auth.ldap.uri', '');

    if ($uri === '') {
        error_log('AD-Login: auth.ldap.uri ist nicht gesetzt – nur Leserechte.');
        return null;
    }

    $ldap = @ldap_connect($uri);
    if ($ldap === false) {
        error_log("AD-Login: Ungültige LDAP-Adresse '$uri'.");
        return null;
    }

    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);      // nötig für AD
    ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, (int)config('auth.ldap.timeout', 5));

    $bound = @ldap_bind(
        $ldap,
        (string)config('auth.ldap.bind_dn'),
        (string)config('auth.ldap.bind_password')
    );

    if (!$bound) {
        error_log('AD-Login: Anmeldung des Servicebenutzers fehlgeschlagen: ' . ldap_error($ldap));
        ldap_unbind($ldap);
        return null;
    }

    return $ldap;
}

/*
 * Mitglied der Gruppe – auch über verschachtelte Gruppen
 * (AD-Regel LDAP_MATCHING_RULE_IN_CHAIN).
 */
function isMemberOf(\LDAP\Connection $ldap, string $baseDn, string $escapedAccount, string $groupDn): bool
{
    if ($groupDn === '') {
        return false;
    }

    $group  = ldap_escape($groupDn, '', LDAP_ESCAPE_FILTER);
    $filter = "(&(sAMAccountName=$escapedAccount)(memberOf:1.2.840.113556.1.4.1941:=$group))";

    $search = @ldap_search($ldap, $baseDn, $filter, ['dn']);

    return $search !== false && ldap_count_entries($ldap, $search) > 0;
}

/*
 * Benutzer in `users` anlegen oder aktualisieren, liefert die ID.
 */
function saveDirectoryUser(array $user): int
{
    $stmt = db()->prepare('
        INSERT INTO users (username, display_name, email, role, last_login_at)
        VALUES (:username, :display_name, :email, :role, NOW())
        ON DUPLICATE KEY UPDATE
            id            = LAST_INSERT_ID(id),
            display_name  = VALUES(display_name),
            email         = VALUES(email),
            role          = VALUES(role),
            last_login_at = VALUES(last_login_at)
    ');
    $stmt->execute([
        'username'     => $user['username'],
        'display_name' => $user['display_name'],
        'email'        => $user['email'],
        'role'         => $user['role'],
    ]);

    return (int)db()->lastInsertId();
}

/*
 * Benutzer aus Kerberos + AD ermitteln und in der Session ablegen.
 * Nutzt die Session, solange sie zum Kerberos-Benutzer passt und
 * die Rolle nicht älter als 'auth.role_refresh_minutes' ist.
 *
 * @return array{id: int, role: string, name: string}|null  null = nicht angemeldet
 */
function directoryUser(): ?array
{
    $username = kerberosUsername();

    if ($username === null) {
        return null;    // Apache hat niemanden angemeldet
    }

    $maxAge = 60 * (int)config('auth.role_refresh_minutes', 15);
    $sessionIsCurrent =
        ($_SESSION['username'] ?? null) === $username
        && isset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['display_name'], $_SESSION['role_checked_at'])
        && time() - (int)$_SESSION['role_checked_at'] < $maxAge;

    if ($sessionIsCurrent) {
        return [
            'id'   => (int)$_SESSION['user_id'],
            'role' => (string)$_SESSION['role'],
            'name' => (string)$_SESSION['display_name'],
        ];
    }

    $user   = lookupDirectoryUser($username);
    $userId = saveDirectoryUser($user);
    $name   = $user['display_name'] ?: $username;   // ohne AD-Namen: Anmeldename

    if (session_status() === PHP_SESSION_ACTIVE) {
        // Neue Session-ID bei neuem Benutzer (Schutz vor Session-Fixation)
        if (($_SESSION['username'] ?? null) !== $username) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id']         = $userId;
        $_SESSION['username']        = $username;
        $_SESSION['role']            = $user['role'];
        $_SESSION['display_name']    = $name;
        $_SESSION['role_checked_at'] = time();
    }

    return ['id' => $userId, 'role' => $user['role'], 'name' => $name];
}
