-- --------------------------------------------------------
-- SQL Commands to set up the pmadb as described in Documentation.html.
-- 
-- This file is meant for use with Drizzle 2011.03.13 and above!
-- 
-- This script expects that you take care of database permissions.
--
-- Please don't forget to set up the tablenames in config.inc.php
-- 

-- --------------------------------------------------------

-- 
-- Database : `phpmyadmin`
-- 
CREATE DATABASE IF NOT EXISTS `phpmyadmin`
  COLLATE utf8_bin;
USE phpmyadmin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_bookmark`
-- 

CREATE TABLE IF NOT EXISTS `pma_bookmark` (
  `id` int(11) NOT NULL auto_increment,
  `dbase` varchar(255) NOT NULL default '',
  `user` varchar(255) NOT NULL default '',
  `label` varchar(255) COLLATE utf8_general_ci NOT NULL default '',
  `query` text NOT NULL,
  PRIMARY KEY  (`id`)
)
  ENGINE=InnoDB COMMENT='Bookmarks'
  COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_column_info`
-- 

CREATE TABLE IF NOT EXISTS `pma_column_info` (
  `id` int(5) NOT NULL auto_increment,
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `column_name` varchar(64) NOT NULL default '',
  `comment` varchar(255) COLLATE utf8_general_ci NOT NULL default '',
  `mimetype` varchar(255) COLLATE utf8_general_ci NOT NULL default '',
  `transformation` varchar(255) NOT NULL default '',
  `transformation_options` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`)
)
  ENGINE=InnoDB COMMENT='Column information for phpMyAdmin'
  COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_history`
-- 

CREATE TABLE IF NOT EXISTS `pma_history` (
  `id` bigint(20) NOT NULL auto_increment,
  `username` varchar(64) NOT NULL default '',
  `db` varchar(64) NOT NULL default '',
  `table` varchar(64) NOT NULL default '',
  `timevalue` timestamp NOT NULL,
  `sqlquery` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`,`db`,`table`,`timevalue`)
)
  ENGINE=InnoDB COMMENT='SQL history for phpMyAdmin'
  COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_pdf_pages`
-- 

CREATE TABLE IF NOT EXISTS `pma_pdf_pages` (
  `db_name` varchar(64) NOT NULL default '',
  `page_nr` int(10) NOT NULL auto_increment,
  `page_descr` varchar(50) COLLATE utf8_general_ci NOT NULL default '',
  PRIMARY KEY  (`page_nr`),
  KEY `db_name` (`db_name`)
)
  ENGINE=InnoDB COMMENT='PDF relation pages for phpMyAdmin'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma_recent`
--

CREATE TABLE IF NOT EXISTS `pma_recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL,
  PRIMARY KEY (`username`)
)
  ENGINE=InnoDB COMMENT='Recently accessed tables'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma_table_uiprefs`
--

CREATE TABLE IF NOT EXISTS `pma_table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`username`,`db_name`,`table_name`)
)
  ENGINE=InnoDB COMMENT='Tables'' UI preferences'
  COLLATE utf8_bin;

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
  ENGINE=InnoDB COMMENT='Relation table'
  COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_table_coords`
-- 

CREATE TABLE IF NOT EXISTS `pma_table_coords` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `pdf_page_number` int(11) NOT NULL default '0',
  `x` float NOT NULL default '0',
  `y` float NOT NULL default '0',
  PRIMARY KEY  (`db_name`,`table_name`,`pdf_page_number`)
)
  ENGINE=InnoDB COMMENT='Table coordinates for phpMyAdmin PDF output'
  COLLATE utf8_bin;

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
  ENGINE=InnoDB COMMENT='Table information for phpMyAdmin'
  COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_designer_coords`
-- 

CREATE TABLE IF NOT EXISTS `pma_designer_coords` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `x` INT,
  `y` INT,
  `v` INT,
  `h` INT,
  PRIMARY KEY (`db_name`,`table_name`)
)
  ENGINE=InnoDB COMMENT='Table coordinates for Designer'
  COLLATE utf8_bin;

-- --------------------------------------------------------

-- 
-- Table structure for table `pma_tracking`
-- 

CREATE TABLE IF NOT EXISTS `pma_tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text,
  `data_sql` text,
  `tracking` varchar(15) default NULL,
  `tracking_active` int(1) NOT NULL default '1',
  PRIMARY KEY  (`db_name`,`table_name`,`version`)
)
  ENGINE=InnoDB
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma_userconfig`
--

CREATE TABLE IF NOT EXISTS `pma_userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL,
  `config_data` text NOT NULL,
  PRIMARY KEY  (`username`)
)
  ENGINE=InnoDB COMMENT='User preferences storage for phpMyAdmin'
  COLLATE utf8_bin;
