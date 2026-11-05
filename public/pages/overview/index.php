<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Übersicht</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
</head>
<body>
    <button class="button_neutral" onclick="history.back()">Zurück</button>
    <br>
    <br>
    <h1>Übersicht Lokring</h1>
    <br>
    <p>Suche</p>
    <input
        type="text"
        id="search"
        placeholder="Name, Status, Standort, Lokring ..."
    >
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

    </tbody>
</table>

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