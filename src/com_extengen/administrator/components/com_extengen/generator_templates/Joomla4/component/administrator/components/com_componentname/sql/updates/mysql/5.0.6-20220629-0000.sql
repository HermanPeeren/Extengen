/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- Canned replies table
ALTER TABLE `#__ats_cannedreplies` ADD `access` INT(11) DEFAULT '0';
UPDATE `#__ats_cannedreplies` SET `access` = 1 WHERE `access` = 0;