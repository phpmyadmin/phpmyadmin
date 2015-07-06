-- --------------------------------------------------------
-- SQL Commands to set up the pmadb as described in the documentation.
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
-- Table structure for table `pma__bookmark`
--

CREATE TABLE IF NOT EXISTS `pma__bookmark` (
  `id` int(11) NOT NULL auto_increment,
  `dbase` varchar(255) NOT NULL default '',
  `user` varchar(255) NOT NULL default '',
  `label` varchar(255) COLLATE utf8_general_ci NOT NULL default '',
  `query` text NOT NULL,
  PRIMARY KEY  (`id`)
)
  COMMENT='Bookmarks'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE IF NOT EXISTS `pma__column_info` (
  `id` int(5) NOT NULL auto_increment,
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `column_name` varchar(64) NOT NULL default '',
  `comment` varchar(255) COLLATE utf8_general_ci NOT NULL default '',
  `mimetype` varchar(255) COLLATE utf8_general_ci NOT NULL default '',
  `transformation` varchar(255) NOT NULL default '',
  `transformation_options` varchar(255) NOT NULL default '',
  `input_transformation` varchar(255) NOT NULL default '',
  `input_transformation_options` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`)
)
  COMMENT='Column information for phpMyAdmin'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE IF NOT EXISTS `pma__history` (
  `id` bigint(20) NOT NULL auto_increment,
  `username` varchar(64) NOT NULL default '',
  `db` varchar(64) NOT NULL default '',
  `table` varchar(64) NOT NULL default '',
  `timevalue` timestamp NOT NULL,
  `sqlquery` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `username` (`username`,`db`,`table`,`timevalue`)
)
  COMMENT='SQL history for phpMyAdmin'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE IF NOT EXISTS `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL default '',
  `page_nr` int(10) NOT NULL auto_increment,
  `page_descr` varchar(50) COLLATE utf8_general_ci NOT NULL default '',
  PRIMARY KEY  (`page_nr`),
  KEY `db_name` (`db_name`)
)
  COMMENT='PDF relation pages for phpMyAdmin'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE IF NOT EXISTS `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL,
  PRIMARY KEY (`username`)
)
  COMMENT='Recently accessed tables'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE IF NOT EXISTS `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`username`,`db_name`,`table_name`)
)
  COMMENT='Tables'' UI preferences'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE IF NOT EXISTS `pma__relation` (
  `master_db` varchar(64) NOT NULL default '',
  `master_table` varchar(64) NOT NULL default '',
  `master_field` varchar(64) NOT NULL default '',
  `foreign_db` varchar(64) NOT NULL default '',
  `foreign_table` varchar(64) NOT NULL default '',
  `foreign_field` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`master_db`,`master_table`,`master_field`),
  KEY `foreign_field` (`foreign_db`,`foreign_table`)
)
  COMMENT='Relation table'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE IF NOT EXISTS `pma__table_coords` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `pdf_page_number` int(11) NOT NULL default '0',
  `x` float NOT NULL default '0',
  `y` float NOT NULL default '0',
  PRIMARY KEY  (`db_name`,`table_name`,`pdf_page_number`)
)
  COMMENT='Table coordinates for phpMyAdmin PDF output'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE IF NOT EXISTS `pma__table_info` (
  `db_name` varchar(64) NOT NULL default '',
  `table_name` varchar(64) NOT NULL default '',
  `display_field` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`db_name`,`table_name`)
)
  COMMENT='Table information for phpMyAdmin'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE IF NOT EXISTS `pma__tracking` (
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
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE IF NOT EXISTS `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL,
  `config_data` text NOT NULL,
  PRIMARY KEY  (`username`)
)
  COMMENT='User preferences storage for phpMyAdmin'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE IF NOT EXISTS `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL,
  PRIMARY KEY (`username`,`usergroup`)
)
  COMMENT='Users and their assignments to user groups'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE IF NOT EXISTS `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`usergroup`,`tab`,`allowed`)
)
  COMMENT='User groups with configured menu items'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE IF NOT EXISTS `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`)
)
  COMMENT='Hidden items of navigation tree'
  COLLATE utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE IF NOT EXISTS `pma__savedsearches` (
  `id` int(5) unsigned NOT NULL auto_increment,
  `username` varchar(64) NOT NULL default '',
  `db_name` varchar(64) NOT NULL default '',
  `search_name` varchar(64) NOT NULL default '',
  `search_data` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`)
)
  COMMENT='Saved searches'
  COLLATE utf8_bin;
