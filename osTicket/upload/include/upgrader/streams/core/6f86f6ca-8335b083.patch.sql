/**
 * @signature 6f86f6ca9d84ce53d72bf99e5d579860
 * @version v1.18.5
 * @title Create webhook table
 */

CREATE TABLE IF NOT EXISTS `%TABLE_PREFIX%webhook` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `event` varchar(255) NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Finished with patch
UPDATE `%TABLE_PREFIX%config`
    SET `value` = '8335b083faec391766bad3f53b958937'
    WHERE `key` = 'schema_signature' AND `namespace` = 'core';