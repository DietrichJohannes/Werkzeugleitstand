// =========================================
// Startseite: Kennzahlen + Standort-Karten
// =========================================

// Benötigt: /assets/js/api.js, /assets/js/location-status.js

const REFRESH_SECONDS = 60;   // Leitstand aktualisiert sich selbst

const locationGrid = document.getElementById('location_grid');

// ---------- Kennzahlen ----------

async function loadKpis() {
    const data = await getService('tool_counter');

    // Verschrottete Werkzeuge zählen nicht zum Bestand
    const toolQuantity = data.tool_quantity - data.tool_scrapped;

    setText('kpi_total', toolQuantity);
    setText('kpi_ready', data.tool_ready);
    setText('kpi_active', data.tool_active);
    setText('kpi_maintenance', data.tool_maintenance);
}

// ---------- Standorte ----------
// Bewertung (normal / warning / critical) kommt aus /assets/js/location-status.js

async function loadLocations() {
    const locations = await getService('tool_counter_by_location');

    if (locations.length === 0) {
        showMessage('Es wurden noch keine Standorte angelegt!');
        setText('location_summary', '');
        return;
    }

    locationGrid.replaceChildren(...locations.map(buildLocationCard));

    const levels = locations.map(location => evaluateLocation(location).level);
    const critical = levels.filter(level => level === 'critical').length;
    const warning = levels.filter(level => level === 'warning').length;

    const parts = [];
    if (critical > 0) parts.push(`${critical} kritisch`);
    if (warning > 0) parts.push(`${warning} mit Warnung`);

    setText('location_summary', parts.length > 0
        ? `${parts.join(', ')} von ${locations.length}`
        : `${locations.length} Standorte, alle im grünen Bereich`);
}

function buildLocationCard(location) {
    const status = evaluateLocation(location);

    const card = document.createElement('a');
    card.className = `location-card is-${status.level}`;
    card.href = `/pages/line/?id=${encodeURIComponent(location.location_id)}`;

    // Kopf: Name + ggf. Hinweis
    const head = document.createElement('div');
    head.className = 'location-head';

    const title = document.createElement('h3');
    title.textContent = location.location;
    head.appendChild(title);

    if (status.level !== 'normal') {
        const badge = document.createElement('span');
        badge.className = 'location-badge';
        badge.textContent = status.level === 'critical' ? 'Kritisch' : 'Warnung';
        head.appendChild(badge);
    }

    // Zahlen
    const numbers = document.createElement('div');
    numbers.className = 'location-numbers';
    numbers.append(
        buildNumber(location.tools_all, 'Werkzeuge'),
        buildNumber(location.tools_defect, 'in Wartung')
    );

    card.append(head, numbers);

    if (status.reasons.length > 0) {
        const reasons = document.createElement('p');
        reasons.className = 'location-reasons';
        reasons.textContent = status.reasons.join(' · ');
        card.append(reasons);
    }

    card.append(buildDefectBar(location));

    return card;
}

function buildNumber(value, label) {
    const wrapper = document.createElement('div');
    const strong = document.createElement('strong');
    strong.textContent = value;
    wrapper.append(strong, label);
    return wrapper;
}

function buildDefectBar(location) {
    const container = document.createElement('div');

    const bar = document.createElement('div');
    bar.className = 'defect-bar';

    const fill = document.createElement('div');
    fill.className = 'defect-bar-fill';
    fill.style.width = `${Math.min(location.defect_percent, 100)}%`;
    bar.appendChild(fill);

    const caption = document.createElement('div');
    caption.className = 'defect-caption';

    const quota = document.createElement('span');
    quota.textContent = `In Wartung ${formatPercent(location.defect_percent)}`;

    const minimum = document.createElement('span');
    minimum.textContent = location.min_stock !== null
        ? `Mindestbestand ${location.min_stock}`
        : 'kein Mindestbestand';

    caption.append(quota, minimum);
    container.append(bar, caption);

    return container;
}

// ---------- Hilfsfunktionen ----------
// formatPercent() kommt aus /assets/js/location-status.js

function setText(id, value) {
    document.getElementById(id).textContent = value ?? '–';
}

function showMessage(text) {
    const p = document.createElement('p');
    p.className = 'home-message';
    p.textContent = text;
    locationGrid.replaceChildren(p);
}

// ---------- Start + automatische Aktualisierung ----------

async function refresh() {
    const results = await Promise.allSettled([loadKpis(), loadLocations()]);
    const failed = results.find(result => result.status === 'rejected');

    if (failed) {
        console.error('Fehler beim Laden der Startseite:', failed.reason);
        setText('last_update', `Fehler: ${failed.reason.message}`);

        // Standorte nur überschreiben, wenn noch nichts angezeigt wird
        if (!locationGrid.querySelector('.location-card')) {
            showMessage(`Daten konnten nicht geladen werden: ${failed.reason.message}`);
        }
        return;
    }

    const time = new Date().toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
    setText('last_update', `Stand ${time} Uhr`);
}

refresh();
setInterval(refresh, REFRESH_SECONDS * 1000);

// Zurück-Button: Der Browser zeigt die Seite dann aus seinem Zwischenspeicher
// (bfcache), ohne die Scripte neu zu starten. "pageshow" mit persisted = true
// erkennt genau diesen Fall -> Daten sofort neu laden.
window.addEventListener('pageshow', event => {
    if (event.persisted) {
        refresh();
    }
});

// Tab war im Hintergrund (z. B. anderer Tab offen) und wird wieder sichtbar
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        refresh();
    }
});