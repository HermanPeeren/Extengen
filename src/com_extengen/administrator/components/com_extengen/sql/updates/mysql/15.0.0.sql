ALTER TABLE `#__extengen_projects` ADD COLUMN  `language` char(7) NOT NULL DEFAULT '*' AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD KEY `idx_language` (`language`);
