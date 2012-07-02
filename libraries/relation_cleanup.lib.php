<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used for cleaning up phpMyAdmin tables
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Cleanu column related relation stuff
 *
 * @param string $db
 * @param string $table
 * @param string $column
 */
function PMA_relationsCleanupColumn($db, $table, $column)
{
    
    $common_functions = PMA_CommonFunctions::getInstance();
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . $common_functions->sqlAddSlashes($table) . '\''
                    . ' AND column_name = \'' . $common_functions->sqlAddSlashes($column) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . $common_functions->sqlAddSlashes($table) . '\''
                    . ' AND display_field = \'' . $common_functions->sqlAddSlashes($column) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                    . ' AND master_table = \'' . $common_functions->sqlAddSlashes($table) . '\''
                    . ' AND master_field = \'' . $common_functions->sqlAddSlashes($column) . '\'';
        PMA_queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                    . ' AND foreign_table = \'' . $common_functions->sqlAddSlashes($table) . '\''
                    . ' AND foreign_field = \'' . $common_functions->sqlAddSlashes($column) . '\'';
        PMA_queryAsControlUser($remove_query);
    }
}

/**
 * Cleanup table related relation stuff
 *
 * @param string $db
 * @param string $table
 */
function PMA_relationsCleanupTable($db, $table)
{
    
    $common_functions = PMA_CommonFunctions::getInstance();
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . $common_functions->sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . $common_functions->sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['pdfwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . $common_functions->sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['designerwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['designer_coords'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . $common_functions->sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                    . ' AND master_table = \'' . $common_functions->sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . $common_functions->sqlAddSlashes($db) . '\''
                    . ' AND foreign_table = \'' . $common_functions->sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);
    }
}

/**
 * Cleanup database related relation stuff
 *
 * @param string $db
 */
function PMA_relationsCleanupDatabase($db)
{
    
    $common_functions = PMA_CommonFunctions::getInstance();
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['bookmarkwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['bookmark'])
                    . ' WHERE dbase  = \'' . $common_functions->sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['pdfwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['pdf_pages'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['designerwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['designer_coords'])
                    . ' WHERE db_name  = \'' . $common_functions->sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . $common_functions->sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM ' . $common_functions->backquote($cfgRelation['db']) . '.' . $common_functions->backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . $common_functions->sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }
}

?>
