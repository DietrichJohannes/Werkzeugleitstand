// =========================================
// Zentrale API-Helfer
// =========================================
// Auf JEDER Seite, die Daten lädt, VOR den Seiten-Skripten einbinden:
//   <script src="/assets/js/api.js"></script>
//
// Alle Aufrufe werfen bei Fehlern einen Error mit der Meldung der API
// (z. B. "Keine Berechtigung" oder "Wird noch von 3 Werkzeug(en) verwendet").

const API_BASE = '/api/';

/*
 * Baut die URL zu einem Endpunkt.
 *   apiUrl('tools.php')                    → /api/tools.php
 *   apiUrl('tools.php', { location_id: 2 }) → /api/tools.php?location_id=2
 */
function apiUrl(path, params = null) {
    const url = API_BASE + path;

    if (!params) {
        return url;
    }

    return `${url}?${new URLSearchParams(params)}`;
}

/*
 * Einheitlicher API-Aufruf.
 *   await apiRequest('tools.php')                                       // GET
 *   await apiRequest('tools.php', { params: { id: 5 } })                // GET ?id=5
 *   await apiRequest('tools.php', { method: 'POST', body: {...} })      // anlegen
 *   await apiRequest('tools.php', { method: 'PATCH', params: { id: 5 }, body: {...} })
 *   await apiRequest('tools.php', { method: 'DELETE', params: { id: 5 } })
 *
 * Gibt die komplette JSON-Antwort zurück ({ success, message, ... }).
 */
async function apiRequest(path, { method = 'GET', params = null, body = null } = {}) {
    const options = { method, credentials: 'same-origin', headers: {} };

    if (body !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    const response = await fetch(apiUrl(path, params), options);

    let data;
    try {
        data = await response.json();
    } catch {
        throw new Error(`Ungültige Antwort vom Server (HTTP ${response.status})`);
    }

    if (!response.ok || !data.success) {
        throw new Error(data.message || `HTTP-Fehler ${response.status}`);
    }

    return data;
}

/*
 * Kennzahlen aus api/service.php, z. B. getService('tool_counter').
 * Gibt direkt den Inhalt von "data" zurück.
 */
async function getService(service) {
    const data = await apiRequest('service.php', { params: { service } });
    return data.data;
}
