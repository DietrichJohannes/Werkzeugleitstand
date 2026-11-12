-- phpMyAdmin SQL Dump
-- version 5.2.1deb1+deb12u1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Erstellungszeit: 11. Nov 2026 um 16:57
-- Server-Version: 10.11.19-MariaDB-0+deb12u1
-- PHP-Version: 8.2.35

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `lokring`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `bodies`
--

CREATE TABLE `bodies` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `bom_file_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grundkörper';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `cleanings`
--

CREATE TABLE `cleanings` (
  `id` int(10) UNSIGNED NOT NULL,
  `tool_id` int(10) UNSIGNED NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reinigungsnachweise';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `connections`
--

CREATE TABLE `connections` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `lokring` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Verbindungen';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `failures`
--

CREATE TABLE `failures` (
  `id` int(10) UNSIGNED NOT NULL,
  `tool_id` int(10) UNSIGNED NOT NULL,
  `cause_id` int(10) UNSIGNED DEFAULT NULL,
  `part_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'betroffenes Bauteil (optional)',
  `description` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ausfälle (Werkzeug geht in Wartung)';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `failure_causes`
--

CREATE TABLE `failure_causes` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auswahlliste der Ausfallursachen';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `jaw_sets`
--

CREATE TABLE `jaw_sets` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `connection_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Backensets';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `locations`
--

CREATE TABLE `locations` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `min_stock` int(10) UNSIGNED DEFAULT NULL COMMENT 'Mindestbestand an Werkzeugen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Standorte / Linien';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `parts`
--

CREATE TABLE `parts` (
  `id` int(10) UNSIGNED NOT NULL,
  `jaw_set_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `hibe_number` varchar(50) NOT NULL,
  `manufacturer_number` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ersatzteile eines Backensets';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `repairs`
--

CREATE TABLE `repairs` (
  `id` int(10) UNSIGNED NOT NULL,
  `tool_id` int(10) UNSIGNED NOT NULL,
  `failure_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reparaturen';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `repair_parts`
--

CREATE TABLE `repair_parts` (
  `repair_id` int(10) UNSIGNED NOT NULL,
  `part_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Getauschte Bauteile je Reparatur';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tools`
--

CREATE TABLE `tools` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `location_id` int(10) UNSIGNED NOT NULL,
  `status_id` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `jaw_set_id` int(10) UNSIGNED NOT NULL,
  `body_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Werkzeuge';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tool_events`
--

CREATE TABLE `tool_events` (
  `id` int(10) UNSIGNED NOT NULL,
  `tool_id` int(10) UNSIGNED NOT NULL,
  `field` varchar(30) NOT NULL COMMENT 'geändertes Feld oder "created"',
  `old_value` varchar(100) DEFAULT NULL,
  `new_value` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL COMMENT 'Anlass, z. B. "Ausfall gemeldet"',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Änderungsverlauf der Werkzeuge';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `tool_statuses`
--

CREATE TABLE `tool_statuses` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Werkzeug-Status';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `bodies`
--
ALTER TABLE `bodies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_bodies_name` (`name`);

--
-- Indizes für die Tabelle `cleanings`
--
ALTER TABLE `cleanings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cleanings_tool_id` (`tool_id`),
  ADD KEY `idx_cleanings_created_at` (`created_at`);

--
-- Indizes für die Tabelle `connections`
--
ALTER TABLE `connections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_connections_name` (`name`);

--
-- Indizes für die Tabelle `failures`
--
ALTER TABLE `failures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_failures_tool_id` (`tool_id`),
  ADD KEY `idx_failures_cause_id` (`cause_id`),
  ADD KEY `idx_failures_part_id` (`part_id`),
  ADD KEY `idx_failures_created_at` (`created_at`);

--
-- Indizes für die Tabelle `failure_causes`
--
ALTER TABLE `failure_causes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_failure_causes_name` (`name`);

--
-- Indizes für die Tabelle `jaw_sets`
--
ALTER TABLE `jaw_sets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_jaw_sets_name` (`name`),
  ADD KEY `idx_jaw_sets_connection_id` (`connection_id`);

--
-- Indizes für die Tabelle `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_locations_name` (`name`);

--
-- Indizes für die Tabelle `parts`
--
ALTER TABLE `parts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parts_jaw_set_id` (`jaw_set_id`),
  ADD KEY `idx_parts_hibe_number` (`hibe_number`);

--
-- Indizes für die Tabelle `repairs`
--
ALTER TABLE `repairs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_repairs_tool_id` (`tool_id`),
  ADD KEY `idx_repairs_failure_id` (`failure_id`),
  ADD KEY `idx_repairs_created_at` (`created_at`);

--
-- Indizes für die Tabelle `repair_parts`
--
ALTER TABLE `repair_parts`
  ADD PRIMARY KEY (`repair_id`,`part_id`),
  ADD KEY `idx_repair_parts_part_id` (`part_id`);

--
-- Indizes für die Tabelle `tools`
--
ALTER TABLE `tools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tools_name` (`name`),
  ADD KEY `idx_tools_location_id` (`location_id`),
  ADD KEY `idx_tools_status_id` (`status_id`),
  ADD KEY `idx_tools_jaw_set_id` (`jaw_set_id`),
  ADD KEY `idx_tools_body_id` (`body_id`);

--
-- Indizes für die Tabelle `tool_events`
--
ALTER TABLE `tool_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tool_events_tool_id` (`tool_id`),
  ADD KEY `idx_tool_events_created_at` (`created_at`);

--
-- Indizes für die Tabelle `tool_statuses`
--
ALTER TABLE `tool_statuses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tool_statuses_name` (`name`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `bodies`
--
ALTER TABLE `bodies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `cleanings`
--
ALTER TABLE `cleanings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `connections`
--
ALTER TABLE `connections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `failures`
--
ALTER TABLE `failures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `failure_causes`
--
ALTER TABLE `failure_causes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `jaw_sets`
--
ALTER TABLE `jaw_sets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `parts`
--
ALTER TABLE `parts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `repairs`
--
ALTER TABLE `repairs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tools`
--
ALTER TABLE `tools`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `tool_events`
--
ALTER TABLE `tool_events`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `cleanings`
--
ALTER TABLE `cleanings`
  ADD CONSTRAINT `fk_cleanings_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `failures`
--
ALTER TABLE `failures`
  ADD CONSTRAINT `fk_failures_cause` FOREIGN KEY (`cause_id`) REFERENCES `failure_causes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_failures_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_failures_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `jaw_sets`
--
ALTER TABLE `jaw_sets`
  ADD CONSTRAINT `fk_jaw_sets_connection` FOREIGN KEY (`connection_id`) REFERENCES `connections` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `parts`
--
ALTER TABLE `parts`
  ADD CONSTRAINT `fk_parts_jaw_set` FOREIGN KEY (`jaw_set_id`) REFERENCES `jaw_sets` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `repairs`
--
ALTER TABLE `repairs`
  ADD CONSTRAINT `fk_repairs_failure` FOREIGN KEY (`failure_id`) REFERENCES `failures` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_repairs_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `repair_parts`
--
ALTER TABLE `repair_parts`
  ADD CONSTRAINT `fk_repair_parts_part` FOREIGN KEY (`part_id`) REFERENCES `parts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_repair_parts_repair` FOREIGN KEY (`repair_id`) REFERENCES `repairs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints der Tabelle `tool_events`
--
ALTER TABLE `tool_events`
  ADD CONSTRAINT `fk_tool_events_tool` FOREIGN KEY (`tool_id`) REFERENCES `tools` (`id`) ON UPDATE CASCADE;

--
-- Constraints der Tabelle `tools`
--
ALTER TABLE `tools`
  ADD CONSTRAINT `fk_tools_body` FOREIGN KEY (`body_id`) REFERENCES `bodies` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tools_jaw_set` FOREIGN KEY (`jaw_set_id`) REFERENCES `jaw_sets` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tools_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tools_status` FOREIGN KEY (`status_id`) REFERENCES `tool_statuses` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
