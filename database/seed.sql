-- ============================================================
--  Pflicht-Grunddaten (nach schema.sql einmal ausführen)
-- ============================================================
--  Ohne diese Daten funktioniert die Anwendung nicht.
--  Kann gefahrlos mehrfach ausgeführt werden.

SET NAMES utf8mb4;

-- IDs müssen zu src/tool_status.php und
-- public/assets/js/tool-status.js passen!
INSERT IGNORE INTO `tool_statuses` (`id`, `name`) VALUES
  (1, 'Bereit'),
  (2, 'Aktiv'),
  (3, 'Wartung'),
  (4, 'Verschrottet');

-- Startwerte, später im Editor erweiterbar
INSERT IGNORE INTO `failure_causes` (`name`, `sort_order`) VALUES
  ('Verschleiß',     10),
  ('Bruch',          20),
  ('Verschmutzung',  30),
  ('Fehlbedienung',  40),
  ('Sonstiges',     100);
