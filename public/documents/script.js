// =========================================
// Dateiübersicht (Dokumente-Explorer)
// =========================================
// Benötigt: /assets/js/api.js

const documentsSearch = document.getElementById('documents_search');
let documentsTotal = 0;

function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) {
        return `${Math.ceil(bytes / 1024).toLocaleString('de-DE')} KB`;
    }

    return `${(bytes / 1024 / 1024).toLocaleString('de-DE', { maximumFractionDigits: 1 })} MB`;
}

function buildDocumentRow(documentInfo) {
    const link = document.createElement('a');
    link.className = 'document-link';
    link.href = `/documents/?file=${encodeURIComponent(documentInfo.name)}`;
    link.target = '_blank';
    link.rel = 'noopener';

    const badge = document.createElement('span');
    badge.className = 'document-badge';
    badge.textContent = 'PDF';

    const name = document.createElement('span');
    name.textContent = documentInfo.name;

    link.append(badge, name);

    const nameCell = document.createElement('td');
    nameCell.appendChild(link);

    const sizeCell = document.createElement('td');
    sizeCell.className = 'document-number';
    sizeCell.textContent = formatFileSize(documentInfo.size);

    const dateCell = document.createElement('td');
    dateCell.className = 'document-number';
    dateCell.textContent = new Date(documentInfo.modified_at).toLocaleString('de-DE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });

    const row = document.createElement('tr');
    row.dataset.search = documentInfo.name.toLowerCase();
    row.append(nameCell, sizeCell, dateCell);
    return row;
}

function showDocumentsMessage(text) {
    const cell = document.createElement('td');
    cell.colSpan = 3;
    cell.className = 'panel-empty';
    cell.textContent = text;

    const row = document.createElement('tr');
    row.appendChild(cell);

    document.getElementById('documents_list').replaceChildren(row);
}

function updateDocumentsCount(visible) {
    document.getElementById('documents_count').textContent = visible === documentsTotal
        ? `${documentsTotal} Dokumente`
        : `${visible} von ${documentsTotal} Dokumenten`;
}

function applyDocumentsSearch() {
    const terms = documentsSearch.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
    const rows = document.querySelectorAll('#documents_list tr[data-search]');
    let visible = 0;

    rows.forEach(row => {
        const matches = terms.every(term => row.dataset.search.includes(term));
        row.hidden = !matches;
        if (matches) visible++;
    });

    if (rows.length > 0) {
        updateDocumentsCount(visible);
    }
}

async function loadDocuments() {
    try {
        const data = await apiRequest('documents.php');
        documentsTotal = data.documents.length;

        if (documentsTotal === 0) {
            showDocumentsMessage('Noch keine Dateien vorhanden.');
            updateDocumentsCount(0);
            return;
        }

        document.getElementById('documents_list').replaceChildren(...data.documents.map(buildDocumentRow));
        applyDocumentsSearch();

    } catch (error) {
        console.error('Fehler beim Laden der Dokumente:', error);
        showDocumentsMessage(`Dokumente konnten nicht geladen werden: ${error.message}`);
        document.getElementById('documents_count').textContent = '';
    }
}

documentsSearch.addEventListener('input', applyDocumentsSearch);

loadDocuments();
