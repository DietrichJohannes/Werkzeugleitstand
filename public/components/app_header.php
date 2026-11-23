<?php
declare(strict_types=1);

/* =========================================
   Globale Kopfzeile mit Hauptmenü – auf JEDER Seite
   direkt nach <body> einbinden:

     <?php include __DIR__ . '/../../components/app_header.php'; ?>
     <script src="<?= asset('/assets/js/app-header.js') ?>"></script>   (am Seitenende)

   Aktiver Menüpunkt, angemeldeter Benutzer und Rechte
   (data-permission) setzt /assets/js/app-header.js.
   Die Rechte zusätzlich auf dem Server
   ========================================= */
?>
<header class="app-header">
    <a class="app-brand" href="/" title="Zur Startseite">
        <strong>BSH Werkzeugleitstand</strong>
        <span>Lokring</span>
    </a>

    <nav class="app-nav" aria-label="Hauptmenü">
        <a href="/pages/overview/">Übersicht</a>
        <a href="/pages/management/">Management</a>
        <a href="/documents/" data-permission="documents.access" hidden>Dokumente</a>

        <details class="app-nav-menu" data-permission="documents.access" hidden>
            <summary>Anleitungen</summary>
            <div class="app-nav-dropdown">
                <a href="/pages/instruction/cleaning/">Werkzeug reinigen</a>
                <a href="/pages/instruction/repair/">Werkzeug warten</a>
            </div>
        </details>

        <a href="/pages/editor/" class="app-nav-admin" data-permission="admin.access" hidden>Verwaltung</a>
    </nav>

    <div class="app-user" id="app_user" hidden>
        <div class="app-user-text">
            <strong id="app_user_name"></strong>
            <span id="app_user_role"></span>
        </div>
        <div class="app-avatar" id="app_user_avatar" aria-hidden="true"></div>
    </div>
</header>
