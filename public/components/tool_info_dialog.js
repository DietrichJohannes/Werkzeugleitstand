// =========================================
// Werkzeug-Dialog (components/tool_info_dialog.php)
// =========================================
// Benötigt: /assets/js/api.js, /assets/js/tool-status.js,
//           /components/report_dialog.js (+ report_dialog.php),
//           /components/parts_dialog.js (+ parts_dialog.php)
// Die einbindende Seite muss eine Funktion loadTools() haben,
// die nach dem Speichern die Liste neu lädt.
// formatDate() stammt aus report_dialog.js.
//
// Einstieg: openToolDialog(). closeToolDialog(), saveToolChanges(),
// createRepair() und openToolParts() werden per onclick aus
// tool_info_dialog.php aufgerufen.

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
        await applyScrapPermission(StatusSelect, statusId);
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
    document.getElementById("tool_last_cleaning").textContent = formatDate(tool.last_cleaning_at) || "Noch kein Reinigung angelegt";

    await loadToolHistory(toolId);
}

/*
 * Verlauf (Status-, Standort- und Stammdatenänderungen) – neueste zuerst.
 * Ein Fehler hier soll den Dialog nicht blockieren.
 */
async function loadToolHistory(toolId) {
    const list = document.getElementById("tool_history_list");

    try {
        const { events } = await apiRequest("tool_events.php", { params: { tool_id: toolId } });

        if (events.length === 0) {
            list.replaceChildren(historyItem("", "Noch keine Änderungen erfasst", ""));
            return;
        }

        list.replaceChildren(...events.map(event => historyItem(
            formatDateTimeShort(event.created_at),
            describeToolEvent(event),
            [event.created_by_name, event.description].filter(Boolean).join(" · ")
        )));
    } catch (error) {
        console.error("Verlauf konnte nicht geladen werden:", error);
        list.replaceChildren(historyItem("", "Verlauf konnte nicht geladen werden", ""));
    }
}

function describeToolEvent(event) {
    if (event.field === "created") {
        return event.new_label ? `Angelegt (Standort ${event.new_label})` : "Angelegt";
    }
    return `${event.field_label}: ${event.old_label ?? "–"} → ${event.new_label ?? "–"}`;
}

// textContent statt innerHTML – Werte kommen aus der Datenbank
function historyItem(time, text, meta) {
    const item = document.createElement("li");

    const timeEl = document.createElement("span");
    timeEl.className = "tool-history-time";
    timeEl.textContent = time;

    const textEl = document.createElement("span");
    textEl.className = "tool-history-text";
    textEl.textContent = text;

    item.append(timeEl, textEl);

    if (meta) {
        const metaEl = document.createElement("span");
        metaEl.className = "tool-history-meta";
        metaEl.textContent = meta;
        item.append(metaEl);
    }

    return item;
}

// "2026-10-30 14:05:00" → "30.10.2026 14:05"
function formatDateTimeShort(value) {
    if (!value) return "";
    const [, time = ""] = value.split(" ");
    return `${formatDate(value)} ${time.slice(0, 5)}`.trim();
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
                // Den Status setzt der Server zusammen mit dem Ausfall
                beforeSave: () => apiRequest("tools.php", {
                    method: "PATCH",
                    params: { id: currentToolId },
                    body: { location_id: changes.location_id },
                }),
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
