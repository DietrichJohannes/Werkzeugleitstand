// =========================================
// Übersicht: alle Werkzeuge mit Suche
// =========================================
// Benötigt: /assets/js/api.js, /components/tool_info_dialog.js

// Wird auch vom Werkzeug-Dialog nach dem Speichern aufgerufen.
async function loadTools() {
    try {
        const data = await apiRequest('tools.php');

        const tableBody = document.getElementById('toolTableBody');
        tableBody.innerHTML = '';

        data.tools.forEach(tool => {
            const row = document.createElement('tr');

            const values = [
                tool.name,
                tool.status,
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

        const cell = document.createElement('td');
        cell.colSpan = 8;
        cell.textContent = `Werkzeuge konnten nicht geladen werden: ${error.message}`;
        const row = document.createElement('tr');
        row.appendChild(cell);
        document.getElementById('toolTableBody').replaceChildren(row);
    }
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

    const rows = document.querySelectorAll('#toolTableBody tr');

    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();

        const matches = searchTerms.every(term =>
            rowText.includes(term)
        );

        row.style.display = matches ? '' : 'none';
    });
}

searchInput.addEventListener('input', applySearch);


document.addEventListener('DOMContentLoaded', loadTools);