/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- Remove our tables
DROP TABLE IF EXISTS `#__ats_tickets`;
DROP TABLE IF EXISTS `#__ats_posts`;
DROP TABLE IF EXISTS `#__ats_attachments`;
DROP TABLE IF EXISTS `#__ats_managernotes`;
DROP TABLE IF EXISTS `#__ats_cannedreplies`;
DROP TABLE IF EXISTS `#__ats_autoreplies`;

-- Remove ticket custom field values
DELETE FROM `#__fields_values` WHERE `field_id` IN (SELECT `field_id` FROM `#__fields` WHERE `context` = 'com_ats.ticket');

-- Remove ticket custom fields and their associations to ticket categories
DELETE FROM `#__fields_categories` WHERE `category_id` IN (SELECT `id` FROM `#__categories` AS `c` WHERE `c`.`extension` = 'com_ats');

DELETE FROM `#__fields` WHERE `context` = 'com_ats.ticket';

-- Remove ticket custom  field groups
DELETE FROM `#__fields_groups` WHERE `context` = 'com_ats.ticket';

/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- Remove the UCM content type for tickets
DELETE FROM `#__content_types` WHERE `type_alias` = 'com_ats.ticket';

-- Remove the UCM content entries for tickets (used by tags).
-- Note that we cannot remove the tags themselves; tags are global, not per content type!
DELETE FROM `#__ucm_content` WHERE `core_type_alias` = 'com_ats.ticket';

-- Remove ticket categories
DELETE FROM `#__categories` WHERE `extension` = 'com_ats';

-- Mail templates
DELETE FROM `#__mail_templates` WHERE `extension` = 'com_ats';