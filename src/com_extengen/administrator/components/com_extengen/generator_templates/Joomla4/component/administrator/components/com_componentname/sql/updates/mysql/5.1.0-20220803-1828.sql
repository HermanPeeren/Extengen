/**
 * @package   ats
 * @copyright Copyright (c)2011-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- Fix discrepancy between updated and freshly installed sites
ALTER TABLE `#__ats_managernotes` CHANGE `ticket_id` `ticket_id` bigint(20) unsigned NOT NULL;
