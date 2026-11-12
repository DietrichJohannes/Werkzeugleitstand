<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkzeugpool</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/page.css">
</head>
<body class="page">

    <header class="page-header">
        <div class="page-header-left">
            <a class="button_neutral page-back" href="/">Zurück</a>

            <div class="page-title">
                <!-- Name wird von script.js über /api/locations.php geladen -->
                <h1 id="location_title">Werkzeugpool</h1>
                <span>Werkzeug durch Klicken öffnen</span>
            </div>
        </div>

        <div class="page-meta" id="tool_count">Lädt…</div>
    </header>

    <main class="page-main">
        <div class="line-grid" id="tools_grid">
            <p class="panel-message">Lädt…</p>
        </div>
    </main>

    <?php include __DIR__ . '/../../components/tool_info_dialog.php'; ?>
    <?php include __DIR__ . '/../../components/report_dialog.php'; ?>
    <?php include __DIR__ . '/../../components/parts_dialog.php'; ?>

    <script src="/assets/js/api.js"></script>
    <script src="/assets/js/tool-status.js"></script>
    <script src="/components/report_dialog.js"></script>
    <script src="/components/parts_dialog.js"></script>
    <script src="/components/tool_info_dialog.js"></script>
    <script src="script.js"></script>
</body>
</html>