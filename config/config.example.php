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
    ],

    'session' => [
        // auf true setzen, sobald die Seite über HTTPS läuft
        'cookie_secure' => false,
    ],
];
