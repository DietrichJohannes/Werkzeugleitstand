// =========================================
// Dialoge: Ausfall melden / Reparatur erfassen
// (components/report_dialog.php)
// =========================================
// Benötigt: /assets/js/api.js
//
// Verwendung:
//   const saved = await reportFailure(tool, { hint, beforeSave });
//   const saved = await reportRepair(tool, { hint, afterSave });
//   tool  = { id, name }
//   saved = true  → gespeichert
//   saved = false → abgebrochen, nichts gespeichert

// ---------- Ausfall melden ----------

const failureDialog = setupFormDialog('failure_dialog', {
    eyebrow:     'failure_dialog_eyebrow',
    hint:        'failure_dialog_hint',
    error:       'failure_dialog_error',
    cause:       'failure_cause',
    part:        'failure_part',
    description: 'failure_description',
});

/*
 * Den Status "Wartung" setzt der Server zusammen mit dem Ausfall.
 *
 * beforeSave = optionale Funktion, die vor dem Speichern des Ausfalls
 *              läuft (z. B. neuen Standort speichern). Schlägt sie fehl,
 *              wird der Ausfall nicht gespeichert und der Dialog bleibt
 *              offen. Darf bei erneutem Speichern wieder laufen.
 */
async function reportFailure(tool, { hint = null, beforeSave = null } = {}) {
    const ui = failureDialog.ui;

    let causes, parts;
    try {
        [causes, parts] = await Promise.all([
            apiRequest('failure_causes.php', { params: { active: 1 } }).then(data => data.failure_causes),
            loadToolParts(tool.id),
        ]);
    } catch (error) {
        alert(`Der Dialog konnte nicht geöffnet werden: ${error.message}`);
        return false;
    }

    ui.eyebrow.textContent = tool.name ?? '';
    ui.hint.textContent = hint ?? '';
    ui.hint.classList.toggle('hidden', !hint);

    fillSelect(ui.cause, '– bitte wählen –', causes.map(cause => [cause.id, cause.name]), { placeholderDisabled: true });
    fillSelect(ui.part, '– unbekannt / keins –', parts.map(part => [part.id, partLabel(part)]));
    ui.description.value = '';

    return failureDialog.open(async () => {
        if (!ui.cause.value) {
            ui.cause.focus();
            throw new Error('Bitte eine Ursache wählen.');
        }

        if (beforeSave) {
            await beforeSave();
        }

        await apiRequest('failures.php', {
            method: 'POST',
            body: {
                tool_id:     tool.id,
                cause_id:    Number(ui.cause.value),
                part_id:     ui.part.value ? Number(ui.part.value) : null,
                description: ui.description.value.trim() || null,
            },
        });
    });
}

// ---------- Reparatur erfassen ----------

const repairDialog = setupFormDialog('repair_dialog', {
    eyebrow:     'repair_dialog_eyebrow',
    hint:        'repair_dialog_hint',
    error:       'repair_dialog_error',
    failure:     'repair_failure',
    parts:       'repair_parts',
    description: 'repair_description',
});

/*
 * hint      = eigener Hinweistext (sonst: "wird auf Bereit gesetzt")
 * afterSave = optionale Funktion nach dem Speichern der Reparatur
 *             (z. B. gewählten Status/Standort setzen). Schlägt sie fehl,
 *             bleibt der Dialog offen; erneutes Speichern legt die
 *             Reparatur nicht doppelt an.
 * Wird afterSave übergeben, schließt die Reparatur die Wartung ab:
 * Gibt es einen offenen Ausfall, muss er ausgewählt werden.
 */
async function reportRepair(tool, { hint = null, afterSave = null } = {}) {
    const ui = repairDialog.ui;
    const closesMaintenance = afterSave !== null;

    let openFailures, parts;
    try {
        [openFailures, parts] = await Promise.all([
            apiRequest('failures.php', { params: { tool_id: tool.id, open: 1 } }).then(data => data.failures),
            loadToolParts(tool.id),
        ]);
    } catch (error) {
        alert(`Der Dialog konnte nicht geöffnet werden: ${error.message}`);
        return false;
    }

    ui.eyebrow.textContent = tool.name ?? '';
    ui.hint.textContent = hint ?? 'Nach dem Speichern wird das Werkzeug auf „Bereit“ gesetzt.';

    // Neuester offener Ausfall ist vorausgewählt
    fillSelect(ui.failure, '– ohne Ausfall –', openFailures.map(failure => [failure.id, failureLabel(failure)]));
    if (openFailures.length > 0) {
        ui.failure.value = String(openFailures[0].id);

        // Wartung abschließen → "ohne Ausfall" nicht erlaubt
        if (closesMaintenance) {
            ui.failure.options[0].remove();
        }
    }

    fillPartCheckboxes(ui.parts, parts);
    checkPartOfSelectedFailure(openFailures);
    ui.failure.onchange = () => checkPartOfSelectedFailure(openFailures);

    ui.description.value = '';

    let repairSaved = false;

    return repairDialog.open(async () => {
        if (!repairSaved) {
            const partIds = [...ui.parts.querySelectorAll('input:checked')].map(box => Number(box.value));

            await apiRequest('repairs.php', {
                method: 'POST',
                body: {
                    tool_id:     tool.id,
                    failure_id:  ui.failure.value ? Number(ui.failure.value) : null,
                    part_ids:    partIds,
                    description: ui.description.value.trim() || null,
                },
            });
            repairSaved = true;
        }

        if (afterSave) {
            try {
                await afterSave();
            } catch (error) {
                throw new Error(`Reparatur gespeichert, aber der Status konnte nicht geändert werden: ${error.message}`);
            }
        }
    });
}

// Das beim Ausfall gemeldete Bauteil gleich mit ankreuzen
function checkPartOfSelectedFailure(openFailures) {
    const ui = repairDialog.ui;
    const failure = openFailures.find(f => String(f.id) === ui.failure.value);

    if (failure?.part_id) {
        const box = ui.parts.querySelector(`input[value="${failure.part_id}"]`);
        if (box) box.checked = true;
    }
}

function fillPartCheckboxes(container, parts) {
    if (parts.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'checkbox-list-empty';
        empty.textContent = 'Für dieses Werkzeug sind keine Bauteile hinterlegt.';
        container.replaceChildren(empty);
        return;
    }

    container.replaceChildren(...parts.map(part => {
        const box = document.createElement('input');
        box.type = 'checkbox';
        box.value = part.id;

        const label = document.createElement('label');
        label.className = 'checkbox-item';
        label.append(box, partLabel(part));
        return label;
    }));
}

// ---------- Gemeinsame Helfer ----------

async function loadToolParts(toolId) {
    const data = await apiRequest('parts.php', { params: { tool_id: toolId } });
    return data.parts;
}

function partLabel(part) {
    return part.hibe_number ? `${part.name} (HiBe ${part.hibe_number})` : part.name;
}

function failureLabel(failure) {
    const parts = [formatDate(failure.created_at), failure.cause_name ?? 'ohne Ursache'];
    if (failure.part_name) parts.push(failure.part_name);
    return parts.join(' – ');
}

// "2026-10-30 14:05:00" → "30.10.2026"
function formatDate(value) {
    if (!value) return '';
    const [date] = value.split(' ');
    const [year, month, day] = date.split('-');
    return `${day}.${month}.${year}`;
}

/*
 * entries = [[value, text], ...]
 */
function fillSelect(select, placeholder, entries, { placeholderDisabled = false } = {}) {
    const first = new Option(placeholder, '');
    first.disabled = placeholderDisabled;

    select.replaceChildren(first, ...entries.map(([value, text]) => new Option(text, value)));
    select.value = '';
}

/*
 * Verdrahtet einen <dialog> mit <form>:
 *   - Speichern ruft die bei open() übergebene Funktion auf.
 *     Wirft sie einen Fehler, bleibt der Dialog offen und zeigt ihn an.
 *   - Abbrechen, × und Escape schließen ohne zu speichern.
 *   - Zeichenzähler für Textfelder mit data-counter-for.
 */
function setupFormDialog(dialogId, elementIds) {
    const dialog = document.getElementById(dialogId);
    const form = dialog.querySelector('form');
    const saveButton = dialog.querySelector('[data-dialog-save]');

    const ui = Object.fromEntries(
        Object.entries(elementIds).map(([key, id]) => [key, document.getElementById(id)])
    );

    let onSave = null;
    let resolveOpen = null;

    function finish(saved) {
        dialog.close();
        resolveOpen?.(saved);
        resolveOpen = null;
    }

    function setBusy(busy) {
        saveButton.disabled = busy;
        saveButton.textContent = busy ? 'Speichert…' : 'Speichern';
    }

    function showError(message) {
        ui.error.textContent = message;
        ui.error.classList.toggle('hidden', !message);
    }

    function updateCounters() {
        dialog.querySelectorAll('[data-counter-for]').forEach(counter => {
            const field = document.getElementById(counter.dataset.counterFor);
            counter.textContent = `${field.value.length} / ${field.maxLength}`;
        });
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        showError('');
        setBusy(true);

        try {
            await onSave();
            finish(true);
        } catch (error) {
            showError(error.message);
        } finally {
            setBusy(false);
        }
    });

    form.addEventListener('input', updateCounters);

    dialog.querySelectorAll('[data-dialog-cancel]').forEach(button => {
        button.addEventListener('click', () => finish(false));
    });

    // Escape-Taste
    dialog.addEventListener('cancel', event => {
        event.preventDefault();
        finish(false);
    });

    return {
        ui,
        open(saveFunction) {
            onSave = saveFunction;
            showError('');
            setBusy(false);
            updateCounters();
            dialog.showModal();

            return new Promise(resolve => {
                resolveOpen = resolve;
            });
        },
    };
}
