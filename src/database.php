<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/*
 * Liefert die (einmalig aufgebaute) Datenbankverbindung.
 * Zugangsdaten stehen in config/config.php.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            config('db.host'),
            (int)config('db.port', 3306),
            config('db.name')
        );

        $pdo = new PDO($dsn, config('db.user'), config('db.password'), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}
