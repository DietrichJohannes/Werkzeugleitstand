// =========================================
// Gemeinsame Status-Bewertung für Standorte
// =========================================
// Wird von der Startseite und der Management-Ansicht genutzt,
// damit beide Seiten immer denselben Status anzeigen.
// Grenzwerte nur HIER ändern.

const STATUS_THRESHOLDS = {
    defectWarning: 25,    // ab 25 % in Wartung: Warnung (gelb)
    defectCritical: 50,   // ab 50 % in Wartung: kritisch (rot)
};

const STATUS_ORDER = { critical: 0, warning: 1, normal: 2 };

/*
 * Bewertet eine Defekt-/Wartungsquote in Prozent.
 * Rückgabe: 'normal' | 'warning' | 'critical'
 */
function evaluateDefectPercent(percent) {
    if (percent >= STATUS_THRESHOLDS.defectCritical) return 'critical';
    if (percent >= STATUS_THRESHOLDS.defectWarning) return 'warning';
    return 'normal';
}

/*
 * Bewertet einen Standort aus service.php?service=tool_counter_by_location.
 * min_stock = Mindestbestand: Hat der Standort so viele Werkzeuge oder
 * weniger, ist er kritisch. Ist kein Mindestbestand gepflegt, entfällt die Prüfung.
 *
 * Rückgabe: { level: 'normal' | 'warning' | 'critical', reasons: [...] }
 */
function evaluateLocation(location) {
    const reasons = [];
    const defectLevel = evaluateDefectPercent(location.defect_percent);
    const tooFewTools = location.min_stock !== null && location.tools_all < location.min_stock;

    if (defectLevel === 'critical') {
        reasons.push('Defektrate zu hoch');
    }

    if (tooFewTools) {
        reasons.push('Zu wenig Werkzeuge');
    }

    if (reasons.length > 0) {
        return { level: 'critical', reasons };
    }

    if (defectLevel === 'warning') {
        return { level: 'warning', reasons: ['Defektrate erhöht'] };
    }

    return { level: 'normal', reasons: [] };
}

/*
 * Sortiert Standorte: kritisch zuerst, dann Warnung, dann normal;
 * innerhalb gleicher Stufe nach Defektquote absteigend.
 */
function sortLocationsBySeverity(locations) {
    return [...locations].sort((a, b) =>
        STATUS_ORDER[evaluateLocation(a).level] - STATUS_ORDER[evaluateLocation(b).level]
        || b.defect_percent - a.defect_percent
    );
}

function formatPercent(value) {
    return `${Number(value).toLocaleString('de-DE', { maximumFractionDigits: 1 })} %`;
}