// =========================================
// Dateiübersicht
// =========================================

function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) {
        return `${Math.ceil(bytes / 1024).toLocaleString('de-DE')} KB`;
    }

    return `${(bytes / 1024 / 1024).toLocaleString('de-DE', { maximumFractionDigits: 1 })} MB`;
}

function buildDocumentRow(documentInfo) {
    const link = document.createElement('a');
    link.href = `/documents/?file=${encodeURIComponent(documentInfo.name)}`;
    link.target = '_blank';
    link.rel = 'noopener';
    link.textContent = documentInfo.name;

    const nameCell = document.createElement('td');
    nameCell.appendChild(link);

    const sizeCell = document.createElement('td');
    sizeCell.textContent = formatFileSize(documentInfo.size);

    const dateCell = document.createElement('td');
    dateCell.textContent = new Date(documentInfo.modified_at).toLocaleString('de-DE');

    const row = document.createElement('tr');
    row.append(nameCell, sizeCell, dateCell);
    return row;
}

function showDocumentsMessage(text) {
    const cell = document.createElement('td');
    cell.colSpan = 3;
    cell.textContent = text;

    const row = document.createElement('tr');
    row.appendChild(cell);

    document.getElementById('documents_list').replaceChildren(row);
}

async function loadDocuments() {
    try {
        const data = await apiRequest('documents.php');

        if (data.documents.length === 0) {
            showDocumentsMessage('Noch keine Dateien vorhanden.');
            return;
        }

        document.getElementById('documents_list').replaceChildren(...data.documents.map(buildDocumentRow));

    } catch (error) {
        console.error('Fehler beim Laden der Dokumente:', error);
        showDocumentsMessage(`Dokumente konnten nicht geladen werden: ${error.message}`);
    }
}

loadDocuments();
