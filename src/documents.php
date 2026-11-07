<?php
declare(strict_types=1);

/*
 * ============================================================
 *  Dokumente (BoM-PDFs)
 * ============================================================
 *  Die Dateien liegen außerhalb von public/ und sind nur über
 *  public/documents/index.php erreichbar (mit Rechteprüfung).
 *  Der Dateiname ist gleichzeitig der Wert von bodies.bom_file_name.
 */

const DOCUMENTS_DIR      = __DIR__ . '/../storage/documents';
const DOCUMENT_MAX_BYTES = 20 * 1024 * 1024;

/*
 * Erlaubt keine Pfadtrenner und keinen führenden Punkt. Ein Name aus
 * der URL kann das Verzeichnis dadurch nicht verlassen.
 */
function isValidDocumentName(string $name): bool
{
    return strlen($name) <= 200
        && preg_match('/^[\p{L}\p{N}][\p{L}\p{N} ._()-]*\.pdf\z/u', $name) === 1;
}

function documentPath(string $name): string
{
    return DOCUMENTS_DIR . '/' . $name;
}

function documentExists(string $name): bool
{
    return isValidDocumentName($name) && is_file(documentPath($name));
}

function formatDocument(string $name): array
{
    $path = documentPath($name);

    return [
        'name'        => $name,
        'size'        => filesize($path),
        'modified_at' => date('c', filemtime($path)),
    ];
}

function listDocuments(): array
{
    if (!is_dir(DOCUMENTS_DIR)) {
        return [];
    }

    $names = array_values(array_filter(scandir(DOCUMENTS_DIR), 'documentExists'));
    natcasesort($names);

    return array_map('formatDocument', array_values($names));
}
