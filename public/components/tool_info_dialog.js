// =========================================
// Werkzeug-Dialog (components/tool_info_dialog.php)
// =========================================
// Benötigt: /assets/js/api.js, /assets/js/tool-status.js,
//           /components/report_dialog.js (+ report_dialog.php),
//           /components/parts_dialog.js (+ parts_dialog.php)
// Die einbindende Seite muss eine Funktion loadTools() haben,
// die nach dem Speichern die Liste neu lädt.

const ToolInfoDialog = document.getElementById("tool_info_dialog");
const LocationSelect = document.getElementById("location_dropdown");
const StatusSelect = document.getElementById("status_dropdown");

let currentToolId = null;
let currentToolName = '';
let currentToolStatusId = null;   // Status beim Öffnen des Dialogs

async function openToolDialog(toolId, statusId, locationId) {
    currentToolId = toolId;
    currentToolStatusId = Number(statusId);

    try {
        await loadLocationOptions();
        await loadToolDetails(toolId);
    } catch (error) {
        console.error("Fehler beim Öffnen des Werkzeugs:", error);
        alert(`Werkzeug konnte nicht geladen werden: ${error.message}`);
        return;
    }

    LocationSelect.value = String(locationId);
    StatusSelect.value = String(statusId);

    ToolInfoDialog.showModal();
}

async function loadLocationOptions() {
    const data = await apiRequest("locations.php");

    if (data.locations.length > 0) {
        LocationSelect.replaceChildren(
            ...data.locations.map(location => new Option(location.name, location.id))
        );
    }
}

async function loadToolDetails(toolId) {
    const { tool } = await apiRequest("tools.php", { params: { id: toolId } });

    currentToolName = tool.name;
    document.getElementById("tool_name").textContent = tool.name;
    document.getElementById("tool_body").textContent = tool.body;
    document.getElementById("tool_backenset").textContent = tool.jaw_set_name;
    document.getElementById("tool_lokring").textContent = tool.lokring;
    document.getElementById("tool_connection").textContent = tool.connection;
}

// Nach der Reparatur ist das Werkzeug "Bereit" → Dialog schließen, Liste neu laden
async function createRepair() {
    const saved = await reportRepair({ id: currentToolId, name: currentToolName });

    if (saved) {
        ToolInfoDialog.close();
        loadTools();
    }
}

/*
 * Speichern:
 *  - Status wechselt NEU auf "Wartung"  → zuerst Ausfall melden
 *  - Status verlässt "Wartung" (Bereit/Aktiv) → zuerst Reparatur erfassen
 * Bricht der Benutzer im zweiten Dialog ab, wird nichts gespeichert.
 */
async function saveToolChanges() {
    const changes = { status_id: StatusSelect.value, location_id: LocationSelect.value };
    const saveTool = () => apiRequest("tools.php", {
        method: "PATCH",
        params: { id: currentToolId },
        body: changes,
    });

    if (isEnteringMaintenance(currentToolStatusId, changes.status_id)) {
        const saved = await reportFailure(
            { id: currentToolId, name: currentToolName },
            {
                hint: "Das Werkzeug wird auf „Wartung“ gesetzt. Bitte den Grund für den Ausfall angeben.",
                beforeSave: saveTool,
            }
        );

        if (!saved) {
            return; // abgebrochen → Werkzeug-Dialog bleibt offen, nichts gespeichert
        }
    } else if (isLeavingMaintenance(currentToolStatusId, changes.status_id)) {
        const saved = await reportRepair(
            { id: currentToolId, name: currentToolName },
            {
                hint: `Das Werkzeug verlässt die Wartung und wird auf „${getToolStatus(changes.status_id).label}“ gesetzt. Bitte die Reparatur erfassen.`,
                afterSave: saveTool,
            }
        );

        if (!saved) {
            return;
        }
    } else {
        try {
            await saveTool();
        } catch (error) {
            alert(`Änderungen konnten nicht gespeichert werden: ${error.message}`);
            return;
        }
    }

    ToolInfoDialog.close();
    loadTools();
}

function closeToolDialog() {
    ToolInfoDialog.close();
}

function openToolParts() {
    openPartsDialog({ id: currentToolId, name: currentToolName });
}

function openInstruction() {
    window.open("/pages/instruction/repair/");
}
