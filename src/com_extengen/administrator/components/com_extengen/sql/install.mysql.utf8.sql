CREATE TABLE IF NOT EXISTS `#__extengen_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;


ALTER TABLE `#__extengen_projects` ADD COLUMN  `access` int(10) unsigned NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD KEY `idx_access` (`access`);

ALTER TABLE `#__extengen_projects` ADD COLUMN  `catid` int(11) NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN  `state` tinyint(3) NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD KEY `idx_catid` (`catid`);

ALTER TABLE `#__extengen_projects` ADD COLUMN  `published` tinyint(1) NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN  `publish_up` datetime AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN  `publish_down` datetime AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD KEY `idx_state` (`published`);

ALTER TABLE `#__extengen_projects` ADD COLUMN  `language` char(7) NOT NULL DEFAULT '*' AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD KEY `idx_language` (`language`);

ALTER TABLE `#__extengen_projects` ADD COLUMN  `ordering` int(11) NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN  `params` text NOT NULL AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN `checked_out` int(10) unsigned NOT NULL DEFAULT 0 AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN `checked_out_time` datetime AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD KEY `idx_checkout` (`checked_out`);

ALTER TABLE `#__extengen_projects` ADD COLUMN `name` varchar(255) NOT NULL DEFAULT '' AFTER `alias`;

ALTER TABLE `#__extengen_projects` ADD COLUMN `AST` text  AFTER `alias`;
