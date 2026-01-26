/**
 * @signature 3d823ec24a50e59bc522ec95b3d0bc9e
 * @version v1.18.4
 * @title Add not_selectable to help_topic
 */

-- Only add the column if it doesn't exist to prevent 500 errors
SET @dbname = DATABASE();
SET @tablename = (SELECT table_name FROM information_schema.tables WHERE table_schema = @dbname AND table_name LIKE '%help_topic' LIMIT 1);
SET @columnname = 'not_selectable';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = @dbname AND table_name = @tablename AND column_name = @columnname) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD ', @columnname, ' TINYINT(1) UNSIGNED NOT NULL DEFAULT ''0'' AFTER `notes` ')
));
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '7b395b9c2ccde3f6d8f6c7bccb12ccba'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';