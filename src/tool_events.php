<?php
declare(strict_types=1);

/*
 * ============================================================
 *  Änderungsverlauf der Werkzeuge (Tabelle tool_events)
 * ============================================================
 *  Jede Änderung an einem Werkzeug läuft über updateToolTracked(),
 *  damit sie im Verlauf landet – auch automatische Statuswechsel
 *  durch Ausfall (→ Wartung) und Reparatur (→ Bereit).
 *
 *  Gespeichert werden Rohwerte (IDs bzw. Name). Die Anzeigetexte
 *  ermittelt /api/tool_events.php beim Lesen.
 *
 *  Benötigt api_bootstrap.php (db(), currentUserId(), insertRow()).
 */

// Felder, deren Änderung protokolliert wird
const TOOL_TRACKED_FIELDS = ['name', 'status_id', 'location_id', 'body_id', 'jaw_set_id'];

const TOOL_EVENT_CREATED = 'created';

/*
 * Ändert ein Werkzeug und schreibt für jedes Feld, dessen Wert sich
 * wirklich ändert, einen Eintrag in tool_events. Unveränderte Felder
 * (z. B. der Standort, den der Dialog immer mitschickt) erzeugen
 * keinen Eintrag.
 *
 * Läuft in einer Transaktion – eine bereits offene wird mitbenutzt.
 */
function updateToolTracked(int $toolId, array $values, ?string $description = null): void
{
    $pdo = db();
    $ownTransaction = !$pdo->inTransaction();

    if ($ownTransaction) {
        $pdo->beginTransaction();
    }

    try {
        // FOR UPDATE: gleichzeitige Änderungen am selben Werkzeug warten,
        // damit old_value stimmt
        $stmt = $pdo->prepare('SELECT ' . implode(', ', TOOL_TRACKED_FIELDS) . ' FROM tools WHERE id = ? FOR UPDATE');
        $stmt->execute([$toolId]);
        $before = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($before === false) {
            sendError(404, 'Werkzeug nicht gefunden');
        }

        $changed = [];
        foreach ($values as $field => $value) {
            if ((string)$before[$field] !== (string)$value) {
                $changed[$field] = $value;
            }
        }

        if ($changed) {
            updateRow('tools', $toolId, $changed);

            foreach ($changed as $field => $value) {
                recordToolEvent($toolId, $field, (string)$before[$field], (string)$value, $description);
            }
        }

        if ($ownTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($ownTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;   // zentrale Fehlerbehandlung
    }
}

function recordToolEvent(int $toolId, string $field, ?string $oldValue, ?string $newValue, ?string $description = null): void
{
    if (!in_array($field, [...TOOL_TRACKED_FIELDS, TOOL_EVENT_CREATED], true)) {
        throw new LogicException("Unbekanntes Verlaufsfeld '$field'");
    }

    insertRow('tool_events', [
        'tool_id'     => $toolId,
        'field'       => $field,
        'old_value'   => $oldValue,
        'new_value'   => $newValue,
        'description' => $description,
        'created_by'  => currentUserId(),
    ]);
}
