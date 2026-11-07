// =========================================
// Reinigung quittieren (letzte Folie)
// =========================================
// Benötigt: /assets/js/api.js, /assets/js/tool-status.js

const cleaningToolSelect = document.getElementById('cleaning_tool');
const cleaningButton = document.getElementById('cleaning_confirm');
const cleaningMessage = document.getElementById('cleaning_message');

function showCleaningMessage(text, isError) {
    cleaningMessage.textContent = text;
    cleaningMessage.classList.toggle('dialog-error', isError);
    cleaningMessage.classList.remove('hidden');
}

async function loadCleaningTools() {
    try {
        const { tools } = await apiRequest('tools.php');
        const usableTools = tools.filter(tool => tool.status_id !== TOOL_STATUS_SCRAPPED);

        cleaningToolSelect.append(...usableTools.map(tool => new Option(tool.name, tool.id)));
    } catch (error) {
        console.error('Fehler beim Laden der Werkzeuge:', error);
        showCleaningMessage(`Werkzeuge konnten nicht geladen werden: ${error.message}`, true);
    }
}

async function confirmCleaning() {
    const toolOption = cleaningToolSelect.selectedOptions[0];

    if (!cleaningToolSelect.value) {
        showCleaningMessage('Bitte ein Werkzeug wählen.', true);
        cleaningToolSelect.focus();
        return;
    }

    cleaningButton.disabled = true;

    try {
        await apiRequest('cleanings.php', { method: 'POST', body: { tool_id: Number(cleaningToolSelect.value) } });

        const time = new Date().toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
        showCleaningMessage(`Reinigung für ${toolOption.text} um ${time} Uhr quittiert.`, false);
        cleaningToolSelect.value = '';

    } catch (error) {
        showCleaningMessage(error.message, true);
    } finally {
        cleaningButton.disabled = false;
    }
}

cleaningButton.addEventListener('click', confirmCleaning);
loadCleaningTools();
