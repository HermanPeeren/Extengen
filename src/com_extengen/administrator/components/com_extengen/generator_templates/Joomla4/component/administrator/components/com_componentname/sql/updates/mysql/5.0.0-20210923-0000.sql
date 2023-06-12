/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- Remove obsolete tables
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

-- Tickets table
ALTER TABLE `#__ats_tickets` CHANGE `title` `title` varchar(1024) NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_tickets` CHANGE `alias` `alias` varchar(255) NOT NULL DEFAULT '' COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_tickets` CHANGE `origin` `origin` varchar(10) NOT NULL DEFAULT 'web' COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_tickets` CHANGE `params` `params` TEXT NULL DEFAULT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_tickets` CHANGE `ats_ticket_id` `id` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `#__ats_tickets` CHANGE `created_on` `created` datetime NULL DEFAULT NULL;
ALTER TABLE `#__ats_tickets` CHANGE `modified_on` `modified` datetime NULL DEFAULT NULL;
ALTER TABLE `#__ats_tickets` DROP `rating`;
ALTER TABLE `#__ats_tickets` DROP `ats_bucket_id`;
ALTER TABLE `#__ats_tickets` ADD KEY `#__ats_tickets_key` (`alias`(100));
ALTER TABLE `#__ats_tickets` DEFAULT CHARSET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_tickets` ENGINE InnoDB;

-- Posts table
UPDATE `#__ats_posts`
SET `content_html` = `content`
WHERE (`content_html` IS NULL OR `content_html` = '');

ALTER TABLE `#__ats_posts` CHANGE `content_html` `content_html` longtext NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_posts` CHANGE `origin` `origin` VARCHAR(20) DEFAULT "web" COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_posts` CHANGE `ats_post_id` `id` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `#__ats_posts` CHANGE `ats_attachment_id` `attachment_id` VARCHAR(512) NOT NULL DEFAULT '0';
ALTER TABLE `#__ats_posts`  CHANGE `ats_ticket_id` `ticket_id` bigint(20) NOT NULL;
ALTER TABLE `#__ats_posts` DROP `content`;
ALTER TABLE `#__ats_posts` CHANGE `created_on` `created` datetime NULL DEFAULT NULL;
ALTER TABLE `#__ats_posts` CHANGE `modified_on` `modified` datetime NULL DEFAULT NULL;
ALTER TABLE `#__ats_posts` DROP KEY `content_html`;
ALTER TABLE `#__ats_posts` DEFAULT CHARSET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_posts` ENGINE InnoDB;

-- Attachments table
ALTER TABLE `#__ats_attachments` CHANGE `original_filename` `original_filename` varchar(1024) NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_attachments` CHANGE `mangled_filename` `mangled_filename` varchar(1024) NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_attachments` CHANGE `mime_type` `mime_type` varchar(255) NOT NULL DEFAULT 'application/octet-stream' COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_attachments` CHANGE `origin` `origin` VARCHAR(20) DEFAULT "web" COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_attachments` CHANGE `ats_attachment_id` `id` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `#__ats_attachments` CHANGE `ats_post_id` `post_id` INT(11) NOT NULL;
ALTER TABLE `#__ats_attachments` CHANGE `created_on` `created` datetime NULL DEFAULT NULL;
ALTER TABLE `#__ats_attachments` DEFAULT CHARSET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_attachments` ENGINE InnoDB;

-- Manager Notes table
UPDATE `#__ats_managernotes`
SET `note_html` = `note`
WHERE (`note_html` IS NULL OR `note_html` = '');

ALTER TABLE `#__ats_managernotes` CHANGE `note_html` `note_html` LONGTEXT COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_managernotes` CHANGE `ats_managernote_id` `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `#__ats_managernotes` CHANGE `ats_ticket_id` `ticket_id` bigint(20) unsigned NOT NULL;
ALTER TABLE `#__ats_managernotes` DROP `note`;
ALTER TABLE `#__ats_managernotes` CHANGE `created_on` `created` datetime NULL DEFAULT NULL;
ALTER TABLE `#__ats_managernotes` CHANGE `modified_on` `modified` datetime NULL DEFAULT NULL;
ALTER TABLE `#__ats_managernotes` DEFAULT CHARSET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_managernotes` ENGINE InnoDB;

-- Canned replies table
ALTER TABLE `#__ats_cannedreplies` CHANGE `title` `title` VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_cannedreplies` CHANGE `reply` `reply` text NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_cannedreplies` CHANGE `ats_cannedreply_id` `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `#__ats_cannedreplies` CHANGE `created_by` `created_by` bigint(20) NULL DEFAULT '0';
ALTER TABLE `#__ats_cannedreplies` CHANGE `created_on` `created` datetime NULL DEFAULT NULL;
ALTER TABLE `#__ats_cannedreplies` CHANGE `modified_by` `modified_by` bigint(20) NULL DEFAULT '0';
ALTER TABLE `#__ats_cannedreplies` CHANGE `modified_on` `modified` datetime NULL DEFAULT NULL;
ALTER TABLE `#__ats_cannedreplies` CHANGE `locked_on` `checked_out_time` datetime NULL DEFAULT NULL;
ALTER TABLE `#__ats_cannedreplies` CHANGE `locked_by` `checked_out` bigint(20) NULL DEFAULT '0';
ALTER TABLE `#__ats_cannedreplies` DEFAULT CHARSET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_cannedreplies` ENGINE InnoDB;

-- Automatic replies
ALTER TABLE `#__ats_autoreplies` CHANGE `ats_autoreply_id` `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE `#__ats_autoreplies` CHANGE `title` `title` varchar(50) NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_autoreplies` CHANGE `reply` `reply` text NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_autoreplies` CHANGE `keywords_title` `keywords_title` text NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_autoreplies` CHANGE `keywords_text` `keywords_text` text NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_autoreplies` CHANGE `params` `params` text NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_autoreplies` DEFAULT CHARSET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__ats_autoreplies` ENGINE InnoDB;

/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- UCM content type (for tagging)
DELETE
FROM `#__content_types`
WHERE `type_alias` = 'com_ats.ticket';
INSERT INTO `#__content_types`
(`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `router`, `content_history_options`)
VALUES ('Ticket', 'com_ats.ticket',
        '{\n	\"special\": {\n		\"dbtable\": \"#__ats_tickets\",\n		\"key\": \"id\",\n		\"type\": \"TicketTable\",\n		\"prefix\": \"Akeeba\\\\Component\\\\ATS\\\\Administrator\\\\Table\\\\\",\n		\"config\": \"array()\"\n	},\n	\"common\": {\n		\"dbtable\": \"#__ucm_content\",\n		\"key\": \"ucm_id\",\n		\"type\": \"Corecontent\",\n		\"prefix\": \"Joomla\\\\CMS\\\\Table\\\\\",\n		\"config\": \"array()\"\n	}\n}',
        '',
        '{\n	\"common\": {\n		\"core_content_item_id\": \"id\",\n		\"core_title\": \"title\",\n		\"core_state\": \"enabled\",\n		\"core_alias\": \"alias\",\n		\"core_created_time\": \"created\",\n		\"core_modified_time\": \"modified\",\n		\"core_params\": \"params\",\n		\"core_catid\": \"catid\"\n	},\n	\"special\": {\n		\"public\": \"public\",\n		\"priority\": \"priority\",\n		\"origin\": \"origin\",\n		\"assigned_to\": \"assigned_to\"\n	}\n}',
        'Akeeba\\Component\\ATS\\Site\\Helper\\Route::getTicketRoute', '');