<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/asset.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Übersicht – Lokringwerkzeuge</title>
    <link rel="stylesheet" type="text/css" href="<?= asset('/assets/css/styles.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset('/assets/css/page.css') ?>">
</head>
<body class="page">

<?php include __DIR__ . '/../../components/app_header.php'; ?>

<header class="page-header">
    <div class="page-header-left">
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
                <span>Zeile anklicken, um ein Werkzeug zu steuern · mehrere Suchbegriffe mit Leerzeichen trennen</span>
            </div>

            <div class="panel-filters">
                <!-- Knöpfe + Anzahl je Status erzeugt script.js aus TOOL_STATUS -->
                <div class="status-filter" id="status_filter" role="group" aria-label="Nach Status filtern"></div>

                <input
                    type="search"
                    id="search"
                    class="panel-search"
                    placeholder="Name, Standort, Lokring ..."
                    aria-label="Werkzeuge durchsuchen"
                >
            </div>
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
                    </tr>
                </thead>

                <tbody id="toolTableBody">
                    <tr><td colspan="7" class="panel-empty">Lädt…</td></tr>
                </tbody>
            </table>
        </div>

    </section>
</main>

<?php include __DIR__ . '/../../components/tool_info_dialog.php'; ?>
<?php include __DIR__ . '/../../components/report_dialog.php'; ?>
<?php include __DIR__ . '/../../components/parts_dialog.php'; ?>
<script src="<?= asset('/assets/js/api.js') ?>"></script>
<script src="<?= asset('/assets/js/tool-status.js') ?>"></script>
<script src="<?= asset('/components/report_dialog.js') ?>"></script>
<script src="<?= asset('/components/parts_dialog.js') ?>"></script>
<script src="<?= asset('/components/tool_info_dialog.js') ?>"></script>
<script src="<?= asset('/pages/overview/script.js') ?>"></script>
<script src="<?= asset('/assets/js/app-header.js') ?>"></script>
</body>
</html>
