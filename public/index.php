<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/asset.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSH Werkzeugleitstand – Lokring</title>
    <link rel="stylesheet" type="text/css" href="<?= asset('/assets/css/styles.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset('/assets/css/page.css') ?>">
    <link rel="stylesheet" type="text/css" href="<?= asset('/assets/css/home.css') ?>">
</head>

<body class="page">

    <?php include __DIR__ . '/components/app_header.php'; ?>

    <header class="page-header">
        <div class="page-header-left">
            <div class="page-title">
                <h1>Leitstand</h1>
                <span>Werkzeuge und Standorte auf einen Blick</span>
            </div>
        </div>

        <div class="page-meta" id="last_update">Lädt…</div>
    </header>

    <main class="page-main">

        <h2 class="home-section-title">Werkzeuge gesamt</h2>

        <div class="kpi-grid">
            <div class="kpi-card kpi-total">
                <span class="kpi-label">Gesamt</span>
                <strong id="kpi_total">–</strong>
            </div>
            <div class="kpi-card kpi-ready">
                <span class="kpi-label">Bereit</span>
                <strong id="kpi_ready">–</strong>
            </div>
            <div class="kpi-card kpi-active">
                <span class="kpi-label">Aktiv</span>
                <strong id="kpi_active">–</strong>
            </div>
            <div class="kpi-card kpi-maintenance">
                <span class="kpi-label">In Wartung</span>
                <strong id="kpi_maintenance">–</strong>
            </div>
        </div>

        <h2 class="home-section-title">
            Standorte
            <span id="location_summary"></span>
        </h2>

        <div class="location-grid" id="location_grid">
            <p class="panel-message">Lädt…</p>
        </div>

    </main>

    <script src="<?= asset('/assets/js/api.js') ?>"></script>
    <script src="<?= asset('/assets/js/app-header.js') ?>"></script>
    <script src="<?= asset('/assets/js/location-status.js') ?>"></script>
    <script src="<?= asset('/assets/js/home.js') ?>"></script>
</body>
</html>
