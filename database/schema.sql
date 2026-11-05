-- ============================================================
--  BSH Werkzeugleitstand – Datenbank-Schema
-- ============================================================
--  Für: MariaDB 10.11+ (MySQL 8 sollte ebenso funktionieren)
--
--  Neue, LEERE Datenbank anlegen:
--    1. schema.sql   (diese Datei – Tabellen)
--    2. seed.sql     (Pflicht-Grunddaten: Status, Ausfallursachen)
--    3. demo_data.sql (optional, Beispieldaten zum Testen)
--
--  Benennungsregeln: siehe database/README.md
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ------------------------------------------------------------
-- Stammdaten
-- ------------------------------------------------------------

CREATE TABLE `locations` (
  `id`        int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`      varchar(100)     NOT NULL,
  `min_stock` int(10) UNSIGNED NULL DEFAULT NULL COMMENT 'Mindestbestand an Werkzeugen',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_locations_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Standorte / Linien';

CREATE TABLE `connections` (
  `id`      int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`    varchar(100)     NOT NULL,
  `lokring` varchar(100)     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_connections_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Verbindungen';

CREATE TABLE `jaw_sets` (
  `id`            int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          varchar(100)     NOT NULL,
  `connection_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jaw_sets_name` (`name`),
  KEY `idx_jaw_sets_connection_id` (`connection_id`),
  CONSTRAINT `fk_jaw_sets_connection` FOREIGN KEY (`connection_id`)
    REFERENCES `connections` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Backensets';

CREATE TABLE `bodies` (
  `id`            int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          varchar(100)     NOT NULL,
  `bom_file_name` varchar(255)     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bodies_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Grundkörper';

CREATE TABLE `parts` (
  `id`                  int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `jaw_set_id`          int(10) UNSIGNED NOT NULL,
  `name`                varchar(100)     NOT NULL,
  `hibe_number`         varchar(50)      NOT NULL,
  `manufacturer_number` varchar(50)      NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_parts_jaw_set_id` (`jaw_set_id`),
  KEY `idx_parts_hibe_number` (`hibe_number`),
  CONSTRAINT `fk_parts_jaw_set` FOREIGN KEY (`jaw_set_id`)
    REFERENCES `jaw_sets` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ersatzteile eines Backensets';


-- ------------------------------------------------------------
-- Werkzeuge
-- ------------------------------------------------------------

-- IDs sind fest im Code hinterlegt (src/tool_status.php,
-- public/assets/js/tool-status.js) → kein AUTO_INCREMENT
CREATE TABLE `tool_statuses` (
  `id`   int(10) UNSIGNED NOT NULL,
  `name` varchar(50)      NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tool_statuses_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Werkzeug-Status';

CREATE TABLE `tools` (
  `id`          int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        varchar(100)     NOT NULL,
  `location_id` int(10) UNSIGNED NOT NULL,
  `status_id`   int(10) UNSIGNED NOT NULL DEFAULT 1,
  `jaw_set_id`  int(10) UNSIGNED NOT NULL,
  `body_id`     int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tools_name` (`name`),
  KEY `idx_tools_location_id` (`location_id`),
  KEY `idx_tools_status_id`   (`status_id`),
  KEY `idx_tools_jaw_set_id`  (`jaw_set_id`),
  KEY `idx_tools_body_id`     (`body_id`),
  CONSTRAINT `fk_tools_location` FOREIGN KEY (`location_id`)
    REFERENCES `locations` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tools_status` FOREIGN KEY (`status_id`)
    REFERENCES `tool_statuses` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tools_jaw_set` FOREIGN KEY (`jaw_set_id`)
    REFERENCES `jaw_sets` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tools_body` FOREIGN KEY (`body_id`)
    REFERENCES `bodies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Werkzeuge';


-- ------------------------------------------------------------
-- Ausfälle, Reparaturen, Reinigungen (Historie)
-- ------------------------------------------------------------
--  created_by: Benutzer-ID aus dem Login (NULL, solange es noch
--  keine Benutzer gibt). Wird vom Server gesetzt, nie vom Browser.

-- active = 0 blendet eine Ursache in der Auswahl aus, ohne sie zu löschen
CREATE TABLE `failure_causes` (
  `id`         int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       varchar(100)     NOT NULL,
  `sort_order` int(11)          NOT NULL DEFAULT 0,
  `active`     tinyint(1)       NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_failure_causes_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Auswahlliste der Ausfallursachen';

CREATE TABLE `failures` (
  `id`          int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id`     int(10) UNSIGNED NOT NULL,
  `cause_id`    int(10) UNSIGNED NULL DEFAULT NULL,
  `part_id`     int(10) UNSIGNED NULL DEFAULT NULL COMMENT 'betroffenes Bauteil (optional)',
  `description` text             NULL DEFAULT NULL,
  `created_by`  int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at`  datetime         NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_failures_tool_id`    (`tool_id`),
  KEY `idx_failures_cause_id`   (`cause_id`),
  KEY `idx_failures_part_id`    (`part_id`),
  KEY `idx_failures_created_at` (`created_at`),
  CONSTRAINT `fk_failures_tool` FOREIGN KEY (`tool_id`)
    REFERENCES `tools` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_failures_cause` FOREIGN KEY (`cause_id`)
    REFERENCES `failure_causes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_failures_part` FOREIGN KEY (`part_id`)
    REFERENCES `parts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ausfälle (Werkzeug geht in Wartung)';

-- Ausfallzeit = repairs.created_at - failures.created_at
-- Ein Ausfall ohne Reparatur gilt als "offen".
CREATE TABLE `repairs` (
  `id`          int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id`     int(10) UNSIGNED NOT NULL,
  `failure_id`  int(10) UNSIGNED NULL DEFAULT NULL,
  `description` text             NULL DEFAULT NULL,
  `created_by`  int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at`  datetime         NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_repairs_tool_id`    (`tool_id`),
  KEY `idx_repairs_failure_id` (`failure_id`),
  KEY `idx_repairs_created_at` (`created_at`),
  CONSTRAINT `fk_repairs_tool` FOREIGN KEY (`tool_id`)
    REFERENCES `tools` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_repairs_failure` FOREIGN KEY (`failure_id`)
    REFERENCES `failures` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Reparaturen';

CREATE TABLE `repair_parts` (
  `repair_id` int(10) UNSIGNED NOT NULL,
  `part_id`   int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`repair_id`, `part_id`),
  KEY `idx_repair_parts_part_id` (`part_id`),
  CONSTRAINT `fk_repair_parts_repair` FOREIGN KEY (`repair_id`)
    REFERENCES `repairs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_repair_parts_part` FOREIGN KEY (`part_id`)
    REFERENCES `parts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Getauschte Bauteile je Reparatur';

CREATE TABLE `cleanings` (
  `id`         int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_id`    int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` datetime         NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cleanings_tool_id`    (`tool_id`),
  KEY `idx_cleanings_created_at` (`created_at`),
  CONSTRAINT `fk_cleanings_tool` FOREIGN KEY (`tool_id`)
    REFERENCES `tools` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Reinigungsnachweise';


SET FOREIGN_KEY_CHECKS = 1;
