<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSH Werkzeugleitstand – Lokring</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/home.css">
</head>

<body class="home">

    <header class="home-header">
        <div class="home-brand">
            <strong>BSH Werkzeugleitstand</strong>
            <span>Lokring</span>
        </div>

        <div class="home-updated" id="last_update">Lädt…</div>
    </header>

    <div class="home-layout">

        <nav class="home-nav">
            <div class="home-nav-title">Menü</div>

            <a href="/pages/overview/">Übersicht<small>Werkzeuge Suchen</small></a>
            <a href="/pages/management/">Management-Ansicht<small>Kennzahlen und Auswertung</small></a>
            <a href="/pages/instruction/cleaning/">Werkzeug Wartung<small>Anleitung</small></a>
            <a href="/pages/editor/">Verwaltung<small>Stammdaten pflegen</small></a>
        </nav>

        <main class="home-main">

            <h2 class="home-section-title">Werkzeuge gesamt</h2>

            <div class="kpi-row">
                <div class="kpi kpi-total">
                    <span>Gesamt</span>
                    <strong id="kpi_total">–</strong>
                </div>
                <div class="kpi kpi-ready">
                    <span>Bereit</span>
                    <strong id="kpi_ready">–</strong>
                </div>
                <div class="kpi kpi-active">
                    <span>Aktiv</span>
                    <strong id="kpi_active">–</strong>
                </div>
                <div class="kpi kpi-maint">
                    <span>In Wartung</span>
                    <strong id="kpi_maintenance">–</strong>
                </div>
            </div>

            <h2 class="home-section-title">
                Standorte
                <span id="location_summary"></span>
            </h2>

            <div class="location-grid" id="location_grid">
                <p class="home-message">Lädt…</p>
            </div>

        </main>
    </div>

    <script src="/assets/js/api.js"></script>
    <script src="/assets/js/location-status.js"></script>
    <script src="/assets/js/home.js"></script>
</body>
</html>