// =========================================
// Dialog: Ersatzteile eines Werkzeugs
// (components/parts_dialog.php)
// =========================================
// Benötigt: /assets/js/api.js
//
// Verwendung: openPartsDialog({ id: 5, name: 'Werkzeug A' })

const partsDialog = {
    dialog:  document.getElementById('parts_dialog'),
    eyebrow: document.getElementById('parts_dialog_eyebrow'),
    error:   document.getElementById('parts_dialog_error'),
    body:    document.getElementById('parts_table_body'),
};

async function openPartsDialog(tool) {
    const d = partsDialog;

    d.eyebrow.textContent = tool.name ?? '';
    d.error.classList.add('hidden');
    setPartsMessage('Lädt…');
    d.dialog.showModal();

    try {
        const { parts } = await apiRequest('parts.php',  { params: { tool_id: tool.id } });
        const { body }  = await apiRequest('bodies.php', { params: { id: tool.id } });
        renderParts(parts);
        getBoMPath(body);
    } catch (error) {
        console.error('Fehler beim Laden der Ersatzteile:', error);
        setPartsMessage('');
        d.error.textContent = `Ersatzteile konnten nicht geladen werden: ${error.message}`;
        d.error.classList.remove('hidden');
    }
}

function closePartsDialog() {
    partsDialog.dialog.close();
}

// ---------- Tabelle ----------

function renderParts(parts) {
    if (!Array.isArray(parts) || parts.length === 0) {
        setPartsMessage('Für dieses Werkzeug sind keine Ersatzteile hinterlegt.');
        return;
    }

    partsDialog.body.replaceChildren(...parts.map(buildPartRow));
}

function buildPartRow(part) {
    const row = document.createElement('tr');

    row.insertCell().textContent = part.name ?? '–';

    const hibeCell = row.insertCell();
    hibeCell.className = 'parts-number';
    hibeCell.textContent = part.hibe_number ?? '–';

    const manufacturerCell = row.insertCell();
    manufacturerCell.className = 'parts-number';
    manufacturerCell.textContent = part.manufacturer_number ?? '–';

    const actionCell = row.insertCell();
    actionCell.className = 'parts-action';

    if (part.hibe_number) {
        const copyButton = document.createElement('button');
        copyButton.type = 'button';
        copyButton.className = 'button_neutral';
        copyButton.textContent = 'HiBe-Nr. kopieren';
        copyButton.addEventListener('click', () => copyToClipboard(part.hibe_number, copyButton));
        actionCell.appendChild(copyButton);
    }

    return row;
}

function setPartsMessage(text) {
    const cell = document.createElement('td');
    cell.colSpan = 4;
    cell.className = 'parts-message';
    cell.textContent = text;

    const row = document.createElement('tr');
    row.appendChild(cell);
    partsDialog.body.replaceChildren(row);
}

// ---------- Kopieren ----------

async function copyToClipboard(text, button) {
    const originalText = button.textContent;

    try {
        await writeClipboard(text);
        button.textContent = 'Kopiert ✓';
    } catch (error) {
        console.error('Kopieren fehlgeschlagen:', error);
        button.textContent = 'Fehler';
    }

    button.disabled = true;
    setTimeout(() => {
        button.textContent = originalText;
        button.disabled = false;
    }, 1200);
}

/*
 * navigator.clipboard gibt es nur über HTTPS oder localhost.
 * Läuft die Seite über http://, wird der ältere Weg über ein
 * unsichtbares Textfeld benutzt.
 */
async function writeClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            return;
        } catch {
            // weiter mit dem Ausweichweg
        }
    }

    const helper = document.createElement('textarea');
    helper.value = text;
    helper.setAttribute('readonly', '');
    helper.style.position = 'fixed';
    helper.style.opacity = '0';

    // Im offenen Dialog einfügen, sonst ist es im modalen Zustand nicht auswählbar
    partsDialog.dialog.appendChild(helper);
    helper.select();
    const ok = document.execCommand('copy');
    helper.remove();

    if (!ok) {
        throw new Error('Zwischenablage nicht verfügbar');
    }
}

// ---------- Ereignisse ----------

partsDialog.dialog.querySelectorAll('[data-parts-close]').forEach(button => {
    button.addEventListener('click', closePartsDialog);
});

// Klick auf den abgedunkelten Hintergrund schließt den Dialog
// (event.target ist auch beim Klick auf den Innenabstand der Dialog selbst,
//  deshalb wird geprüft, ob der Klick außerhalb des Rahmens lag)
partsDialog.dialog.addEventListener('click', event => {
    if (event.target !== partsDialog.dialog) {
        return;
    }

    const box = partsDialog.dialog.getBoundingClientRect();
    const outside = event.clientX < box.left || event.clientX > box.right
                 || event.clientY < box.top  || event.clientY > box.bottom;

    if (outside) {
        closePartsDialog();
    }
});

partsDialog.dialog.querySelector('[data-open-bom-file]')
    .addEventListener('click', openBoMFile);


let bomPath = null;

// Dateipfad aus API-Daten erstellen
function getBoMPath(body) {
    const fileName = body?.bom_file_name;

    if (!fileName) {
        bomPath = null;
        return;
    }

    bomPath = `/documents/?file=${encodeURIComponent(fileName)}`;
}

function openBoMFile() {
    if (!bomPath) {
        alert('Für dieses Werkzeug ist keine Stückliste hinterlegt.');
        return;
    }

    window.open(bomPath, '_blank', 'noopener,noreferrer');
}