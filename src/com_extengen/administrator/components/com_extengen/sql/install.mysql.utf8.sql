CREATE TABLE IF NOT EXISTS `#__extengen_projects` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `alias` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '',
    `checked_out_time` datetime DEFAULT NULL,
    `checked_out` int(10) UNSIGNED DEFAULT '0',
    `params` text COLLATE utf8mb4_unicode_ci,
    `ordering` int(11) DEFAULT '0',
    `language` char(7) COLLATE utf8mb4_unicode_ci DEFAULT '*',
    `publish_down` datetime DEFAULT NULL,
    `publish_up` datetime DEFAULT NULL,
    `published` tinyint(1) DEFAULT '0',
    `state` tinyint(3) DEFAULT '0',
    `catid` int(11) DEFAULT '0',
    `access` int(10) UNSIGNED DEFAULT '0',
    `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
    `form_data` text COLLATE utf8mb4_unicode_ci,
    PRIMARY KEY (`id`),
    KEY `idx_access` (`access`),
    KEY `idx_catid` (`catid`),
    KEY `idx_state` (`published`),
    KEY `idx_language` (`language`),
    KEY `idx_checkout` (`checked_out`)
    ) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


