-- -------------------------------------------------------------
-- SQL Commands to upgrade pmadb for normal phpMyAdmin operation
-- with MySQL 4.1.2 and above.
--
-- This file is meant for use with MySQL 4.1.2 and above!
-- For older MySQL releases, please use create_tables.sql
--
-- If you are running one MySQL 4.1.0 or 4.1.1, please create the tables using
-- create_tables.sql, then use this script.
--
-- Please don't forget to set up the tablenames in config.inc.php
--
-- $Id$

-- --------------------------------------------------------

--
-- Database : `phpmyadmin`
--
ALTER DATABASE `phpmyadmin`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE phpmyadmin;

-- --------------------------------------------------------

--
-- Table structure for table `pma_bookmark`
--
ALTER TABLE `pma_bookmark`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma_bookmark`
  CHANGE `dbase` `dbase` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_bookmark`
  CHANGE `user` `user` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_bookmark`
  CHANGE `label` `label` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `pma_bookmark`
  CHANGE `query` `query` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;

-- --------------------------------------------------------

--
-- Table structure for table `pma_column_info`
--

ALTER TABLE `pma_column_info`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma_column_info`
  CHANGE `db_name` `db_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_column_info`
  CHANGE `table_name` `table_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_column_info`
  CHANGE `column_name` `column_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_column_info`
  CHANGE `comment` `comment` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `pma_column_info`
  CHANGE `mimetype` `mimetype` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `pma_column_info`
  CHANGE `transformation` `transformation` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_column_info`
  CHANGE `transformation_options` `transformation_options` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

-- --------------------------------------------------------

--
-- Table structure for table `pma_history`
--
ALTER TABLE `pma_history`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma_history`
  CHANGE `username` `username` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_history`
  CHANGE `db` `db` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_history`
  CHANGE `table` `table` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_history`
  CHANGE `sqlquery` `sqlquery` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;

-- --------------------------------------------------------

--
-- Table structure for table `pma_pdf_pages`
--

ALTER TABLE `pma_pdf_pages`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma_pdf_pages`
  CHANGE `db_name` `db_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_pdf_pages`
  CHANGE `page_descr` `page_descr` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '';

-- --------------------------------------------------------

--
-- Table structure for table `pma_relation`
--
ALTER TABLE `pma_relation`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma_relation`
  CHANGE `master_db` `master_db` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_relation`
  CHANGE `master_table` `master_table` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_relation`
  CHANGE `master_field` `master_field` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_relation`
  CHANGE `foreign_db` `foreign_db` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_relation`
  CHANGE `foreign_table` `foreign_table` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_relation`
  CHANGE `foreign_field` `foreign_field` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

-- --------------------------------------------------------

--
-- Table structure for table `pma_table_coords`
--

ALTER TABLE `pma_table_coords`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma_table_coords`
  CHANGE `db_name` `db_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_table_coords`
  CHANGE `table_name` `table_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

-- --------------------------------------------------------

--
-- Table structure for table `pma_table_info`
--

ALTER TABLE `pma_table_info`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma_table_info`
  CHANGE `db_name` `db_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_table_info`
  CHANGE `table_name` `table_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma_table_info`
  CHANGE `display_field` `display_field` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

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

