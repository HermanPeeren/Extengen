ALTER TABLE `#__extengen_projects` ADD COLUMN  `published` tinyint(1) NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN  `publish_up` datetime AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN  `publish_down` datetime AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD KEY `idx_state` (`published`);
