#########################################################
#                                                       #
# SQL Commands to set up the pmadb as described in      #
# Documentation.txt.                                    #
#                                                       #
# This script expects the user pma to allready be       #
# existing. (if we would put a line here to create him  #
# too many users might just use this script and end     #
# up with having the same password for the controluser) #
#                                                       #
# Please dont forget to set up the tablenames in        #
# config.inc.php3                                       #
#                                                       #
#########################################################

CREATE DATABASE phpmyadmin;

GRANT     SELECT,INSERT,DELETE     ON    phpmyadmin.*    to
    'pma'@localhost;

CREATE TABLE `PMA_bookmark` (
    id int(11) DEFAULT '0' NOT NULL auto_increment,
    dbase varchar(255) NOT NULL,
    user varchar(255) NOT NULL,
    label varchar(255) NOT NULL,
    query text NOT NULL,
    PRIMARY KEY (id)
) TYPE=MyISAM COMMENT='Bookmarks';

CREATE TABLE `PMA_relation` (
    `master_db` varchar(64) NOT NULL default '',
    `master_table` varchar(64) NOT NULL default '',
    `master_field` varchar(64) NOT NULL default '',
    `foreign_db` varchar(64) NOT NULL default '',
    `foreign_table` varchar(64) NOT NULL default '',
    `foreign_field` varchar(64) NOT NULL default '',
    PRIMARY KEY (`master_db`, `master_table`,`master_field`),
    KEY foreign_field (foreign_db, foreign_table)
    ) TYPE=MyISAM COMMENT='Relation table';

CREATE TABLE `PMA_table_info` (
    `db_name` varchar(64) NOT NULL default '',
    `table_name` varchar(64) NOT NULL default '',
    `display_field` varchar(64) NOT NULL default '',
    PRIMARY KEY (`db_name`, `table_name`)
    ) TYPE=MyISAM COMMENT='Table information  for phpMyAdmin';

CREATE TABLE `PMA_table_coords` (
    `db_name` varchar(64) NOT NULL default '',
    `table_name` varchar(64) NOT NULL default '',
    `pdf_page_number` int NOT NULL default '0',
    `x` float unsigned NOT NULL default '0',
    `y` float unsigned NOT NULL default '0',
    PRIMARY KEY (`db_name`, `table_name`, `pdf_page_number`)
    ) TYPE=MyISAM COMMENT='Table coordinates for phpMyAdmin PDF output';

CREATE TABLE `PMA_pdf_pages` (
    `db_name` varchar(64) NOT NULL default '',
    `page_nr` int(10) unsigned NOT NULL auto_increment,
    `page_descr` varchar(50) NOT NULL default '',
    PRIMARY KEY (page_nr),
    KEY (db_name)
    ) TYPE=MyISAM COMMENT='PDF Relationpages for PMA';

CREATE TABLE `PMA_column_comments` (
    id int(5) unsigned NOT NULL auto_increment,
    db_name varchar(64) NOT NULL default '',
    table_name varchar(64) NOT NULL default '',
    column_name varchar(64) NOT NULL default '',
    comment varchar(255) NOT NULL default '',
    PRIMARY KEY (id),
    UNIQUE KEY db_name (db_name, table_name, column_name)
    ) TYPE=MyISAM COMMENT='Comments for Columns';