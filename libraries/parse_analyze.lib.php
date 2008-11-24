<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
$GLOBALS['unparsed_sql'] = $sql_query;
$parsed_sql = PMA_SQP_parse($sql_query);
$analyzed_sql = PMA_SQP_analyze($parsed_sql);

// lem9: for bug 780516: now that we use case insensitive preg_match
// or flags from the analyser, do not put back the reformatted query
// into $sql_query, to make this kind of query work without
// capitalizing keywords:
//
// CREATE TABLE SG_Persons (
//  id int(10) unsigned NOT NULL auto_increment,
//  first varchar(64) NOT NULL default '',
//  PRIMARY KEY  (`id`)
// )

// check for a real SELECT ... FROM
$is_select = isset($analyzed_sql[0]['queryflags']['select_from']);

// If the query is a Select, extract the db and table names and modify
// $db and $table, to have correct page headers, links and left frame.
// db and table name may be enclosed with backquotes, db is optionnal,
// query may contain aliases.

/**
 * @todo if there are more than one table name in the Select:
 * - do not extract the first table name
 * - do not show a table name in the page header
 * - do not display the sub-pages links)
 */
if ($is_select) {
    $prev_db = $db;
    if (isset($analyzed_sql[0]['table_ref'][0]['table_true_name'])) {
        $table = $analyzed_sql[0]['table_ref'][0]['table_true_name'];
    }
    if (isset($analyzed_sql[0]['table_ref'][0]['db'])
      && strlen($analyzed_sql[0]['table_ref'][0]['db'])) {
        $db    = $analyzed_sql[0]['table_ref'][0]['db'];
    } else {
        $db = $prev_db;
    }
    // Nijel: don't change reload, if we already decided to reload in import
    if (empty($reload)) {
        $reload  = ($db == $prev_db) ? 0 : 1;
    }
}
?>
