ALTER TABLE `#__extengen_projects` ADD COLUMN `checked_out` int(10) unsigned NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN `checked_out_time` datetime AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD KEY `idx_checkout` (`checked_out`);
