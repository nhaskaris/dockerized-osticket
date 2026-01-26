/**
 * @signature 3d823ec24a50e59bc522ec95b3d0bc9e
 * @version v1.18.3
 * @title Add not_selectable to help_topic
 *
 * This patch adds a not_selectable field to help_topic for ticket selection control.
 */

ALTER TABLE `%TABLE_PREFIX%help_topic`
  ADD `not_selectable` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `notes`;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '7b395b9c2ccde3f6d8f6c7bccb12ccba'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';