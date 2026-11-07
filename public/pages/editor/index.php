<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Werkzeugleitstand – Administration</title>

    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="styles.css">
</head>

<body>

<div class="admin-layout">

    <!-- =========================================
         Sidebar
         ========================================= -->

    <aside class="admin-sidebar">

        <div class="admin-logo">
            <strong>Werkzeugleitstand</strong>
            <span>ADMINISTRATION</span>
        </div>


        <div class="nav-section">

            <div class="nav-section-title">
                Übersicht
            </div>

            <a href="/">
                Dashboard
            </a>

        </div>


        <div class="nav-section">

            <div class="nav-section-title">
                Stammdaten
            </div>

            <a href="#" data-section="tools" class="nav-link active">
                <span>Werkzeuge</span>
                <span id="tools_all_menue" class="nav-count"></span>
            </a>

            <a href="#" data-section="bauteile" class="nav-link">
                <span>Bauteile</span>
                <span id="parts_all_menue" class="nav-count"></span>
            </a>

            <a href="#" data-section="standorte" class="nav-link">
                <span>Standorte</span>
                <span id="locations_all_menue" class="nav-count"></span>
            </a>

            <a href="#" data-section="koerper" class="nav-link">
                <span>Grundkörper</span>
                <span id="bodies_all_menue" class="nav-count"></span>
            </a>

            <a href="#" data-section="backensets" class="nav-link">
                <span>Backensets</span>
                <span id="jaw_sets_all_menue" class="nav-count"></span>
            </a>

            <a href="#" data-section="verbindungen" class="nav-link">
                <span>Verbindungen</span>
                <span id="connections_all_menue" class="nav-count"></span>
            </a>
        </div>

        <div class="nav-section">

            <div class="nav-section-title">
                Auswahllisten
            </div>

            <a href="#" data-section="ausfallursachen" class="nav-link">
                <span>Ausfallursachen</span>
                <span id="failure_causes_all_menue" class="nav-count"></span>
            </a>
        </div>
    </aside>


    <!-- =========================================
         Hauptbereich
         ========================================= -->

    <main class="admin-main">

        <header class="admin-header">

            <div class="admin-header-title" id="page_breadcrumb">
                ADMIN / TOOLS
            </div>

            <div class="admin-user">
                Administrator
            </div>

        </header>


        <div class="admin-content">

            <!-- Seitenüberschrift -->

            <div class="page-header">

                <div>

                    <h1 id="page_title">Werkzeuge</h1>

                    <p class="page-description" id="page_description">
                        Werkzeugstammdaten verwalten.
                    </p>

                </div>


                <button
                    class="button_positiv"
                    id="create_button"
                    onclick="onCreateClick()">

                    + Werkzeug anlegen

                </button>

            </div>


            <!-- Statistik (nur für Werkzeuge sichtbar) -->

            <div class="admin-stats" id="stats_block">

                <div class="stat-card">
                    <span>Werkzeuge gesamt</span>
                    <strong id="tools_all"></strong>
                </div>

                <div class="stat-card">
                    <span>Aktiv</span>
                    <strong id="tools_active"></strong>
                </div>

                <div class="stat-card">
                    <span>In Wartung</span>
                    <strong id="tools_maintenance"></strong>
                </div>

            </div>


            <!-- Toolbar -->

            <div class="table-toolbar">

                <div class="toolbar-left">

                    <input
                        type="text"
                        id="search_input"
                        class="search-input"
                        placeholder="Suchen...">

                </div>


                <div class="toolbar-right">

                    <button class="button_neutral" onclick="exportCsv()">
                        Export
                    </button>

                    <button class="button_neutral" onclick="reloadCurrentSection()">
                        Aktualisieren
                    </button>

                </div>

            </div>


            <!-- Tabelle -->

            <table class="admin-table" id="data_table">

                <thead>
                    <!-- Spalten setzt script.js je nach Sektion -->
                    <tr id="table_head_row"></tr>
                </thead>

                <tbody id="admin-table">
                </tbody>

            </table>

            <!-- Hinweis bei leerer Liste oder Ladefehler -->

            <div class="empty-state hidden" id="empty_state">
                <strong id="empty_state_title"></strong>
                <span id="empty_state_text"></span>
            </div>

            <div class="table-footer">
                <span id="table_count"></span>
            </div>

        </div>

    </main>

</div>



<!-- =========================================
     Dialog: Anlegen + Bearbeiten (für alle Sektionen)
     Die Felder baut script.js aus der Sektions-Konfiguration.
     ========================================= -->

<dialog id="entity_dialog" class="tool_dialog">

    <form id="entity_form" novalidate>

        <div class="dialog-header">

            <div>
                <span class="dialog-eyebrow" id="entity_dialog_eyebrow"></span>
                <h2 id="entity_dialog_title"></h2>
            </div>

            <button type="button" class="dialog-close" onclick="closeEntityDialog()">×</button>

        </div>

        <div class="dialog-form">
            <p class="dialog-error hidden" id="entity_dialog_error"></p>
            <div id="entity_form_fields"></div>
        </div>

        <div class="dialog-actions">
            <button type="button" class="button_negativ" onclick="closeEntityDialog()">Abbrechen</button>
            <button type="submit" class="button_positiv" id="entity_save_button">Speichern</button>
        </div>

    </form>

</dialog>


<!-- =========================================
     Dialog: Löschen bestätigen (für alle Sektionen)
     ========================================= -->

<dialog id="delete_dialog" class="tool_dialog">

    <div class="dialog-header">
        <div>
            <h2 id="delete_dialog_title">Wirklich löschen?</h2>
        </div>
        <button type="button" class="dialog-close" onclick="cancelDelete()">×</button>
    </div>

    <div class="dialog-form">
        <p class="dialog-error hidden" id="delete_dialog_error"></p>
        <p class="dialog-text" id="delete_dialog_text"></p>
    </div>

    <div class="dialog-actions">
        <button type="button" class="button_positiv" onclick="cancelDelete()">Abbrechen</button>
        <button type="button" class="button_delete" id="delete_confirm_button" onclick="confirmDelete()">Löschen</button>
    </div>

</dialog>

<?php include __DIR__ . '/../../components/report_dialog.php'; ?>
<?php include __DIR__ . '/../../components/document_upload_dialog.php'; ?>

<script src="/assets/js/api.js"></script>
<script src="/assets/js/tool-status.js"></script>
<script src="/components/report_dialog.js"></script>
<script src="/components/document_upload_dialog.js"></script>
<script src="script.js"></script>
</body>
</html>