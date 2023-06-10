ALTER TABLE `#__extengen_projects` ADD COLUMN  `access` int(10) unsigned NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD KEY `idx_access` (`access`);
