// =========================================
// Werkzeugpool eines Standorts: /pages/line/?id=1
// =========================================
// Benötigt: /assets/js/api.js, /assets/js/tool-status.js,
//           /components/tool_info_dialog.js

const toolsGrid = document.getElementById("tools_grid");
const locationTitle = document.getElementById("location_title");

const currentLocationId = new URLSearchParams(window.location.search).get("id");

// ---------- Standort ----------

async function loadLocation() {
    try {
        const { location } = await apiRequest("locations.php", { params: { id: currentLocationId } });
        locationTitle.textContent = `Werkzeugpool ${location.name}`;
        document.title = `Werkzeugpool ${location.name}`;
    } catch (error) {
        console.error("Fehler beim Laden des Standorts:", error);
        locationTitle.textContent = "Werkzeugpool";
    }
}

// ---------- Werkzeuge ----------

// Wird auch vom Werkzeug-Dialog nach dem Speichern aufgerufen.
async function loadTools() {
    try {
        const { tools } = await apiRequest("tools.php", { params: { location_id: currentLocationId } });
        renderTools(tools);
    } catch (error) {
        console.error("Fehler beim Laden der Werkzeuge:", error);
        showMessage(`Werkzeuge konnten nicht geladen werden: ${error.message}`);
        document.getElementById("tool_count").textContent = "";
    }
}

function renderTools(tools) {
    updateToolCount(Array.isArray(tools) ? tools : []);

    if (!Array.isArray(tools) || tools.length === 0) {
        showMessage("Keine Werkzeuge an diesem Standort");
        return;
    }

    toolsGrid.replaceChildren(...tools.map(buildToolCard));
}

/*
 * Aufbau einer Karte:
 *   Kopf     – Kennung "Werkzeug" + Status-Chip, darunter der Name
 *   Daten    – Verbindung, Lokring, Backenset, Grundkörper
 *   Fußzeile – letzte Reinigung
 */
function buildToolCard(tool) {
    const status = getToolStatus(tool.status_id);

    const article = document.createElement("article");
    // chip_wartung → status-wartung: steuert die Kartenfarbe (styles.css)
    article.className = `line-card ${status.chip.replace("chip_", "status-")}`;
    article.tabIndex = 0;
    article.setAttribute("role", "button");
    article.setAttribute("aria-label", `Werkzeug ${tool.name} öffnen, Status ${status.label}`);

    const open = () => openToolDialog(tool.id, tool.status_id, tool.location_id);
    article.addEventListener("click", open);
    article.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            open();
        }
    });

    // ---------- Kopf ----------

    const eyebrow = document.createElement("span");
    eyebrow.className = "line-card-eyebrow";
    eyebrow.textContent = "Werkzeug";

    const head = document.createElement("div");
    head.className = "line-card-head";
    head.append(eyebrow, buildStatusChip(tool.status_id));

    const title = document.createElement("h3");
    title.textContent = tool.name;

    // ---------- Daten ----------

    const data = document.createElement("dl");
    data.className = "line-card-data";
    data.append(
        buildDataField("Verbindung", tool.connection),
        buildDataField("Lokring", tool.lokring),
        buildDataField("Backenset", tool.jaw_set_name),
        buildDataField("Grundkörper", tool.body),
    );

    // ---------- Fußzeile ----------

    const footer = document.createElement("div");
    footer.className = "line-card-footer";
    footer.textContent = `Letzte Reinigung: ${formatCleaningDate(tool.last_cleaning_at)}`;

    article.append(head, title, data, footer);
    return article;
}

function buildDataField(label, value) {
    const wrapper = document.createElement("div");

    const term = document.createElement("dt");
    term.textContent = label;

    const description = document.createElement("dd");
    description.textContent = value || "–";

    wrapper.append(term, description);
    return wrapper;
}

// "2026-11-06 14:30:00" → "06.11.2026", leer → "–"
// Eigener Name: alle Skripte teilen sich den globalen Namensraum, ein
// zweites formatDate() würde das aus report_dialog.js überschreiben.
function formatCleaningDate(value) {
    if (!value) {
        return "–";
    }

    const [year, month, day] = String(value).slice(0, 10).split("-");
    return day && month && year ? `${day}.${month}.${year}` : "–";
}

// Titelzeile rechts: "6 Werkzeuge · 1 in Wartung"
function updateToolCount(tools) {
    const inMaintenance = tools.filter(tool => tool.status_id === TOOL_STATUS_MAINTENANCE).length;
    const parts = [`${tools.length} ${tools.length === 1 ? "Werkzeug" : "Werkzeuge"}`];

    if (inMaintenance > 0) {
        parts.push(`${inMaintenance} in Wartung`);
    }

    document.getElementById("tool_count").textContent = parts.join(" · ");
}

function showMessage(text) {
    const p = document.createElement("p");
    p.className = "panel-message";
    p.textContent = text;
    toolsGrid.replaceChildren(p);
}

// ---------- Start ----------

if (currentLocationId) {
    loadLocation();
    loadTools();
} else {
    showMessage("Kein Standort ausgewählt");
    document.getElementById("tool_count").textContent = "";
}
