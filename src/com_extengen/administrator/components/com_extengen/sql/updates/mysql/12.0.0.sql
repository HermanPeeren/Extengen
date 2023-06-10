ALTER TABLE `#__extengen_projects` ADD COLUMN  `catid` int(11) NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN  `state` tinyint(3) NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD KEY `idx_catid` (`catid`);
