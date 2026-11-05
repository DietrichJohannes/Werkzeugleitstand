<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkzeugpool</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
</head>
<body>
    <button class="button_neutral" onclick="history.back()">Zurück</button>
    <br>
    <br>
    <br>
    <br>

    <!-- Name wird von script.js über /api/locations.php geladen -->
    <h1 id="location_title">Werkzeugpool</h1>
    <h3>Werkzeug durch klicken öffnen</h3>
    <br>
    <br>

    <div class="line-grid" id="tools_grid">
        <p>Lädt…</p>
    </div>

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