<?php
declare(strict_types=1);

/*
 * ============================================================
 *  Zugriff auf config/config.php
 * ============================================================
 *  Beispiel:
 *    config('db.host')                 → '192.168.2.100'
 *    config('auth.dev_fake_role')      → 'einrichter' | null
 *    config('gibt.es.nicht', 'x')      → 'x'
 */

function config(string $key, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $file = __DIR__ . '/../config/config.php';

        if (!is_file($file)) {
            error_log("Konfigurationsdatei fehlt: $file (Vorlage: config/config.example.php)");
            http_response_code(500);
            exit('Konfiguration fehlt – siehe README.');
        }

        $config = require $file;
    }

    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

function isDevelopment(): bool
{
    return config('environment') === 'development';
}
