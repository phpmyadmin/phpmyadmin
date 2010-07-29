-- --------------------------------------------------------
-- SQL Commands to set up the pmadb as described in Documentation.html.
-- 
-- This file is meant for use with MySQL 5 and above!
-- 
-- This script expects the user pma to already be existing. If we would put a
-- line here to create him too many users might just use this script and end
-- up with having the same password for the controluser.
--                                                     
-- This user "pma" must be defined in config.inc.php (controluser/controlpass)                         
--                                                  
-- Please don't forget to set up the tablenames in config.inc.php                                 
-- 
-- $Id$

-- --------------------------------------------------------

-- 
-- Database : `phpmyadmin`
-- 
CREATE DATABASE IF NOT EXISTS `phpmyadmin`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE phpmyadmin;

-- --------------------------------------------------------

-- 
-- Privileges
-- 
-- (activate this statement if necessary)
-- GRANT SELECT, INSERT, DELETE, UPDATE ON `phpmyadmin`.* TO
--    'pma'@localhost;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_bookmark`
-- 

CREATE TABLE IF NOT EXISTS `pma_bookmark` (
  `id` int(11) NOT NULL auto_increment,
  `dbase` varchar(255) NOT NULL default '',
  `user` varchar(255) NOT NULL default '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
  `query` text NOT NULL,
  PRIMARY KEY  (`id`)
)
  ENGINE=MyISAM COMMENT='Bookmarks'
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_column_info`
-- 

CREATE TABLE IF NOT EXISTS `pma_column_info` (
  `id` int(5) unsigned NOT NULL auto_increment,
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `column_name` varchar(64) NOT NULL default '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
  `transformation` varchar(255) NOT NULL default '',
  `transformation_options` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`)
)
  ENGINE=MyISAM COMMENT='Column information for phpMyAdmin'
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_history`
-- 

CREATE TABLE IF NOT EXISTS `pma_history` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `username` varchar(64) NOT NULL default '',
  `db` varchar(64) NOT NULL default '',
  `table` varchar(64) NOT NULL default '',
  `timevalue` timestamp NOT NULL,
  `sqlquery` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`,`db`,`table`,`timevalue`)
)
  ENGINE=MyISAM COMMENT='SQL history for phpMyAdmin'
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_pdf_pages`
-- 

CREATE TABLE IF NOT EXISTS `pma_pdf_pages` (
  `db_name` varchar(64) NOT NULL default '',
  `page_nr` int(10) unsigned NOT NULL auto_increment,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
  PRIMARY KEY  (`page_nr`),
  KEY `db_name` (`db_name`)
)
  ENGINE=MyISAM COMMENT='PDF relation pages for phpMyAdmin'
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_relation`
-- 

CREATE TABLE IF NOT EXISTS `pma_relation` (
  `master_db` varchar(64) NOT NULL default '',
  `master_table` varchar(64) NOT NULL default '',
  `master_field` varchar(64) NOT NULL default '',
  `foreign_db` varchar(64) NOT NULL default '',
  `foreign_table` varchar(64) NOT NULL default '',
  `foreign_field` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`master_db`,`master_table`,`master_field`),
  KEY `foreign_field` (`foreign_db`,`foreign_table`)
)
  ENGINE=MyISAM COMMENT='Relation table'
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_table_coords`
-- 

CREATE TABLE IF NOT EXISTS `pma_table_coords` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `pdf_page_number` int(11) NOT NULL default '0',
  `x` float unsigned NOT NULL default '0',
  `y` float unsigned NOT NULL default '0',
  PRIMARY KEY  (`db_name`,`table_name`,`pdf_page_number`)
)
  ENGINE=MyISAM COMMENT='Table coordinates for phpMyAdmin PDF output'
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_table_info`
-- 

CREATE TABLE IF NOT EXISTS `pma_table_info` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `display_field` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`db_name`,`table_name`)
)
  ENGINE=MyISAM COMMENT='Table information for phpMyAdmin'
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_designer_coords`
-- 

CREATE TABLE IF NOT EXISTS `pma_designer_coords` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `x` INT,
  `y` INT,
  `v` TINYINT,
  `h` TINYINT,
  PRIMARY KEY (`db_name`,`table_name`)
)
  ENGINE=MyISAM COMMENT='Table coordinates for Designer'
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_tracking`
-- 

CREATE TABLE IF NOT EXISTS `pma_tracking` (
  `db_name` varchar(64) collate utf8_bin NOT NULL,
  `table_name` varchar(64) collate utf8_bin NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text collate utf8_bin NOT NULL,
  `schema_sql` text collate utf8_bin,
  `data_sql` longtext collate utf8_bin,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') collate utf8_bin default NULL,
  `tracking_active` int(1) unsigned NOT NULL default '1',
  PRIMARY KEY  (`db_name`,`table_name`,`version`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=COMPACT;
