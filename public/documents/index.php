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
            sendError(404, 'Datei nicht gefunden!');
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
    <title>Dokumente – Lokringwerkzeuge</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/page.css">
    <link rel="stylesheet" type="text/css" href="/documents/styles.css">
</head>
<body class="page">

    <header class="page-header">
        <div class="page-header-left">
            <a class="button_neutral page-back" href="/">Zurück</a>

            <div class="page-title">
                <h1>Dokumente</h1>
                <span>Stücklisten (BoM) zu den Werkzeugen</span>
            </div>
        </div>

        <div class="page-meta" id="documents_count">Lädt…</div>
    </header>

    <main class="page-main">
        <section class="page-panel">

            <div class="panel-toolbar">
                <div>
                    <h2>Verfügbare Dokumente</h2>
                    <span>PDF öffnet sich in einem neuen Tab</span>
                </div>

                <input
                    type="search"
                    id="documents_search"
                    class="panel-search"
                    placeholder="Dateiname ..."
                    aria-label="Dokumente durchsuchen"
                >
            </div>

            <div class="panel-table">
                <table class="documents-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Größe</th>
                            <th>Geändert</th>
                        </tr>
                    </thead>
                    <tbody id="documents_list">
                        <tr><td colspan="3" class="panel-empty">Lädt…</td></tr>
                    </tbody>
                </table>
            </div>

        </section>
    </main>

    <script src="/assets/js/api.js"></script>
    <script src="/documents/script.js"></script>
</body>
</html>
