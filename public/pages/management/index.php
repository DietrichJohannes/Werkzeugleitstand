<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Management-Ansicht – Lokringwerkzeuge</title>

    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/charts.css">
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>

<body class="mgmt">

    <header class="mgmt-header">
        <div class="mgmt-header-left">
            <a class="button_neutral mgmt-back" href="/">← Zurück</a>

            <div class="mgmt-title">
                <h1>Management-Ansicht</h1>
                <span>Lokringwerkzeuge – aktueller Status</span>
            </div>
        </div>

        <div class="mgmt-updated" id="last_update">Lädt…</div>
    </header>

    <main class="mgmt-main">

        <!-- Fehlerhinweis, falls die API nicht erreichbar ist -->
        <p class="mgmt-error hidden" id="error_box"></p>

        <!-- Kennzahlen -->
        <section class="kpi-grid">

            <div class="kpi-card">
                <span class="kpi-label">Werkzeuge gesamt</span>
                <strong id="tools_all">–</strong>
            </div>

            <div class="kpi-card">
                <span class="kpi-label">Aktiv</span>
                <strong id="tools_active">–</strong>
                <span class="kpi-sub" id="tools_active_percent"></span>
            </div>

            <div class="kpi-card">
                <span class="kpi-label">Bereit</span>
                <strong id="tools_ready">–</strong>
                <span class="kpi-sub" id="tools_ready_percent"></span>
            </div>

            <div class="kpi-card is-status" id="maintenance_card">
                <span class="kpi-label"><span class="status-dot"></span>In Wartung</span>
                <strong id="tools_maintenance">–</strong>
                <span class="kpi-sub" id="maintenance_percent"></span>
            </div>

            <div class="kpi-card is-status" id="critical_card">
                <span class="kpi-label"><span class="status-dot"></span>Standorte mit Handlungsbedarf</span>
                <strong id="locations_critical">–</strong>
                <span class="kpi-sub" id="locations_critical_text"></span>
            </div>

        </section>

        <!-- Standorte -->
        <section class="mgmt-panel">

            <div class="panel-header">
                <div>
                    <h2>Status nach Standort</h2>
                    <span>Anteil Werkzeuge in Wartung – kritische Standorte zuerst</span>
                </div>

                <div class="legend">
                    <span class="legend-item is-normal"><span class="status-dot"></span>unter <span data-threshold="warning"></span></span>
                    <span class="legend-item is-warning"><span class="status-dot"></span>ab <span data-threshold="warning"></span></span>
                    <span class="legend-item is-critical"><span class="status-dot"></span>ab <span data-threshold="critical"></span> oder Mindestbestand erreicht</span>
                </div>
            </div>

            <div id="location_stats">
                <p class="mgmt-empty">Lädt…</p>
            </div>

        </section>

        <!-- =========================================
             Auswertung – alles hierunter gilt für den gewählten Zeitraum
             ========================================= -->
        <section class="analytics" id="analytics" aria-labelledby="analytics_title">

            <div class="analytics-header">
                <div>
                    <h2 id="analytics_title">Auswertung</h2>
                    <span id="analytics_period_text">Lädt…</span>
                </div>

                <div class="period-filter" role="group" aria-label="Zeitraum">
                    <button type="button" data-days="30">30 Tage</button>
                    <button type="button" data-days="90">90 Tage</button>
                    <button type="button" data-days="365">12 Monate</button>
                </div>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <span class="kpi-label">Ausfälle</span>
                    <strong id="kpi_failures">–</strong>
                    <span class="kpi-sub">im Zeitraum</span>
                </div>

                <div class="kpi-card">
                    <span class="kpi-label">Reparaturen</span>
                    <strong id="kpi_repairs">–</strong>
                    <span class="kpi-sub">im Zeitraum</span>
                </div>

                <div class="kpi-card">
                    <span class="kpi-label">Ø Ausfallzeit</span>
                    <strong id="kpi_downtime">–</strong>
                    <span class="kpi-sub">vom Ausfall bis zur Reparatur</span>
                </div>

                <div class="kpi-card is-status" id="open_failures_card">
                    <span class="kpi-label"><span class="status-dot"></span>Offene Ausfälle</span>
                    <strong id="kpi_open_failures">–</strong>
                    <span class="kpi-sub" id="kpi_open_failures_text">aktueller Stand</span>
                </div>
            </div>

            <div class="chart-layout">

                <section class="mgmt-panel chart-card chart-wide">
                    <div class="panel-header">
                        <div>
                            <h2 id="timeline_title">Ausfälle und Reparaturen</h2>
                            <span>Entwicklung über die Zeit</span>
                        </div>
                    </div>
                    <div class="chart-body" id="chart_timeline"></div>
                </section>

                <section class="mgmt-panel chart-card">
                    <div class="panel-header">
                        <div>
                            <h2>Ausfälle nach Ursache</h2>
                            <span>Was führt zur Wartung?</span>
                        </div>
                    </div>
                    <div class="chart-body" id="chart_causes"></div>
                </section>

                <section class="mgmt-panel chart-card">
                    <div class="panel-header">
                        <div>
                            <h2>Häufig betroffene Bauteile</h2>
                            <span>Top 10 – beim Ausfall angegeben</span>
                        </div>
                    </div>
                    <div class="chart-body" id="chart_parts"></div>
                </section>

                <section class="mgmt-panel chart-card">
                    <div class="panel-header">
                        <div>
                            <h2>Werkzeuge mit den meisten Ausfällen</h2>
                            <span>Top 10</span>
                        </div>
                    </div>
                    <div class="chart-body" id="chart_tools"></div>
                </section>

                <section class="mgmt-panel chart-card">
                    <div class="panel-header">
                        <div>
                            <h2>Ø Ausfallzeit je Standort</h2>
                            <span>vom Ausfall bis zur Reparatur</span>
                        </div>
                    </div>
                    <div class="chart-body" id="chart_downtime"></div>
                </section>

            </div>

        </section>

        <!-- Ausfallübersicht -->
        <section class="mgmt-panel">

            <div class="panel-header">
                <div>
                    <h2>Ausfallübersicht</h2>
                    <span>Erfasste Ausfälle im gewählten Zeitraum</span>
                </div>
            </div>

            <div class="repairs-table-wrapper">
                <table class="repairs-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Werkzeug</th>
                            <th>Ursache</th>
                            <th>Bauteil</th>
                            <th>Bemerkung</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody id="failures_table_body">
                        <tr>
                            <td colspan="6">Lädt…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </section>

        <!-- Reparaturübersicht -->
        <section class="mgmt-panel">

            <div class="panel-header">
                <div>
                    <h2>Reparaturübersicht</h2>
                    <span>Erfasste Reparaturen im gewählten Zeitraum</span>
                </div>
            </div>

            <div class="repairs-table-wrapper">
                <table class="repairs-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Werkzeug</th>
                            <th>Zu Ausfall</th>
                            <th>Getauschte Bauteile</th>
                            <th>Bemerkung</th>
                        </tr>
                    </thead>

                    <tbody id="repairs_table_body">
                        <tr>
                            <td colspan="5">Lädt…</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </section>

    </main>

    <script src="/assets/js/api.js"></script>
    <script src="/assets/js/location-status.js"></script>
    <script src="/assets/js/charts.js"></script>
    <script src="script.js"></script>
</body>
</html>