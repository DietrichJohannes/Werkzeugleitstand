# Datenbank

| Datei | Inhalt |
|---|---|
| `schema.sql` | Alle Tabellen (Struktur, ohne Daten) |
| `seed.sql` | Pflicht-Grunddaten: Werkzeug-Status und Ausfallursachen |
| `demo_data.sql` | Optionale Beispieldaten zum Testen, **nicht für Produktion** |

## Neue Datenbank anlegen

In phpMyAdmin:

1. Links **Neu**, Name `lokring`, Kollation `utf8mb4_unicode_ci` → **Anlegen**.
2. Datenbank `lokring` auswählen → Reiter **Importieren** → `schema.sql` → **OK**.
3. Genauso `seed.sql` importieren.
4. Optional `demo_data.sql` importieren.

Datenbank-Benutzer für die Anwendung (einmalig, als Admin im Reiter **SQL**):

```sql
CREATE USER 'webapp'@'%' IDENTIFIED BY '<sicheres-passwort>';
GRANT SELECT, INSERT, UPDATE, DELETE ON lokring.* TO 'webapp'@'%';
```

Ein bestehender Benutzer behält seine Rechte, auch wenn die Datenbank gelöscht und neu angelegt wird.

## Spätere Änderungen an der Struktur

Wenn die Datenbank echte Daten enthält, darf sie nicht mehr neu angelegt werden. Änderungen kommen dann
als nummerierte Skripte nach `migrations/` (`001_….sql`, `002_….sql`, …). Jedes Skript wird einmal
ausgeführt, zuerst an einer Kopie (phpMyAdmin → Operationen → „Kopiere Datenbank nach“).
Gleichzeitig wird `schema.sql` so angepasst, dass eine Neuinstallation denselben Stand ergibt.

## Benennungsregeln

- Tabellen: englisch, Plural, `snake_case` (`jaw_sets`, `failure_causes`)
- Fremdschlüssel: `<tabelle_einzahl>_id` (`jaw_set_id`, `tool_id`)
- Alle IDs: `INT UNSIGNED`
- Erfassungszeitpunkt `created_at`, Ersteller `created_by`, Freitext `description`
- Dieselben Namen gelten in der API (JSON) und im JavaScript.
- Index: `idx_<tabelle>_<spalte>`, Fremdschlüssel: `fk_<tabelle>_<ziel>`

## Tabellen

| Tabelle          | Inhalt |
|------------------|--------|
| `tools`          | Werkzeuge (`location_id`, `status_id`, `jaw_set_id`, `body_id`) |
| `tool_statuses`  | Status 1 Bereit, 2 Aktiv, 3 Wartung, 4 Verschrottet (IDs fest im Code) |
| `locations`      | Standorte / Linien (`min_stock` = Mindestbestand) |
| `bodies`         | Grundkörper (`bom_file_name`) |
| `jaw_sets`       | Backensets (`connection_id`) |
| `connections`    | Verbindungen (`lokring`) |
| `parts`          | Ersatzteile eines Backensets (`jaw_set_id`, `hibe_number`, `manufacturer_number`) |
| `failures`       | Ausfälle (`tool_id`, `cause_id`, `part_id`, `description`, `created_by`, `created_at`) |
| `failure_causes` | Auswahlliste der Ausfallursachen (`active`, `sort_order`) |
| `repairs`        | Reparaturen (`tool_id`, `failure_id`, `description`, `created_by`, `created_at`) |
| `repair_parts`   | getauschte Bauteile je Reparatur |
| `cleanings`      | Reinigungsnachweise (`tool_id`, `created_by`, `created_at`) |

Werkzeuge, Bauteile und andere Stammdaten lassen sich nicht löschen, solange noch etwas auf sie verweist
(Fremdschlüssel mit `RESTRICT`). Ausgemusterte Werkzeuge werden auf „Verschrottet“ gesetzt.
