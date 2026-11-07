// =========================================
// Einrichterpanel
// =========================================
// Jede Sektion wird nur über ihre Konfiguration beschrieben:
//   - welche API, welche Spalten in der Tabelle
//   - welche Felder im Dialog (Anlegen + Bearbeiten)
// Tabelle, Dialog, Löschen, Suche und Export funktionieren
// dadurch für alle Sektionen gleich.

// Benötigt: /assets/js/api.js, /assets/js/tool-status.js, /components/report_dialog.js,
//           /components/document_upload_dialog.js

// Auswahllisten für Dropdowns im Dialog (Wert ist die id, außer "value" sagt etwas anderes)
const lookups = {
    documents:   { endpoint: 'documents.php',   key: 'documents',   label: item => item.name, value: item => item.name },
    locations:   { endpoint: 'locations.php',   key: 'locations',   label: item => item.name },
    bodies:      { endpoint: 'bodies.php',      key: 'bodies',      label: item => item.name },
    jaw_sets:    { endpoint: 'jaw_sets.php',    key: 'jaw_sets',    label: item => `${item.name} (${item.connection})` },
    connections: { endpoint: 'connections.php', key: 'connections', label: item => item.name },
};


// =========================================
// Sektions-Konfiguration
// =========================================

const sections = {

    tools: {
        title: 'Werkzeuge',
        breadcrumb: 'Übersicht Werkzeuge',
        description: 'Werkzeugstammdaten verwalten.',
        createLabel: '+ Werkzeug anlegen',
        entityName: 'Werkzeug',
        endpoint: 'tools.php',
        listKey: 'tools',
        showStats: true,
        columns: [
            { label: 'ID',         value: t => `#${t.id}`, className: 'id-cell' },
            { label: 'Werkzeug',   value: t => t.name, strong: true },
            { label: 'Grundkörper', value: t => t.body },
            { label: 'Backenset',  value: t => t.jaw_set_name },
            { label: 'Verbindung', value: t => t.connection },
            { label: 'Standort',   value: t => t.location },
            { label: 'Status',     value: t => t.status, render: tool => buildStatusChip(tool.status_id) },
            { label: 'Lokring',    value: t => t.lokring },
        ],
        fields: [
            { key: 'name',        label: 'Name',        type: 'text',   required: true, maxLength: 100 },
            { key: 'body_id',     label: 'Grundkörper', type: 'select', lookup: 'bodies',    required: true },
            { key: 'jaw_set_id',  label: 'Backenset',   type: 'select', lookup: 'jaw_sets',  required: true },
            { key: 'status_id',   label: 'Status',      type: 'select', options: TOOL_STATUS_OPTIONS, required: true, editOnly: true },
            { key: 'location_id', label: 'Standort',    type: 'select', lookup: 'locations', required: true },
        ],
    },

    bauteile: {
        title: 'Bauteile',
        breadcrumb: 'Übersicht HiBe Materialien',
        description: 'Bauteilstammdaten verwalten.',
        createLabel: '+ Bauteil anlegen',
        entityName: 'Bauteil',
        endpoint: 'parts.php',
        listKey: 'parts',
        columns: [
            { label: 'ID',               value: p => `#${p.id}`, className: 'id-cell' },
            { label: 'Bauteil',          value: p => p.name, strong: true },
            { label: 'Backenset',        value: p => p.jaw_set_name },
            { label: 'HiBe-Nummer',      value: p => p.hibe_number },
            { label: 'Herstellernummer', value: p => p.manufacturer_number },
        ],
        fields: [
            { key: 'name',                label: 'Name',             type: 'text',   required: true, maxLength: 100 },
            { key: 'jaw_set_id',          label: 'Backenset',        type: 'select', lookup: 'jaw_sets', required: true },
            { key: 'hibe_number',         label: 'HiBe-Nummer',      type: 'text',   required: true, maxLength: 50 },
            { key: 'manufacturer_number', label: 'Herstellernummer', type: 'text',   required: true, maxLength: 50 },
        ],
    },

    standorte: {
        title: 'Standorte',
        breadcrumb: 'Übersicht Standorte',
        description: 'Standorte verwalten.',
        createLabel: '+ Standort anlegen',
        entityName: 'Standort',
        endpoint: 'locations.php',
        listKey: 'locations',
        columns: [
            { label: 'ID',         value: l => `#${l.id}`, className: 'id-cell' },
            { label: 'Standort',   value: l => l.name, strong: true },
            { label: 'Mindestbestand', value: l => l.min_stock !== null ? `${l.min_stock} Werkzeuge` : null },
        ],
        fields: [
            { key: 'name',          label: 'Name',                               type: 'text',   required: true, maxLength: 100 },
            { key: 'min_stock',     label: 'Mindestbestand (Anzahl Werkzeuge)',  type: 'number', min: 0, step: 1 },
        ],
    },

    koerper: {
        title: 'Grundkörper',
        breadcrumb: 'Übersicht Grundkörper',
        description: 'Grundkörper verwalten.',
        createLabel: '+ Grundkörper anlegen',
        entityName: 'Grundkörper',
        endpoint: 'bodies.php',
        listKey: 'bodies',
        columns: [
            { label: 'ID',        value: b => `#${b.id}`, className: 'id-cell' },
            { label: 'Name',      value: b => b.name, strong: true },
            { label: 'BoM-Datei', value: b => b.bom_file_name },
        ],
        fields: [
            { key: 'name',          label: 'Name',      type: 'text', required: true, maxLength: 100 },
            { key: 'bom_file_name', label: 'BoM-Datei', type: 'select', lookup: 'documents', valueType: 'string',
              action: { label: 'PDF hochladen', onClick: uploadBomDocument } },
        ],
    },

    backensets: {
        title: 'Backensets',
        breadcrumb: 'Übersicht Backensets',
        description: 'Backensets verwalten.',
        createLabel: '+ Backenset anlegen',
        entityName: 'Backenset',
        endpoint: 'jaw_sets.php',
        listKey: 'jaw_sets',
        columns: [
            { label: 'ID',         value: b => `#${b.id}`, className: 'id-cell' },
            { label: 'Name',       value: b => b.name, strong: true },
            { label: 'Verbindung', value: b => b.connection },
            { label: 'Lokring',    value: b => b.lokring },
        ],
        fields: [
            { key: 'name',          label: 'Name',       type: 'text',   required: true, maxLength: 100 },
            { key: 'connection_id', label: 'Verbindung', type: 'select', lookup: 'connections', required: true },
        ],
    },

    verbindungen: {
        title: 'Verbindungen',
        breadcrumb: 'Übersicht Verbindungen',
        description: 'Verbindungen verwalten.',
        createLabel: '+ Verbindung anlegen',
        entityName: 'Verbindung',
        endpoint: 'connections.php',
        listKey: 'connections',
        columns: [
            { label: 'ID',      value: c => `#${c.id}`, className: 'id-cell' },
            { label: 'Name',    value: c => c.name, strong: true },
            { label: 'Lokring', value: c => c.lokring },
        ],
        fields: [
            { key: 'name',    label: 'Name',    type: 'text', required: true, maxLength: 100 },
            { key: 'lokring', label: 'Lokring', type: 'text', maxLength: 100 },
        ],
    },

    ausfallursachen: {
        title: 'Ausfallursachen',
        breadcrumb: 'Übersicht Ausfallursachen',
        description: 'Auswahlliste für „Ausfall melden“. Benutzte Ursachen lassen sich nicht löschen, nur deaktivieren.',
        createLabel: '+ Ursache anlegen',
        entityName: 'Ausfallursache',
        endpoint: 'failure_causes.php',
        listKey: 'failure_causes',
        columns: [
            { label: 'ID',          value: c => `#${c.id}`, className: 'id-cell' },
            { label: 'Name',        value: c => c.name, strong: true },
            { label: 'Sortierung',  value: c => c.sort_order },
            { label: 'In Auswahl',  value: c => (c.active ? 'Ja' : 'Nein') },
        ],
        fields: [
            { key: 'name',       label: 'Name',       type: 'text',   required: true, maxLength: 100 },
            { key: 'sort_order', label: 'Sortierung', type: 'number', step: 1, placeholder: 'kleine Zahl = weiter oben' },
            { key: 'active',     label: 'In Auswahl', type: 'select', required: true,
              options: [{ value: 1, label: 'Ja' }, { value: 0, label: 'Nein (ausgeblendet)' }] },
        ],
    },
};


// =========================================
// Zustand
// =========================================

let currentSection = 'tools';
let currentItems = [];          // zuletzt geladene Datensätze der aktiven Sektion

let dialogMode = 'create';      // 'create' | 'edit'
let itemBeingEdited = null;     // Datensatz, der gerade bearbeitet wird
let itemPendingDelete = null;   // Datensatz, der gelöscht werden soll


// =========================================
// Initialisierung
// =========================================

function initPanel() {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', event => {
            event.preventDefault();
            switchSection(link.dataset.section);
        });
    });

    document.getElementById('search_input').addEventListener('input', renderTable);
    document.getElementById('entity_form').addEventListener('submit', event => {
        event.preventDefault();
        saveEntity();
    });

    refreshCounts();
    switchSection('tools');
}


// =========================================
// Zähler (Sidebar + Statistik)
// =========================================

function setText(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value ?? '–';
    }
}

async function refreshCounts() {
    const counters = [
        ['parts_counter',      'parts_quantity',      'parts_all_menue'],
        ['location_counter',   'location_quantity',   'locations_all_menue'],
        ['body_counter',       'body_quantity',       'bodies_all_menue'],
        ['jaw_set_counter',    'jaw_set_quantity',    'jaw_sets_all_menue'],
        ['connection_counter', 'connection_quantity', 'connections_all_menue'],
        ['failure_cause_counter', 'failure_cause_quantity', 'failure_causes_all_menue'],
    ];

    const jobs = counters.map(async ([service, field, elementId]) => {
        const data = await getService(service);
        setText(elementId, data[field]);
    });

    jobs.push((async () => {
        const data = await getService('tool_counter');
        setText('tools_all', data.tool_quantity);
        setText('tools_all_menue', data.tool_quantity);
        setText('tools_active', data.tool_active);
        setText('tools_maintenance', data.tool_maintenance);
    })());

    const results = await Promise.allSettled(jobs);
    results
        .filter(result => result.status === 'rejected')
        .forEach(result => console.error('Fehler beim Laden der Zähler:', result.reason));
}


// =========================================
// Sektion wechseln + Tabelle
// =========================================

function switchSection(sectionKey) {
    const config = sections[sectionKey];

    if (!config) {
        console.error(`Unbekannte Sektion: ${sectionKey}`);
        return;
    }

    currentSection = sectionKey;
    currentItems = [];

    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.toggle('active', link.dataset.section === sectionKey);
    });

    setText('page_breadcrumb', config.breadcrumb);
    setText('page_title', config.title);
    setText('page_description', config.description);
    setText('create_button', config.createLabel);
    document.getElementById('stats_block').classList.toggle('hidden', !config.showStats);
    document.getElementById('search_input').value = '';

    const headRow = document.getElementById('table_head_row');
    headRow.replaceChildren(
        ...[...config.columns.map(col => col.label), 'Aktionen'].map(label => {
            const th = document.createElement('th');
            th.textContent = label;
            return th;
        })
    );

    loadCurrentSection();
}

async function loadCurrentSection() {
    const sectionKey = currentSection;
    const config = sections[sectionKey];

    setTableMessage('Lädt…');

    try {
        const data = await apiRequest(config.endpoint);

        // Falls inzwischen eine andere Sektion gewählt wurde: Ergebnis verwerfen
        if (sectionKey !== currentSection) {
            return;
        }

        currentItems = data[config.listKey] ?? [];
        renderTable();

    } catch (error) {
        console.error(`Fehler beim Laden (${config.title}):`, error);
        showEmptyState('Daten konnten nicht geladen werden', error.message);
    }
}

function reloadCurrentSection() {
    loadCurrentSection();
    refreshCounts();
}

function getVisibleItems() {
    const config = sections[currentSection];
    const search = document.getElementById('search_input').value.trim().toLowerCase();

    if (!search) {
        return currentItems;
    }

    return currentItems.filter(item =>
        config.columns.some(col => String(col.value(item) ?? '').toLowerCase().includes(search))
    );
}

function renderTable() {
    const config = sections[currentSection];
    const tbody = document.getElementById('admin-table');
    const items = getVisibleItems();

    document.getElementById('data_table').classList.remove('hidden');
    document.getElementById('empty_state').classList.add('hidden');

    if (currentItems.length === 0) {
        showEmptyState(`Noch keine Einträge`, `Lege über „${config.createLabel.replace('+ ', '')}“ den ersten Eintrag an.`);
        return;
    }

    if (items.length === 0) {
        setTableMessage('Keine Treffer für diese Suche.');
        return;
    }

    tbody.replaceChildren(...items.map(item => buildRow(config, item)));
    setText('table_count', `${items.length} von ${currentItems.length} Einträgen`);
}

function buildRow(config, item) {
    const row = document.createElement('tr');

    config.columns.forEach(col => {
        const cell = document.createElement('td');

        if (col.className) {
            cell.classList.add(col.className);
        }

        if (col.render) {
            cell.appendChild(col.render(item));
        } else if (col.strong) {
            const strong = document.createElement('strong');
            strong.textContent = col.value(item) ?? '–';
            cell.appendChild(strong);
        } else {
            cell.textContent = col.value(item) ?? '–';
        }

        row.appendChild(cell);
    });

    const actionCell = document.createElement('td');
    actionCell.classList.add('action-cell');
    actionCell.append(
        makeButton('Bearbeiten', ['button_neutral', 'button-small'], () => openEditDialog(item)),
        makeButton('Löschen',    ['button_negativ', 'button-small'], () => requestDelete(item))
    );
    row.appendChild(actionCell);

    return row;
}

function makeButton(text, classes, onClick) {
    const button = document.createElement('button');
    button.type = 'button';
    button.classList.add(...classes);
    button.textContent = text;
    button.addEventListener('click', onClick);
    return button;
}


function setTableMessage(text) {
    const config = sections[currentSection];
    const cell = document.createElement('td');
    cell.colSpan = config.columns.length + 1;
    cell.textContent = text;

    const row = document.createElement('tr');
    row.appendChild(cell);

    document.getElementById('admin-table').replaceChildren(row);
    document.getElementById('data_table').classList.remove('hidden');
    document.getElementById('empty_state').classList.add('hidden');
    setText('table_count', '');
}

function showEmptyState(title, text) {
    document.getElementById('data_table').classList.add('hidden');
    setText('empty_state_title', title);
    setText('empty_state_text', text);
    setText('table_count', '');
    document.getElementById('empty_state').classList.remove('hidden');
}


// =========================================
// Dialog: Anlegen + Bearbeiten (für alle Sektionen)
// =========================================

function onCreateClick() {
    openEntityDialog('create', null);
}

function openEditDialog(item) {
    openEntityDialog('edit', item);
}

async function openEntityDialog(mode, item) {
    const config = sections[currentSection];

    dialogMode = mode;
    itemBeingEdited = item;

    setText('entity_dialog_eyebrow', `${config.title} verwalten`);
    setText('entity_dialog_title', mode === 'edit'
        ? `${config.entityName} bearbeiten`
        : `${config.entityName} anlegen`);
    hideDialogError();

    const fields = config.fields.filter(field => mode === 'edit' || !field.editOnly);
    const form = document.getElementById('entity_form_fields');
    form.replaceChildren(...fields.map(field => buildFormRow(field)));

    setSaveButtonBusy(true);
    document.getElementById('entity_dialog').showModal();

    try {
        await fillLookupSelects(fields);
        fields.forEach(field => setFieldValue(field, item ? item[field.key] : null));

        if (currentSection === 'tools' && mode === 'edit') {
            await applyScrapPermission(document.getElementById('field_status_id'), item.status_id);
        }

        setSaveButtonBusy(false);
        form.querySelector('input, select')?.focus();
    } catch (error) {
        showDialogError(`Auswahllisten konnten nicht geladen werden: ${error.message}`);
    }
}

function buildFormRow(field) {
    const row = document.createElement('div');
    row.classList.add('form-row');

    const label = document.createElement('label');
    label.htmlFor = `field_${field.key}`;
    label.textContent = field.required ? `${field.label} *` : field.label;

    let input;

    if (field.type === 'select') {
        input = document.createElement('select');

        (field.options ?? []).forEach(option => {
            input.appendChild(new Option(option.label, option.value));
        });
    } else {
        input = document.createElement('input');
        input.type = field.type;

        if (field.maxLength)  input.maxLength = field.maxLength;
        if (field.placeholder) input.placeholder = field.placeholder;
        if (field.min !== undefined) input.min = field.min;
        if (field.max !== undefined) input.max = field.max;
        if (field.step !== undefined) input.step = field.step;
    }

    input.id = `field_${field.key}`;
    input.name = field.key;
    input.required = Boolean(field.required);

    if (field.action) {
        const control = document.createElement('div');
        control.classList.add('form-row-control');
        control.append(input, makeButton(field.action.label, ['button_neutral', 'button-small'], field.action.onClick));
        row.append(label, control);
    } else {
        row.append(label, input);
    }

    return row;
}

function uploadBomDocument() {
    const field = sections.koerper.fields.find(entry => entry.key === 'bom_file_name');

    openDocumentUploadDialog(async name => {
        try {
            await fillLookupSelects([field]);
            setFieldValue(field, name);
        } catch (error) {
            showDialogError(`Auswahlliste konnte nicht aktualisiert werden: ${error.message}`);
        }
    });
}

async function fillLookupSelects(fields) {
    const lookupFields = fields.filter(field => field.lookup);

    // Immer frisch laden, damit gerade angelegte Einträge auswählbar sind
    await Promise.all(lookupFields.map(async field => {
        const lookup = lookups[field.lookup];
        const data = await apiRequest(lookup.endpoint);
        const select = document.getElementById(`field_${field.key}`);

        // Pflichtfelder: Platzhalter nicht wählbar, sonst bedeutet er „kein Wert“
        const placeholder = new Option(field.required ? '– bitte wählen –' : '– keine –', '');
        placeholder.disabled = Boolean(field.required);

        select.replaceChildren(
            placeholder,
            ...data[lookup.key].map(entry => new Option(lookup.label(entry), lookup.value ? lookup.value(entry) : entry.id))
        );
    }));
}

function setFieldValue(field, value) {
    const input = document.getElementById(`field_${field.key}`);

    // Alte Einträge ohne Datei auf dem Server anzeigen, damit sie beim Speichern nicht verloren gehen
    if (field.valueType === 'string' && value && ![...input.options].some(option => option.value === value)) {
        input.add(new Option(`${value} (nicht auf dem Server)`, value));
    }

    input.value = value ?? '';

    // Bei Auswahlfeldern ohne Wert den Platzhalter zeigen
    if (field.type === 'select' && input.value === '' && input.options.length > 0) {
        input.selectedIndex = 0;
    }
}

function readFieldValue(field) {
    const raw = document.getElementById(`field_${field.key}`).value.trim();

    if (raw === '') {
        return null;
    }

    if (field.valueType === 'string') {
        return raw;
    }

    return field.type === 'select' || field.type === 'number' ? Number(raw) : raw;
}

function collectFormData() {
    const config = sections[currentSection];
    const fields = config.fields.filter(field => dialogMode === 'edit' || !field.editOnly);
    const data = {};

    for (const field of fields) {
        const value = readFieldValue(field);

        if (field.required && value === null) {
            throw new Error(`Bitte „${field.label}“ ausfüllen.`);
        }

        // Beim Bearbeiten nur geänderte Felder schicken
        if (dialogMode === 'edit' && (itemBeingEdited[field.key] ?? null) === value) {
            continue;
        }

        data[field.key] = value;
    }

    return data;
}

async function saveEntity() {
    const config = sections[currentSection];
    hideDialogError();

    let data;
    try {
        data = collectFormData();
    } catch (error) {
        showDialogError(error.message);
        return;
    }

    if (dialogMode === 'edit' && Object.keys(data).length === 0) {
        closeEntityDialog();   // nichts geändert
        return;
    }

    const save = () => dialogMode === 'edit'
        ? apiRequest(config.endpoint, { method: 'PATCH', params: { id: itemBeingEdited.id }, body: data })
        : apiRequest(config.endpoint, { method: 'POST', body: data });

    // Werkzeug geht neu in die Wartung → zuerst Ausfall melden
    if (currentSection === 'tools' && dialogMode === 'edit' && 'status_id' in data
        && isEnteringMaintenance(itemBeingEdited.status_id, data.status_id)) {

        // Den Status setzt der Server zusammen mit dem Ausfall, hier nur die übrigen Änderungen
        const { status_id, ...otherChanges } = data;

        const saved = await reportFailure(itemBeingEdited, {
            hint: 'Das Werkzeug wird auf „Wartung“ gesetzt. Bitte den Grund für den Ausfall angeben.',
            beforeSave: Object.keys(otherChanges).length === 0 ? null : () => apiRequest(config.endpoint, {
                method: 'PATCH',
                params: { id: itemBeingEdited.id },
                body: otherChanges,
            }),
        });

        if (saved) {
            closeEntityDialog();
            reloadCurrentSection();
        }
        return; // abgebrochen → Bearbeiten-Dialog bleibt offen
    }

    // Werkzeug verlässt die Wartung → zuerst Reparatur erfassen
    if (currentSection === 'tools' && dialogMode === 'edit' && 'status_id' in data
        && isLeavingMaintenance(itemBeingEdited.status_id, data.status_id)) {

        const saved = await reportRepair(itemBeingEdited, {
            hint: `Das Werkzeug verlässt die Wartung und wird auf „${getToolStatus(data.status_id).label}“ gesetzt. Bitte die Reparatur erfassen.`,
            afterSave: save,
        });

        if (saved) {
            closeEntityDialog();
            reloadCurrentSection();
        }
        return;
    }

    setSaveButtonBusy(true);

    try {
        await save();
        closeEntityDialog();
        reloadCurrentSection();

    } catch (error) {
        showDialogError(error.message);
    } finally {
        setSaveButtonBusy(false);
    }
}

function closeEntityDialog() {
    document.getElementById('entity_dialog').close();
    itemBeingEdited = null;
}

function setSaveButtonBusy(busy) {
    const button = document.getElementById('entity_save_button');
    button.disabled = busy;
    button.textContent = busy ? 'Bitte warten…' : 'Speichern';
}

function showDialogError(message) {
    const element = document.getElementById('entity_dialog_error');
    element.textContent = message;
    element.classList.remove('hidden');
}

function hideDialogError() {
    document.getElementById('entity_dialog_error').classList.add('hidden');
}


// =========================================
// Löschen (mit Bestätigung)
// =========================================

function requestDelete(item) {
    const config = sections[currentSection];
    itemPendingDelete = item;

    setText('delete_dialog_title', `${config.entityName} löschen?`);
    setText('delete_dialog_text', `„${item.name}“ wird endgültig gelöscht.`);
    document.getElementById('delete_dialog_error').classList.add('hidden');
    document.getElementById('delete_confirm_button').disabled = false;

    document.getElementById('delete_dialog').showModal();
}

function cancelDelete() {
    itemPendingDelete = null;
    document.getElementById('delete_dialog').close();
}

async function confirmDelete() {
    if (itemPendingDelete === null) {
        return;
    }

    const config = sections[currentSection];
    const button = document.getElementById('delete_confirm_button');
    button.disabled = true;

    try {
        await apiRequest(config.endpoint, { method: 'DELETE', params: { id: itemPendingDelete.id } });
        cancelDelete();
        reloadCurrentSection();

    } catch (error) {
        // z. B. "Wird noch von 3 Werkzeug(en) verwendet" – im Dialog anzeigen
        const errorElement = document.getElementById('delete_dialog_error');
        errorElement.textContent = error.message;
        errorElement.classList.remove('hidden');
        button.disabled = false;
    }
}


// =========================================
// Export (CSV, öffnet direkt in Excel)
// =========================================

function exportCsv() {
    const config = sections[currentSection];
    const items = getVisibleItems();

    const escape = value => `"${String(value ?? '').replace(/"/g, '""')}"`;
    const header = config.columns.map(col => escape(col.label)).join(';');
    const rows = items.map(item => config.columns.map(col => escape(col.value(item))).join(';'));

    // BOM, damit Excel Umlaute richtig erkennt
    const csv = '﻿' + [header, ...rows].join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });

    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `${config.title.toLowerCase()}_${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    setTimeout(() => URL.revokeObjectURL(link.href), 1000);
}


initPanel();