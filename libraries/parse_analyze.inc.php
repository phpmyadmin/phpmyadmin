<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Parse and analyse a SQL query
 *
 * @package PhpMyAdmin
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

// for bug 780516: now that we use case insensitive preg_match
// or flags from the analyser, do not put back the reformatted query
// into $sql_query, to make this kind of query work without
// capitalizing keywords:
//
// CREATE TABLE SG_Persons (
//  id int(10) unsigned NOT NULL auto_increment,
//  first varchar(64) NOT NULL default '',
//  PRIMARY KEY  (`id`)
// )

// Fills some variables from the analysed SQL
// A table has to be created, renamed, dropped:
// the navigation panel should be reloaded
$reload = isset($analyzed_sql[0]['queryflags']['reload']);

// check for drop database
$drop_database = isset($analyzed_sql[0]['queryflags']['drop_database']);

// for the presence of EXPLAIN
$is_explain = isset($analyzed_sql[0]['queryflags']['is_explain']);

// for the presence of DELETE
$is_delete = isset($analyzed_sql[0]['queryflags']['is_delete']);

// for the presence of UPDATE, DELETE or INSERT|LOAD DATA|REPLACE
$is_affected = isset($analyzed_sql[0]['queryflags']['is_affected']);

// for the presence of REPLACE
$is_replace = isset($analyzed_sql[0]['queryflags']['is_replace']);

// for the presence of INSERT
$is_insert = isset($analyzed_sql[0]['queryflags']['is_insert']);

// for the presence of CHECK|ANALYZE|REPAIR|OPTIMIZE TABLE
$is_maint = isset($analyzed_sql[0]['queryflags']['is_maint']);

// for the presence of SHOW
$is_show = isset($analyzed_sql[0]['queryflags']['is_show']);

// for the presence of PROCEDURE ANALYSE
$is_analyse = isset($analyzed_sql[0]['queryflags']['is_analyse']);

// for the presence of INTO OUTFILE
$is_export = isset($analyzed_sql[0]['queryflags']['is_export']);

// for the presence of GROUP BY|HAVING|SELECT DISTINCT
$is_group = isset($analyzed_sql[0]['queryflags']['is_group']);

// for the presence of SUM|AVG|STD|STDDEV|MIN|MAX|BIT_OR|BIT_AND
$is_func = isset($analyzed_sql[0]['queryflags']['is_func']);

// for the presence of SELECT COUNT
$is_count = isset($analyzed_sql[0]['queryflags']['is_count']);

// check for a real SELECT ... FROM
$is_select = isset($analyzed_sql[0]['queryflags']['select_from']);

// check for CALL
// Since multiple query execution is anyway handled,
// ignore the WHERE clause of the first sql statement
// which might contain a phrase like 'call '
if (isset($analyzed_sql[0]['queryflags']['is_procedure'])
    && empty($analyzed_sql[0]['where_clause'])
) {
    $is_procedure = true;
} else {
    $is_procedure = false;
}

// aggregates all the results into one array
$analyzed_sql_results = array(
    "parsed_sql" => $parsed_sql,
    "analyzed_sql" => $analyzed_sql,
    "reload" => $reload,
    "drop_database" => $drop_database,
    "is_explain" => $is_explain,
    "is_delete" => $is_delete,
    "is_affected" => $is_affected,
    "is_replace" => $is_replace,
    "is_insert" => $is_insert,
    "is_maint" => $is_maint,
    "is_show" => $is_show,
    "is_analyse" => $is_analyse,
    "is_export" => $is_export,
    "is_group" => $is_group,
    "is_func" => $is_func,
    "is_count" => $is_count,
    "is_select" => $is_select,
    "is_procedure" => $is_procedure
);


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
        && strlen($analyzed_sql[0]['table_ref'][0]['db'])
    ) {
        $db    = $analyzed_sql[0]['table_ref'][0]['db'];
    } else {
        $db = $prev_db;
    }
    // Don't change reload, if we already decided to reload in import
    if (empty($reload) && empty($GLOBALS['is_ajax_request'])) {
        $reload  = ($db == $prev_db) ? 0 : 1;
    }
}
?>
