// =========================================
// Werkzeug-Status
// =========================================
// Gegenstück zu src/tool_status.php und zur DB-Tabelle tool_statuses –
// bei Änderungen ALLE DREI anpassen.

const TOOL_STATUS_READY       = 1;
const TOOL_STATUS_ACTIVE      = 2;
const TOOL_STATUS_MAINTENANCE = 3;
const TOOL_STATUS_SCRAPPED    = 4;

const TOOL_STATUS = {
    1: { label: 'Bereit',       chip: 'chip_bereit' },
    2: { label: 'Aktiv',        chip: 'chip_aktiv' },
    3: { label: 'Wartung',      chip: 'chip_wartung' },
    4: { label: 'Verschrottet', chip: 'chip_verschrottet' },
};

// Für Auswahllisten: [{ value: 1, label: 'Bereit' }, ...]
const TOOL_STATUS_OPTIONS = Object.entries(TOOL_STATUS)
    .map(([value, status]) => ({ value: Number(value), label: status.label }));

function getToolStatus(statusId) {
    return TOOL_STATUS[Number(statusId)] ?? { label: 'Nicht definiert!', chip: 'chip_neutral' };
}

// true, wenn ein Werkzeug gerade NEU in die Wartung geht
// (dann muss ein Ausfall gemeldet werden).
function isEnteringMaintenance(oldStatusId, newStatusId) {
    return Number(newStatusId) === TOOL_STATUS_MAINTENANCE
        && Number(oldStatusId) !== TOOL_STATUS_MAINTENANCE;
}

// true, wenn ein Werkzeug die Wartung verlässt und wieder eingesetzt wird
// (dann muss eine Reparatur erfasst werden). Verschrotten geht ohne.
function isLeavingMaintenance(oldStatusId, newStatusId) {
    return Number(oldStatusId) === TOOL_STATUS_MAINTENANCE
        && [TOOL_STATUS_READY, TOOL_STATUS_ACTIVE].includes(Number(newStatusId));
}

// Verschrotten und Zurückholen nur mit dem Recht 'tools.scrap':
// Ohne das Recht fehlt die Option, ein verschrottetes Werkzeug bleibt gesperrt.
// Der Server prüft das zusätzlich. Benötigt /assets/js/api.js.
async function applyScrapPermission(select, currentStatusId) {
    const { permissions } = await apiRequest('permissions.php');
    const canScrap = permissions.includes('tools.scrap');
    const isScrapped = Number(currentStatusId) === TOOL_STATUS_SCRAPPED;
    const scrapOption = [...select.options].find(option => Number(option.value) === TOOL_STATUS_SCRAPPED);

    scrapOption.hidden = !canScrap && !isScrapped;
    scrapOption.disabled = !canScrap;
    select.disabled = !canScrap && isScrapped;
}

// Erzeugt ein <span> mit Farbe und Text des Status.
function buildStatusChip(statusId) {
    const status = getToolStatus(statusId);
    const chip = document.createElement('span');
    chip.className = status.chip;
    chip.textContent = status.label;
    return chip;
}
