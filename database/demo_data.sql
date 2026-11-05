-- ============================================================
--  Beispieldaten zum Testen (optional, NICHT für Produktion)
-- ============================================================
--  Voraussetzung: schema.sql + seed.sql auf einer LEEREN Datenbank.
--  Alle Namen sind frei erfunden.

SET NAMES utf8mb4;

INSERT INTO `locations` (`id`, `name`, `min_stock`) VALUES
  (1, 'Linie 1', 3),
  (2, 'Linie 2', 2),
  (3, 'Werkstatt', NULL);

INSERT INTO `connections` (`id`, `name`, `lokring`) VALUES
  (1, 'Verbindung 10 mm', 'LR 10'),
  (2, 'Verbindung 12 mm', 'LR 12');

INSERT INTO `jaw_sets` (`id`, `name`, `connection_id`) VALUES
  (1, 'Backenset 10', 1),
  (2, 'Backenset 12', 2);

INSERT INTO `bodies` (`id`, `name`, `bom_file_name`) VALUES
  (1, 'Grundkörper A', NULL),
  (2, 'Grundkörper B', NULL);

INSERT INTO `parts` (`id`, `jaw_set_id`, `name`, `hibe_number`, `manufacturer_number`) VALUES
  (1, 1, 'Oberbacke',     '00100001', 'OB-10'),
  (2, 1, 'Unterbacke',    '00100002', 'UB-10'),
  (3, 1, 'Druckfeder',    '00100003', 'DF-10'),
  (4, 2, 'Oberbacke',     '00120001', 'OB-12'),
  (5, 2, 'Unterbacke',    '00120002', 'UB-12'),
  (6, 2, 'Führungsstift', '00120003', 'FS-12');

-- Status: 1 Bereit, 2 Aktiv, 3 Wartung, 4 Verschrottet
INSERT INTO `tools` (`id`, `name`, `location_id`, `status_id`, `jaw_set_id`, `body_id`) VALUES
  (1, 'WZ-001', 1, 2, 1, 1),
  (2, 'WZ-002', 1, 1, 1, 1),
  (3, 'WZ-003', 1, 3, 1, 1),
  (4, 'WZ-004', 2, 2, 2, 2),
  (5, 'WZ-005', 2, 1, 2, 2),
  (6, 'WZ-006', 3, 3, 2, 2);

-- Ausfälle (cause_id: 1 Verschleiß, 2 Bruch, 3 Verschmutzung …)
INSERT INTO `failures` (`id`, `tool_id`, `cause_id`, `part_id`, `description`, `created_at`) VALUES
  (1, 3, 2, 1, 'Oberbacke gerissen',          NOW() - INTERVAL 2 DAY),
  (2, 6, 1, 6, 'Stift ausgeschlagen',         NOW() - INTERVAL 1 DAY),
  (3, 1, 3, NULL, 'Verpressung unsauber',     NOW() - INTERVAL 20 DAY);

-- Reparatur zu Ausfall 3 (Werkzeug 1 ist wieder im Einsatz)
INSERT INTO `repairs` (`id`, `tool_id`, `failure_id`, `description`, `created_at`) VALUES
  (1, 1, 3, 'Gereinigt und Feder getauscht', NOW() - INTERVAL 19 DAY);

INSERT INTO `repair_parts` (`repair_id`, `part_id`) VALUES
  (1, 3);
