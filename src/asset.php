<?php
declare(strict_types=1);

/* =====================================================================
   Cache-Busting für JS/CSS
   =====================================================================
   Statische Dateien hängen ihre Änderungszeit als ?v=… an die URL.
   Nach jedem Upload ändert sich die URL automatisch, der Browser
   lädt die neue Datei – kein Strg+Shift+R mehr nötig.

     <?php require_once __DIR__ . '/../../../src/asset.php'; ?>
     <script src="<?= asset('/assets/js/api.js') ?>"></script>

   Pfade immer absolut ab public/ angeben.
   ===================================================================== */

/*
 * Die HTML-Seite selbst immer neu vom Server holen (mit Revalidierung),
 * sonst zeigt eine zwischengespeicherte Seite noch auf alte ?v=-URLs.
 */
if (!headers_sent()) {
    header('Cache-Control: no-cache, private');
}

function asset(string $path): string
{
    $file  = dirname(__DIR__) . '/public' . $path;
    $mtime = @filemtime($file);

    $url = $mtime === false ? $path : $path . '?v=' . $mtime;
    return htmlspecialchars($url, ENT_QUOTES);
}
