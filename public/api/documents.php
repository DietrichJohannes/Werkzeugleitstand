<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/api_bootstrap.php';
require_once __DIR__ . '/../../src/documents.php';

/*
 * ============================================================
 *  Dokumente-API (BoM-PDFs)
 * ============================================================
 *  GET    /api/documents.php     alle verfügbaren PDFs
 *  POST   /api/documents.php     PDF hochladen (Einrichter)
 *
 *  POST erwartet als Ausnahme kein JSON, sondern multipart/form-data:
 *    file (PDF-Datei), name (Anzeigename, ".pdf" wird ergänzt)
 *
 *  Abruf einer Datei: /documents/?file=<name>
 */

function handleGet(): void
{
    sendJson(['success' => true, 'documents' => listDocuments()]);
}

/*
 * Prüft den Upload und gibt den temporären Pfad zurück.
 * Überschreitet die Anfrage post_max_size, ist $_FILES leer.
 */
function requireUploadedPdf(): string
{
    $file = $_FILES['file'] ?? null;

    if (!is_array($file) || !is_int($file['error'])) {
        sendError(400, 'Keine Datei übergeben oder Datei zu groß für den Server');
    }

    match ($file['error']) {
        UPLOAD_ERR_OK         => null,
        UPLOAD_ERR_NO_FILE    => sendError(400, 'Keine Datei ausgewählt'),
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE  => sendError(413, 'Die Datei ist zu groß für den Server'),
        default               => throw new RuntimeException("Upload-Fehler {$file['error']}"),
    };

    if ($file['size'] > DOCUMENT_MAX_BYTES) {
        sendError(413, 'Die Datei ist zu groß (max. ' . DOCUMENT_MAX_BYTES / 1024 / 1024 . ' MB)');
    }

    // Der Inhalt entscheidet, nicht Dateiendung oder Angabe des Browsers
    if (mime_content_type($file['tmp_name']) !== 'application/pdf') {
        sendError(415, 'Die Datei ist keine gültige PDF');
    }

    return $file['tmp_name'];
}

function handleUpload(): void
{
    $tmpPath = requireUploadedPdf();

    $title = requireString($_POST['name'] ?? null, 'Name', 100);
    $name  = preg_replace('/\.pdf\z/i', '', $title) . '.pdf';

    if (!isValidDocumentName($name)) {
        sendError(400, 'Der Name muss mit einem Buchstaben oder einer Ziffer beginnen und darf nur Buchstaben, Ziffern, Leerzeichen und . _ ( ) - enthalten');
    }

    if (file_exists(documentPath($name))) {
        sendError(409, "Eine Datei mit dem Namen „{$name}“ existiert bereits");
    }

    if (!is_dir(DOCUMENTS_DIR) && !mkdir(DOCUMENTS_DIR, 0775, true) && !is_dir(DOCUMENTS_DIR)) {
        throw new RuntimeException('Dokumentenordner konnte nicht angelegt werden');
    }

    if (!move_uploaded_file($tmpPath, documentPath($name))) {
        throw new RuntimeException("Datei konnte nicht gespeichert werden: $name");
    }

    sendJson([
        'success'  => true,
        'message'  => 'Datei wurde hochgeladen',
        'document' => formatDocument($name),
    ], 201);
}

handleRequest([
    'GET'  => ['documents.read',  'handleGet'],
    'POST' => ['documents.write', 'handleUpload'],
]);
