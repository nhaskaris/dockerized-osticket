/**
 * @version v1.18.3
 * @signature 3d823ec24a50e59bc522ec95b3d0bc9e
 * Add can_reply_tickets permission to API keys
 */

-- Add can_reply_tickets column to api_key table if it doesn't exist
ALTER TABLE `%TABLE_PREFIX%api_key` ADD COLUMN `can_reply_tickets` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `can_create_tickets`;

-- Update schema signature
UPDATE `%TABLE_PREFIX%config` 
    SET `value` = '3d823ec24a50e59bc522ec95b3d0bc9e', updated = NOW()
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';
