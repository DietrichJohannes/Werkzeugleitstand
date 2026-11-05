// =========================================
// Management-Ansicht
// =========================================
// Status-Bewertung und Grenzwerte kommen aus /assets/js/location-status.js
// (dieselben Regeln wie auf der Startseite).

// Benötigt: /assets/js/api.js, /assets/js/location-status.js, /assets/js/charts.js

const REFRESH_SECONDS = 60;

// Gewählter Zeitraum der Auswertung (wird im Browser gemerkt)
const PERIOD_STORAGE_KEY = 'lokring.management.days';
let selectedDays = [30, 90, 365].includes(Number(localStorage.getItem(PERIOD_STORAGE_KEY)))
    ? Number(localStorage.getItem(PERIOD_STORAGE_KEY))
    : 90;

// ---------- Kennzahlen ----------

function renderKpis(counts, locations) {
    const total = counts.tool_quantity;
    const active = counts.tool_active;
    const maintenance = counts.tool_maintenance;
    const ready = total - active - maintenance;

    const share = value => (total > 0 ? (value / total) * 100 : 0);

    setText('tools_all', total);
    setText('tools_active', active);
    setText('tools_active_percent', `${formatPercent(share(active))} vom Bestand`);
    setText('tools_ready', ready);
    setText('tools_ready_percent', `${formatPercent(share(ready))} vom Bestand`);
    setText('tools_maintenance', maintenance);
    setText('maintenance_percent', `${formatPercent(share(maintenance))} vom Bestand`);

    setLevel(document.getElementById('maintenance_card'), evaluateDefectPercent(share(maintenance)));

    // Standorte mit Handlungsbedarf
    const critical = locations.filter(location => evaluateLocation(location).level === 'critical').length;
    const warning = locations.filter(location => evaluateLocation(location).level === 'warning').length;

    setText('locations_critical', critical);
    setText('locations_critical_text', warning > 0
        ? `von ${locations.length} · ${warning} weitere mit Warnung`
        : `von ${locations.length} Standorten`);

    setLevel(document.getElementById('critical_card'),
        critical > 0 ? 'critical' : warning > 0 ? 'warning' : 'normal');
}

function setLevel(element, level) {
    element.classList.remove('is-normal', 'is-warning', 'is-critical');
    element.classList.add(`is-${level}`);
}

// ---------- Standorte ----------

function renderLocations(locations) {
    const container = document.getElementById('location_stats');

    if (locations.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'mgmt-empty';
        empty.textContent = 'Noch keine Standorte angelegt.';
        container.replaceChildren(empty);
        return;
    }

    container.replaceChildren(...sortLocationsBySeverity(locations).map(buildLocationRow));
}

function buildLocationRow(location) {
    const status = evaluateLocation(location);

    const row = document.createElement('a');
    row.className = `location-row is-${status.level}`;
    row.href = `/pages/line/?id=${encodeURIComponent(location.location_id)}`;
    row.title = 'Werkzeuge dieses Standorts anzeigen';

    // Linke Seite: Status, Name, Hinweise
    const info = document.createElement('div');
    info.className = 'location-info';

    const name = document.createElement('div');
    name.className = 'location-name';

    const dot = document.createElement('span');
    dot.className = 'status-dot';

    const nameText = document.createElement('span');
    nameText.textContent = location.location;

    name.append(dot, nameText);
    info.appendChild(name);

    status.reasons.forEach(reason => {
        const chip = document.createElement('span');
        chip.className = 'reason-chip';
        chip.textContent = reason;
        info.appendChild(chip);
    });

    // Rechte Seite: Zahlen
    const values = document.createElement('div');
    values.className = 'location-values';

    const defect = document.createElement('span');
    defect.innerHTML = '<strong></strong> von <strong></strong> in Wartung';
    const [defectCount, totalCount] = defect.querySelectorAll('strong');
    defectCount.textContent = location.tools_defect;
    totalCount.textContent = location.tools_all;

    const minimum = document.createElement('span');
    minimum.className = 'location-minimum';
    minimum.textContent = location.min_stock !== null
        ? `Mindestbestand ${location.min_stock}`
        : 'kein Mindestbestand';

    const percent = document.createElement('span');
    percent.className = 'location-percent';
    percent.textContent = formatPercent(location.defect_percent);

    values.append(defect, minimum, percent);

    const head = document.createElement('div');
    head.className = 'location-head';
    head.append(info, values);

    row.append(head, buildBar(location.defect_percent));
    return row;
}

/*
 * Balken mit Markierungen bei den Grenzwerten (25 % / 50 %).
 */
function buildBar(percent) {
    const bar = document.createElement('div');
    bar.className = 'location-bar';

    const fill = document.createElement('div');
    fill.className = 'location-bar-fill';
    fill.style.width = `${Math.min(percent, 100)}%`;
    bar.appendChild(fill);

    [STATUS_THRESHOLDS.defectWarning, STATUS_THRESHOLDS.defectCritical].forEach(threshold => {
        const tick = document.createElement('span');
        tick.className = 'location-bar-tick';
        tick.style.left = `${threshold}%`;
        bar.appendChild(tick);
    });

    return bar;
}


 // ---------- Reparaturen ----------

function renderRepairs(repairs) {
    const tableBody = document.getElementById('repairs_table_body');

    if (repairs.length === 0) {
        setTableMessage(tableBody, 5, 'Keine Reparaturen vorhanden.');
        return;
    }

    tableBody.replaceChildren(...repairs.map(repair => buildRow([
        formatDateTime(repair.created_at),
        repair.tool_name,
        repair.failure_id
            ? `${repair.cause_name ?? 'ohne Ursache'} (${formatDateTime(repair.failure_created_at)})`
            : '–',
        repair.part_names || '–',
        repair.description || '–',
    ])));
}

// ---------- Ausfälle ----------

function renderFailures(failures) {
    const tableBody = document.getElementById('failures_table_body');

    if (failures.length === 0) {
        setTableMessage(tableBody, 6, 'Keine Ausfälle vorhanden.');
        return;
    }

    tableBody.replaceChildren(...failures.map(failure => {
        const row = buildRow([
            formatDateTime(failure.created_at),
            failure.tool_name,
            failure.cause_name ?? 'nicht kategorisiert',
            failure.part_name || '–',
            failure.description || '–',
        ]);

        const status = document.createElement('span');
        status.className = failure.repaired ? 'chip_bereit' : 'chip_wartung';
        status.textContent = failure.repaired ? 'repariert' : 'offen';
        row.insertCell().appendChild(status);

        return row;
    }));
}

function buildRow(values) {
    const row = document.createElement('tr');
    values.forEach(value => {
        row.insertCell().textContent = value ?? '–';
    });
    return row;
}

function setTableMessage(tableBody, columns, text) {
    const cell = document.createElement('td');
    cell.colSpan = columns;
    cell.textContent = text;

    const row = document.createElement('tr');
    row.appendChild(cell);
    tableBody.replaceChildren(row);
}

// "2026-10-30 14:05:00" → "30.10.2026 14:05"
function formatDateTime(value) {
    if (!value) return '–';
    const [date, time = ''] = value.split(' ');
    const [year, month, day] = date.split('-');
    return `${day}.${month}.${year} ${time.slice(0, 5)}`.trim();
}

// ---------- Hilfsfunktionen ----------

function setText(id, value) {
    document.getElementById(id).textContent = value ?? '–';
}

function showError(message) {
    const box = document.getElementById('error_box');
    box.textContent = message;
    box.classList.toggle('hidden', !message);
}

function fillLegend() {
    document.querySelectorAll('[data-threshold="warning"]').forEach(el => {
        el.textContent = formatPercent(STATUS_THRESHOLDS.defectWarning);
    });
    document.querySelectorAll('[data-threshold="critical"]').forEach(el => {
        el.textContent = formatPercent(STATUS_THRESHOLDS.defectCritical);
    });
}

// ---------- Laden + automatische Aktualisierung ----------


async function refresh() {
    const analyticsSection = document.getElementById('analytics');
    analyticsSection.classList.add('is-loading');

    try {
        const [counts, locations, repairs, failures, analytics] = await Promise.all([
            getService('tool_counter'),
            getService('tool_counter_by_location'),
            apiRequest('repairs.php').then(data => data.repairs),
            apiRequest('failures.php').then(data => data.failures),
            apiRequest('analytics.php', { params: { days: selectedDays } }).then(data => data.data),
        ]);

        renderKpis(counts, locations);
        renderLocations(locations);
        renderAnalytics(analytics);

        // Tabellen zeigen denselben Zeitraum wie die Auswertung
        const inPeriod = item => item.created_at >= analytics.period.from;
        renderRepairs(repairs.filter(inPeriod));
        renderFailures(failures.filter(inPeriod));

        showError('');

        const time = new Date().toLocaleTimeString('de-DE', {
            hour: '2-digit',
            minute: '2-digit'
        });

        setText('last_update', `Stand ${time} Uhr`);

    } catch (error) {
        console.error('Fehler beim Laden der Management-Ansicht:', error);
        showError(`Daten konnten nicht geladen werden: ${error.message}`);
        setText('last_update', 'Fehler beim Laden');
    } finally {
        analyticsSection.classList.remove('is-loading');
    }
}

// ---------- Auswertung ----------

const PERIOD_TEXT = { 30: 'letzte 30 Tage', 90: 'letzte 90 Tage', 365: 'letzte 12 Monate' };

function renderAnalytics(data) {
    const { period, summary } = data;

    setText('analytics_period_text', `${PERIOD_TEXT[period.days]} – seit ${formatDate(period.from)}`);

    // Kennzahlen
    setText('kpi_failures', summary.failures.toLocaleString('de-DE'));
    setText('kpi_repairs', summary.repairs.toLocaleString('de-DE'));
    setText('kpi_downtime', summary.avg_downtime_hours !== null ? formatDuration(summary.avg_downtime_hours) : '–');
    setText('kpi_open_failures', summary.open_failures.toLocaleString('de-DE'));
    setText('kpi_open_failures_text', summary.open_failures > 0
        ? 'aktuell noch in Wartung'
        : 'aktuell keine – alles abgeschlossen');
    setLevel(document.getElementById('open_failures_card'), summary.open_failures > 0 ? 'warning' : 'normal');

    // Verlauf
    const isMonth = period.bucket === 'month';
    setText('timeline_title', `Ausfälle und Reparaturen je ${isMonth ? 'Monat' : 'Woche'}`);
    renderColumnChart(document.getElementById('chart_timeline'), data.timeline.map(bucket => ({
        label: isMonth ? formatMonth(bucket.start) : formatDate(bucket.start).slice(0, 6),
        title: isMonth ? formatMonth(bucket.start, true) : `Woche ab ${formatDate(bucket.start)}`,
        values: { failures: bucket.failures, repairs: bucket.repairs },
    })), {
        series: [
            { key: 'failures', label: 'Ausfälle',    className: 'series-1' },
            { key: 'repairs',  label: 'Reparaturen', className: 'series-2' },
        ],
        periodHeader: isMonth ? 'Monat' : 'Woche',
        ariaLabel: `Ausfälle und Reparaturen je ${isMonth ? 'Monat' : 'Woche'}, ${PERIOD_TEXT[period.days]}`,
    });

    // Ranglisten
    renderBarList(document.getElementById('chart_causes'),
        data.by_cause.map(c => ({ label: c.name, value: c.count })),
        { valueLabel: 'Ausfälle', labelHeader: 'Ursache', emptyText: 'Keine Ausfälle im gewählten Zeitraum.' });

    renderBarList(document.getElementById('chart_parts'),
        data.by_part.map(p => ({ label: p.name, sublabel: `${p.jaw_set} · HiBe ${p.hibe_number}`, value: p.count })),
        { valueLabel: 'Ausfälle', labelHeader: 'Bauteil', emptyText: 'Bei keinem Ausfall im Zeitraum wurde ein Bauteil angegeben.' });

    renderBarList(document.getElementById('chart_tools'),
        data.by_tool.map(t => ({ label: t.name, sublabel: t.location, value: t.count })),
        { valueLabel: 'Ausfälle', labelHeader: 'Werkzeug', emptyText: 'Keine Ausfälle im gewählten Zeitraum.' });

    // eine Einheit für das ganze Diagramm (nach dem größten Wert)
    const maxDowntime = Math.max(0, ...data.downtime_by_location.map(l => l.avg_hours));
    const downtimeFormat = hours => formatDuration(hours, maxDowntime >= 48 ? 'days' : 'hours');

    renderBarList(document.getElementById('chart_downtime'),
        data.downtime_by_location.map(l => ({
            label: l.location,
            sublabel: `${l.repairs} ${l.repairs === 1 ? 'Reparatur' : 'Reparaturen'}`,
            value: l.avg_hours,
        })),
        { valueLabel: 'Ø Ausfallzeit', labelHeader: 'Standort', format: downtimeFormat,
          emptyText: 'Keine abgeschlossenen Ausfälle im gewählten Zeitraum.' });
}

// 5,5 → "5,5 Std."   |   50 → "2,1 Tage"
// unit: 'hours' | 'days' erzwingt eine Einheit, sonst automatisch
function formatDuration(hours, unit = hours < 48 ? 'hours' : 'days') {
    if (unit === 'hours') {
        return `${hours.toLocaleString('de-DE', { maximumFractionDigits: 1 })} Std.`;
    }
    return `${(hours / 24).toLocaleString('de-DE', { maximumFractionDigits: 1 })} Tage`;
}

// "2026-10-26" → "26.10.2026"
function formatDate(value) {
    const [year, month, day] = value.slice(0, 10).split('-');
    return `${day}.${month}.${year}`;
}

// "2026-10-01" → "Okt 26" bzw. "Oktober 2026"
function formatMonth(value, long = false) {
    const date = new Date(`${value.slice(0, 10)}T00:00:00`);
    return long
        ? date.toLocaleDateString('de-DE', { month: 'long', year: 'numeric' })
        : date.toLocaleDateString('de-DE', { month: 'short', year: '2-digit' });
}

function initPeriodFilter() {
    const buttons = document.querySelectorAll('.period-filter button');

    const mark = () => buttons.forEach(button => {
        button.setAttribute('aria-pressed', String(Number(button.dataset.days) === selectedDays));
    });

    buttons.forEach(button => button.addEventListener('click', () => {
        selectedDays = Number(button.dataset.days);
        localStorage.setItem(PERIOD_STORAGE_KEY, String(selectedDays));
        mark();
        refresh();
    }));

    mark();
}

fillLegend();
initPeriodFilter();
refresh();
setInterval(refresh, REFRESH_SECONDS * 1000);

// Zurück-Button / Tab-Wechsel: sofort frische Daten
window.addEventListener('pageshow', event => {
    if (event.persisted) refresh();
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') refresh();
});