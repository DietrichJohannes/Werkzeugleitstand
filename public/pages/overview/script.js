// =========================================
// Übersicht: alle Werkzeuge mit Suche
// =========================================
// Benötigt: /assets/js/api.js, /assets/js/tool-status.js,
//           /components/tool_info_dialog.js

// Wird auch vom Werkzeug-Dialog nach dem Speichern aufgerufen.
async function loadTools() {
    try {
        const data = await apiRequest('tools.php');

        const tableBody = document.getElementById('toolTableBody');
        tableBody.innerHTML = '';

        if (data.tools.length === 0) {
            showToolMessage('Noch keine Werkzeuge angelegt.');
            updateToolCount(0, 0);
            return;
        }

        data.tools.forEach(tool => {
            const row = document.createElement('tr');

            const nameCell = document.createElement('td');
            const nameText = document.createElement('strong');
            nameText.textContent = tool.name.trim();
            nameCell.appendChild(nameText);

            const statusCell = document.createElement('td');
            statusCell.appendChild(buildStatusChip(tool.status_id));

            row.append(nameCell, statusCell);

            const values = [
                tool.location,
                tool.connection,
                tool.lokring,
                tool.jaw_set_name,
                tool.body
            ];

            values.forEach(value => {
                const cell = document.createElement('td');

                cell.textContent = typeof value === 'string'
                    ? value.trim()
                    : value ?? '';

                row.appendChild(cell);
            });

            const actionCell = document.createElement('td');

            const editButton = document.createElement('button');
            editButton.classList.add('button_neutral', 'button-small');
            editButton.textContent = 'Werkzeug steuern';

            editButton.addEventListener('click', () => {
                openToolDialog(
                    tool.id,
                    tool.status_id,
                    tool.location_id
                );
            });

            actionCell.appendChild(editButton);
            row.appendChild(actionCell);

            tableBody.appendChild(row);
        });

        applySearch(); // Filter nach dem Neuladen wieder anwenden

    } catch (error) {
        console.error('Fehler beim Laden der Werkzeuge:', error);
        showToolMessage(`Werkzeuge konnten nicht geladen werden: ${error.message}`);
        document.getElementById('tool_count').textContent = '';
    }
}

function showToolMessage(text) {
    const cell = document.createElement('td');
    cell.colSpan = 8;
    cell.className = 'panel-empty';
    cell.textContent = text;

    const row = document.createElement('tr');
    row.appendChild(cell);
    document.getElementById('toolTableBody').replaceChildren(row);
}

function updateToolCount(visible, total) {
    document.getElementById('tool_count').textContent = visible === total
        ? `${total} Werkzeuge`
        : `${visible} von ${total} Werkzeugen`;
}


// -------------------------
// Suchfunktion
// -------------------------

const searchInput = document.getElementById('search');

function applySearch() {
    const searchTerms = searchInput.value
        .toLowerCase()
        .trim()
        .split(/\s+/)
        .filter(term => term.length > 0);

    // Meldungszeilen (Lädt…, Fehler) nicht filtern
    const rows = document.querySelectorAll('#toolTableBody tr:not(:has(.panel-empty))');
    let visible = 0;

    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();

        const matches = searchTerms.every(term =>
            rowText.includes(term)
        );

        row.style.display = matches ? '' : 'none';
        if (matches) visible++;
    });

    if (rows.length > 0) {
        updateToolCount(visible, rows.length);
    }
}

searchInput.addEventListener('input', applySearch);


document.addEventListener('DOMContentLoaded', loadTools);