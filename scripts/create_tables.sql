-- ########################################################
--                                                        #
--  SQL Commands to set up the pmadb as described in      #
--  Documentation.txt.                                    #
--                                                        #
--  This script expects the user pma to already be        #
--  existing. (if we would put a line here to create him  #
--  too many users might just use this script and end     #
--  up with having the same password for the controluser) #
--                                                        #
--  This user "pma" must be defined in config.inc.php     #
--  (controluser/controlpass)                             #
--                                                        #
--  Please dont forget to set up the tablenames in        #
--  config.inc.php                                        #
--                                                        #
--  Please note that the table names might be converted   #
--  to lower case, if the MySQL option                    #
--  "lower_case_table_names" is enabled. By default, this #
--  is the case on Win32 machines.                        #
--                                                        #
-- ########################################################

DROP DATABASE IF EXISTS `phpmyadmin`;
CREATE DATABASE IF NOT EXISTS `phpmyadmin`;

-- (backquotes are not supported in USE)
USE phpmyadmin;

GRANT SELECT, INSERT, DELETE, UPDATE ON `phpmyadmin`.* TO
    'pma'@localhost;

DROP TABLE IF EXISTS `pma_bookmark`;
CREATE TABLE `pma_bookmark` (
    `id` int(11) DEFAULT '0' NOT NULL AUTO_INCREMENT,
    `dbase` VARCHAR(255) NOT NULL,
    `user` VARCHAR(255) NOT NULL,
    `label` VARCHAR(255) NOT NULL,
    `query` TEXT NOT NULL,
    PRIMARY KEY (`id`)
) TYPE=MyISAM COMMENT='Bookmarks';

DROP TABLE IF EXISTS `pma_relation`;
CREATE TABLE `pma_relation` (
    `master_db` VARCHAR(64) NOT NULL DEFAULT '',
    `master_table` VARCHAR(64) NOT NULL DEFAULT '',
    `master_field` VARCHAR(64) NOT NULL DEFAULT '',
    `foreign_db` VARCHAR(64) NOT NULL DEFAULT '',
    `foreign_table` VARCHAR(64) NOT NULL DEFAULT '',
    `foreign_field` VARCHAR(64) NOT NULL DEFAULT '',
    PRIMARY KEY (`master_db`, `master_table`,`master_field`),
    KEY `foreign_field` (`foreign_db`, `foreign_table`)
) TYPE=MyISAM COMMENT='Relation table';

DROP TABLE IF EXISTS `pma_table_info`;
CREATE TABLE `pma_table_info` (
    `db_name` VARCHAR(64) NOT NULL DEFAULT '',
    `table_name` VARCHAR(64) NOT NULL DEFAULT '',
    `display_field` VARCHAR(64) NOT NULL DEFAULT '',
    PRIMARY KEY (`db_name`, `table_name`)
) TYPE=MyISAM COMMENT='Table information for phpMyAdmin';

DROP TABLE IF EXISTS `pma_table_coords`;
CREATE TABLE `pma_table_coords` (
    `db_name` VARCHAR(64) NOT NULL DEFAULT '',
    `table_name` VARCHAR(64) NOT NULL DEFAULT '',
    `pdf_page_number` INT NOT NULL DEFAULT '0',
    `x` float unsigned NOT NULL DEFAULT '0',
    `y` float unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`db_name`, `table_name`, `pdf_page_number`)
) TYPE=MyISAM COMMENT='Table coordinates for phpMyAdmin PDF output';

DROP TABLE IF EXISTS `pma_pdf_pages`;
CREATE TABLE `pma_pdf_pages` (
    `db_name` VARCHAR(64) NOT NULL DEFAULT '',
    `page_nr` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page_descr` VARCHAR(50) NOT NULL DEFAULT '',
    PRIMARY KEY (`page_nr`),
    KEY (`db_name`)
) TYPE=MyISAM COMMENT='PDF Relationpages for PMA';

DROP TABLE IF EXISTS `pma_column_info`;
CREATE TABLE `pma_column_info` (
    `id` INT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
    `db_name` VARCHAR(64) NOT NULL DEFAULT '',
    `table_name` VARCHAR(64) NOT NULL DEFAULT '',
    `column_name` VARCHAR(64) NOT NULL DEFAULT '',
    `comment` VARCHAR(255) NOT NULL DEFAULT '',
    `mimetype` VARCHAR(255) NOT NULL DEFAULT '',
    `transformation` VARCHAR(255) NOT NULL DEFAULT '',
    `transformation_options` VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `db_name` (`db_name`, `table_name`, `column_name`)
) TYPE=MyISAM COMMENT='Column Information for phpMyAdmin';

DROP TABLE IF EXISTS `pma_history`;
CREATE TABLE `pma_history` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(64) NOT NULL,
    `db` VARCHAR(64) NOT NULL,
    `table` VARCHAR(64) NOT NULL,
    `timevalue` TIMESTAMP NOT NULL,
    `sqlquery` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `username` (`username`, `db`, `table`, `timevalue`)
) TYPE=MyISAM COMMENT='SQL history';
