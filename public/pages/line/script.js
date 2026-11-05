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
    }
}

function renderTools(tools) {
    if (!Array.isArray(tools) || tools.length === 0) {
        showMessage("Keine Werkzeuge an diesem Standort");
        return;
    }

    toolsGrid.replaceChildren(...tools.map(buildToolCard));
}

function buildToolCard(tool) {
    const status = getToolStatus(tool.status_id);

    const article = document.createElement("article");
    article.className = `line-card ${status.chip.replace("chip_", "status-")}`;
    article.addEventListener("click", () => {
        openToolDialog(tool.id, tool.status_id, tool.location_id);
    });

    const title = document.createElement("h3");
    title.textContent = tool.name;

    const statusBox = document.createElement("div");
    statusBox.className = status.chip;
    const statusText = document.createElement("strong");
    statusText.textContent = status.label;
    statusBox.append(statusText);

    const connection = document.createElement("p");
    connection.textContent = tool.connection ?? "";
    const connectionBox = document.createElement("div");
    connectionBox.append(connection);

    const info = document.createElement("div");
    info.className = "line-info";
    info.append(statusBox, document.createElement("br"), connectionBox);

    article.append(title, info);
    return article;
}

function showMessage(text) {
    const p = document.createElement("p");
    p.textContent = text;
    toolsGrid.replaceChildren(p);
}

// ---------- Start ----------

if (currentLocationId) {
    loadLocation();
    loadTools();
} else {
    showMessage("Kein Standort ausgewählt");
}
