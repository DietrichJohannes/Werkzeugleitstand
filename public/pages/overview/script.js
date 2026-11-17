// =========================================
// Übersicht: alle Werkzeuge mit Statusfilter und Suche
// =========================================
// Benötigt: /assets/js/api.js, /assets/js/tool-status.js,
//           /components/tool_info_dialog.js

const TABLE_COLUMNS = 7;

const tableBody = document.getElementById('toolTableBody');
const searchInput = document.getElementById('search');
const statusFilter = document.getElementById('status_filter');

let selectedStatus = null;   // null = alle Status

// Wird auch vom Werkzeug-Dialog nach dem Speichern aufgerufen.
async function loadTools() {
    try {
        const data = await apiRequest('tools.php');

        if (data.tools.length === 0) {
            showToolMessage('Noch keine Werkzeuge angelegt.');
            updateStatusCounts([]);
            updateToolCount(0, 0);
            return;
        }

        tableBody.replaceChildren(...data.tools.map(buildToolRow));
        updateStatusCounts(data.tools);
        applyFilters(); // Filter nach dem Neuladen wieder anwenden

    } catch (error) {
        console.error('Fehler beim Laden der Werkzeuge:', error);
        showToolMessage(`Werkzeuge konnten nicht geladen werden: ${error.message}`);
        document.getElementById('tool_count').textContent = '';
    }
}

/*
 * Ganze Zeile ist klickbar (Maus) bzw. per Tab + Enter erreichbar.
 */
function buildToolRow(tool) {
    const row = document.createElement('tr');
    row.className = 'row-clickable';
    row.dataset.status = String(tool.status_id);
    row.tabIndex = 0;
    row.title = 'Werkzeug steuern';
    row.setAttribute('aria-label', `Werkzeug ${tool.name.trim()} steuern`);

    const nameCell = document.createElement('td');
    const nameText = document.createElement('strong');
    nameText.textContent = tool.name.trim();
    nameCell.appendChild(nameText);

    const statusCell = document.createElement('td');
    statusCell.appendChild(buildStatusChip(tool.status_id));

    row.append(nameCell, statusCell);

    [
        tool.location,
        tool.connection,
        tool.lokring,
        tool.jaw_set_name,
        tool.body
    ].forEach(value => {
        const cell = document.createElement('td');
        cell.textContent = typeof value === 'string' ? value.trim() : value ?? '';
        row.appendChild(cell);
    });

    const open = () => openToolDialog(tool.id, tool.status_id, tool.location_id);

    row.addEventListener('click', open);
    row.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            open();
        }
    });

    return row;
}

function showToolMessage(text) {
    const cell = document.createElement('td');
    cell.colSpan = TABLE_COLUMNS;
    cell.className = 'panel-empty';
    cell.textContent = text;

    const row = document.createElement('tr');
    row.appendChild(cell);
    tableBody.replaceChildren(row);
}

function updateToolCount(visible, total) {
    document.getElementById('tool_count').textContent = visible === total
        ? `${total} Werkzeuge`
        : `${visible} von ${total} Werkzeugen`;
}


// -------------------------
// Statusfilter
// -------------------------

// Knöpfe "Alle" + je Status aus TOOL_STATUS (tool-status.js)
function buildStatusFilter() {
    const options = [{ value: null, label: 'Alle' }, ...TOOL_STATUS_OPTIONS];

    statusFilter.replaceChildren(...options.map(option => {
        const button = document.createElement('button');
        button.type = 'button';

        if (option.value !== null) {
            button.dataset.status = String(option.value);
        }

        const count = document.createElement('span');
        count.className = 'status-filter-count';

        button.append(option.label, ' ', count);

        button.addEventListener('click', () => {
            selectedStatus = option.value;
            markSelectedStatus();
            applyFilters();
        });

        return button;
    }));

    markSelectedStatus();
}

function markSelectedStatus() {
    statusFilter.querySelectorAll('button').forEach(button => {
        const value = button.dataset.status ? Number(button.dataset.status) : null;
        button.setAttribute('aria-pressed', String(value === selectedStatus));
    });
}

function updateStatusCounts(tools) {
    statusFilter.querySelectorAll('button').forEach(button => {
        const count = button.dataset.status
            ? tools.filter(tool => Number(tool.status_id) === Number(button.dataset.status)).length
            : tools.length;

        button.querySelector('.status-filter-count').textContent = count;
    });
}


// -------------------------
// Filter anwenden (Status + Suche)
// -------------------------

function applyFilters() {
    const searchTerms = searchInput.value
        .toLowerCase()
        .trim()
        .split(/\s+/)
        .filter(term => term.length > 0);

    // Meldungszeilen (Lädt…, Fehler) nicht filtern
    const rows = tableBody.querySelectorAll('tr:not(:has(.panel-empty))');
    let visible = 0;

    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();

        const matchesStatus = selectedStatus === null
            || Number(row.dataset.status) === selectedStatus;

        const matchesSearch = searchTerms.every(term => rowText.includes(term));

        const matches = matchesStatus && matchesSearch;
        row.style.display = matches ? '' : 'none';
        if (matches) visible++;
    });

    if (rows.length > 0) {
        updateToolCount(visible, rows.length);
    }
}

searchInput.addEventListener('input', applyFilters);

buildStatusFilter();
document.addEventListener('DOMContentLoaded', loadTools);
