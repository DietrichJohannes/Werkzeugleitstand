<?php
declare(strict_types=1);

/*
 *  /documents/             Dateiübersicht
 *  /documents/?file=<name> liefert die PDF aus (mit Rechteprüfung)
 *
 *  Die Query-Variante funktioniert ohne Rewrite-Regeln auf Apache,
 *  nginx und dem PHP-Testserver.
 */

if (isset($_GET['file'])) {
    require_once __DIR__ . '/../../src/api_bootstrap.php';
    require_once __DIR__ . '/../../src/documents.php';

    function serveDocument(): never
    {
        $name = $_GET['file'];

        if (!is_string($name) || !documentExists($name)) {
            sendError(404, 'Datei nicht gefunden');
        }

        $path = documentPath($name);

        header('Content-Type: application/pdf');
        header("Content-Disposition: inline; filename*=UTF-8''" . rawurlencode($name));
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');

        readfile($path);
        exit;
    }

    handleRequest(['GET' => ['documents.read', 'serveDocument']]);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumente</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="/documents/styles.css">
</head>
<body>
    <button class="button_neutral" onclick="location.href = '/'">Zurück</button>
    <br>
    <br>
    <h1>Dokumente</h1>
    <h3>BoM-Dateien auf dem Server</h3>

    <table class="documents-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Größe</th>
                <th>Geändert</th>
            </tr>
        </thead>
        <tbody id="documents_list">
            <tr><td colspan="3">Lädt…</td></tr>
        </tbody>
    </table>

    <script src="/assets/js/api.js"></script>
    <script src="/documents/script.js"></script>
</body>
</html>
