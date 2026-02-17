/**
 * @signature 8335b083faec391766bad3f53b958937
 * @version v1.18.5
 * @title Add Multi-Event Support to Webhooks
 */

-- 1. Add the new event columns (Defaulting to 0/False)
ALTER TABLE `%TABLE_PREFIX%webhook`
    ADD COLUMN IF NOT EXISTS `timeout` int(11) unsigned NOT NULL DEFAULT '0',
    ADD `event_new_ticket` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `timeout`,
    ADD `event_ticket_closed` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `event_new_ticket`,
    ADD `event_staff_reply` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `event_ticket_closed`,
    ADD `event_client_reply` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `event_staff_reply`;

-- 2. (Optional) If you have existing webhooks, enable them for ALL events by default
--    so they don't stop working suddenly.
UPDATE `%TABLE_PREFIX%webhook` 
SET `event_new_ticket`=1, `event_ticket_closed`=1, `event_staff_reply`=1, `event_client_reply`=1;

-- 3. Update the Schema Signature
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '3985c33fdcd5dfb07b18f9f0c7ec87a1'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';