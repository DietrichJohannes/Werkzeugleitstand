<?php
/*
 * ============================================================
 *  Konfigurationsvorlage
 * ============================================================
 *  Kopieren nach config/config.php und Werte anpassen:
 *      cp config/config.example.php config/config.php
 *
 *  config/config.php enthält Passwörter und ist NICHT im Git.
 */

return [
    // 'development' oder 'production'
    'environment' => 'production',

    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'lokring',
        'user'     => 'webapp',
        'password' => '',
    ],

    'auth' => [
        // NUR ZUM TESTEN, solange es noch keinen Login gibt:
        // Ist hier eine Rolle eingetragen ('beobachter', 'user', 'einrichter'),
        // gilt JEDER Aufruf als angemeldet mit dieser Rolle.
        // !!! Im Produktivbetrieb auf null setzen !!!
        'dev_fake_role' => null,

        // Rolle wird nach so vielen Minuten neu aus dem AD gelesen
        // (z. B. nachdem jemand aus einer Gruppe entfernt wurde).
        'role_refresh_minutes' => 15,

        // Anmeldung selbst macht Apache per Kerberos (mod_auth_gssapi).
        // Hier nur der Zugriff aufs AD, um Name, E-Mail und Gruppen zu lesen.
        // Benötigt die PHP-Erweiterung ldap (apt install php-ldap).
        'ldap' => [
            // ldaps:// bevorzugen; mehrere DCs mit Leerzeichen trennen
            'uri'           => 'ldaps://dc01.firma.local ldaps://dc02.firma.local',
            'base_dn'       => 'DC=firma,DC=local',

            // Servicebenutzer mit Leserechten im AD (kein Admin nötig)
            'bind_dn'       => 'CN=svc-werkzeugleitstand,OU=Service,DC=firma,DC=local',
            'bind_password' => '',

            // Gruppen (vollständiger DN). Wer in keiner ist → beobachter (nur lesen).
            // Einrichter hat Vorrang, falls jemand in beiden ist.
            'group_einrichter' => 'CN=GG-Werkzeugleitstand-Einrichter,OU=Gruppen,DC=firma,DC=local',
            'group_user'       => 'CN=GG-Werkzeugleitstand-Benutzer,OU=Gruppen,DC=firma,DC=local',

            'timeout' => 5,   // Sekunden
        ],
    ],

    'session' => [
        // auf true setzen, sobald die Seite über HTTPS läuft
        'cookie_secure' => false,
    ],
];
