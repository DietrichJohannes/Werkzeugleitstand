<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Übersicht – Lokringwerkzeuge</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/page.css">
</head>
<body class="page">

<header class="page-header">
    <div class="page-header-left">
        <a class="button_neutral page-back" href="/">Zurück</a>

        <div class="page-title">
            <h1>Übersicht</h1>
            <span>Alle Lokringwerkzeuge – suchen und steuern</span>
        </div>
    </div>

    <div class="page-meta" id="tool_count">Lädt…</div>
</header>

<main class="page-main">
    <section class="page-panel">

        <div class="panel-toolbar">
            <div>
                <h2>Werkzeuge</h2>
                <span>Mehrere Begriffe mit Leerzeichen trennen</span>
            </div>

            <input
                type="search"
                id="search"
                class="panel-search"
                placeholder="Name, Status, Standort, Lokring ..."
                aria-label="Werkzeuge durchsuchen"
            >
        </div>

        <div class="panel-table">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Standort</th>
                        <th>Verbindung</th>
                        <th>Lokring</th>
                        <th>Backenset</th>
                        <th>Grundkörper</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>

                <tbody id="toolTableBody">
                    <tr><td colspan="8" class="panel-empty">Lädt…</td></tr>
                </tbody>
            </table>
        </div>

    </section>
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