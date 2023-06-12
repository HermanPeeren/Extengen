/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- Clean up leftovers from previous version (just in case)
DROP TABLE IF EXISTS `#__ats_attempts`;
DROP TABLE IF EXISTS `#__ats_emailtemplates`;
DROP TABLE IF EXISTS `#__ats_credittransactions`;
DROP TABLE IF EXISTS `#__ats_creditconsumptions`;
DROP TABLE IF EXISTS `#__ats_offlineschedules`;
DROP TABLE IF EXISTS `#__ats_customfields`;
DROP TABLE IF EXISTS `#__ats_customfields_cats`;
DROP TABLE IF EXISTS `#__ats_buckets`;
DROP TABLE IF EXISTS `#__ats_usertags`;
DROP TABLE IF EXISTS `#__ats_users_usertags`;

/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- Create tables
CREATE TABLE IF NOT EXISTS `#__ats_tickets` (
`id`    bigint(20) NOT NULL AUTO_INCREMENT,
`catid` bigint(20) NOT NULL,
`status` ENUM('O', 'P', 'C', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31', '32', '33', '34', '35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48', '49', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '60', '61', '62', '63', '64', '65', '66', '67', '68', '69', '70', '71', '72', '73', '74', '75', '76', '77', '78', '79', '80', '81', '82', '83', '84', '85', '86', '87', '88', '89', '90', '91', '92', '93', '94', '95', '96', '97', '98', '99') NOT NULL DEFAULT 'O',
`title`       varchar(1024) NOT NULL  COLLATE utf8mb4_unicode_ci,
`alias`       varchar(255)  NOT NULL DEFAULT '' COLLATE utf8mb4_unicode_ci,
`public`      tinyint(3)    NOT NULL DEFAULT '1',
`priority`    TINYINT       NOT NULL,
`origin`      varchar(10)   NOT NULL DEFAULT 'web' COLLATE utf8mb4_unicode_ci,
`assigned_to` BIGINT(20)    NOT NULL DEFAULT '0',
`timespent`   FLOAT         NOT NULL DEFAULT 0,
`created`     datetime      NULL     DEFAULT NULL,
`created_by`  bigint(20)    NOT NULL DEFAULT '0',
`modified`    datetime      NULL     DEFAULT NULL,
`modified_by` bigint(20)    NOT NULL DEFAULT '0',
`enabled`     tinyint(3)    NOT NULL DEFAULT '1',
`params`      TEXT          NULL     DEFAULT NULL COLLATE utf8mb4_unicode_ci,
PRIMARY KEY (`id`),
KEY `#__ats_tickets_key` (`alias`(100))
) ENGINE=InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ats_posts` (
`id`            bigint(20)   NOT NULL AUTO_INCREMENT,
`attachment_id` VARCHAR(512) NOT NULL DEFAULT '0',
`ticket_id`     bigint(20)   NOT NULL,
`content_html`  longtext     NOT NULL COLLATE utf8mb4_unicode_ci,
`origin`        VARCHAR(20)           DEFAULT "web" COLLATE utf8mb4_unicode_ci,
`timespent`     FLOAT        NOT NULL DEFAULT 0,
`email_uid`     VARCHAR(255) NULL,
`created`       datetime     NULL     DEFAULT NULL,
`created_by`    bigint(20)   NOT NULL DEFAULT '0',
`modified`      datetime     NULL     DEFAULT NULL,
`modified_by`   bigint(20)   NOT NULL DEFAULT '0',
`enabled`       tinyint(4)   NOT NULL DEFAULT '1',
PRIMARY KEY (`id`),
KEY `#__ats_posts_email_uid` (`email_uid`(100))
) ENGINE=InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ats_attachments` (
`id`                bigint(20)    NOT NULL AUTO_INCREMENT,
`post_id`           INT(11)       NOT NULL,
`original_filename` varchar(1024) NOT NULL COLLATE utf8mb4_unicode_ci,
`mangled_filename`  varchar(1024) NOT NULL COLLATE utf8mb4_unicode_ci,
`mime_type`         varchar(255)  NOT NULL DEFAULT 'application/octet-stream' COLLATE utf8mb4_unicode_ci,
`origin`            VARCHAR(20)            DEFAULT "web" COLLATE utf8mb4_unicode_ci,
`created`           datetime      NULL     DEFAULT NULL,
`created_by`        bigint(20)    NOT NULL DEFAULT '0',
`enabled`           tinyint(4)    NOT NULL DEFAULT '1',
PRIMARY KEY (`id`),
KEY `#__ats_post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ats_managernotes` (
`id`          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`ticket_id`   bigint(20) unsigned NOT NULL,
`note_html`   LONGTEXT COLLATE utf8mb4_unicode_ci,
`created`     datetime            NULL     DEFAULT NULL,
`created_by`  bigint(20)          NOT NULL DEFAULT '0',
`modified`    datetime            NULL     DEFAULT NULL,
`modified_by` bigint(20)          NOT NULL DEFAULT '0',
`enabled`     tinyint(3)          NOT NULL DEFAULT '1',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__ats_cannedreplies` (
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`title` VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci,
`reply` text NOT NULL COLLATE utf8mb4_unicode_ci,
`ordering` bigint(20) unsigned NOT NULL,
`enabled` tinyint(3) NOT NULL DEFAULT '1',
`created` datetime NULL DEFAULT NULL,
`created_by` bigint(20) NULL DEFAULT '0',
`modified` datetime NULL DEFAULT NULL,
`modified_by` bigint(20) NULL DEFAULT '0',
`checked_out_time` datetime NULL DEFAULT NULL,
`checked_out` bigint(20) NULL DEFAULT '0',
`access` int(11) NULL DEFAULT '0',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `#__ats_autoreplies` (
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`title` varchar(50) NOT NULL COLLATE utf8mb4_unicode_ci,
`reply` text NOT NULL COLLATE utf8mb4_unicode_ci,
`ordering` tinyint(4) NOT NULL,
`enabled` tinyint(1) NOT NULL,
`run_after_manager` tinyint(1) NOT NULL,
`num_posts` int(11) NOT NULL,
`min_after` int(11) NOT NULL,
`attachment` tinyint(4) NOT NULL,
`keywords_title` text NOT NULL COLLATE utf8mb4_unicode_ci,
`keywords_text` text NOT NULL COLLATE utf8mb4_unicode_ci,
`params` text NOT NULL COLLATE utf8mb4_unicode_ci,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;

-- UCM Content types (for tagging)
DELETE FROM `#__content_types` WHERE `type_alias` = 'com_ats.ticket';

INSERT INTO `#__content_types`
    (`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`)
VALUES
    ('Ticket', 'com_ats.ticket', '{\n	\"special\": {\n		\"dbtable\": \"#__ats_tickets\",\n		\"key\": \"id\",\n		\"type\": \"TicketTable\",\n		\"prefix\": \"Akeeba\\\\Component\\\\ATS\\\\Administrator\\\\Table\\\\\",\n		\"config\": \"array()\"\n	},\n	\"common\": {\n		\"dbtable\": \"#__ucm_content\",\n		\"key\": \"ucm_id\",\n		\"type\": \"Corecontent\",\n		\"prefix\": \"Joomla\\\\CMS\\\\Table\\\\\",\n		\"config\": \"array()\"\n	}\n}', '', '{\n	\"common\": {\n		\"core_content_item_id\": \"id\",\n		\"core_title\": \"title\",\n		\"core_state\": \"enabled\",\n		\"core_alias\": \"alias\",\n		\"core_created_time\": \"created\",\n		\"core_modified_time\": \"modified\",\n		\"core_params\": \"params\",\n		\"core_catid\": \"catid\"\n	},\n	\"special\": {\n		\"public\": \"public\",\n		\"priority\": \"priority\",\n		\"origin\": \"origin\",\n		\"assigned_to\": \"assigned_to\"\n	}\n}', 'Akeeba\\Component\\ATS\\Site\\Helper\\Route::getTicketRoute', '');

--
-- Create the common table for all Akeeba extensions.
--
-- This table is never uninstalled when uninstalling the extensions themselves.
--
CREATE TABLE IF NOT EXISTS `#__akeeba_common` (
    `key`   VARCHAR(190) NOT NULL,
    `value` LONGTEXT     NOT NULL,
    PRIMARY KEY (`key`(100))
) ENGINE InnoDB DEFAULT COLLATE utf8mb4_unicode_ci;