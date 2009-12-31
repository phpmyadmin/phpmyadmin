<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used for cleaning up phpMyAdmin tables
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
require_once './libraries/relation.lib.php';

/**
 * Cleanu column related relation stuff
 *
 * @uses PMA_getRelationsParam()
 * @uses PMA_backquote()
 * @uses PMA_sqlAddslashes()
 * @uses PMA_query_as_controluser()
 * @param string $db
 * @param string $table
 * @param string $column
 */
function PMA_relationsCleanupColumn($db, $table, $column)
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                    . ' AND column_name = \'' . PMA_sqlAddslashes($column) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                    . ' AND display_field = \'' . PMA_sqlAddslashes($column) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\''
                    . ' AND master_field = \'' . PMA_sqlAddslashes($column) . '\'';
        PMA_query_as_controluser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND foreign_table = \'' . PMA_sqlAddslashes($table) . '\''
                    . ' AND foreign_field = \'' . PMA_sqlAddslashes($column) . '\'';
        PMA_query_as_controluser($remove_query);
    }
}

/**
 * Cleanup table related relation stuff
 *
 * @uses PMA_getRelationsParam()
 * @uses PMA_backquote()
 * @uses PMA_sqlAddslashes()
 * @uses PMA_query_as_controluser()
 * @param string $db
 * @param string $table
 */
function PMA_relationsCleanupTable($db, $table)
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['pdfwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['designerwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['designer_coords'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\'';
        PMA_query_as_controluser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND foreign_table = \'' . PMA_sqlAddslashes($table) . '\'';
        PMA_query_as_controluser($remove_query);
    }
}

/**
 * Cleanup database related relation stuff
 *
 * @uses PMA_getRelationsParam()
 * @uses PMA_backquote()
 * @uses PMA_sqlAddslashes()
 * @uses PMA_query_as_controluser()
 * @param string $db
 */
function PMA_relationsCleanupDatabase($db)
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['bookmarkwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['bookmark'])
                    . ' WHERE dbase  = \'' . PMA_sqlAddslashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['pdfwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
        PMA_query_as_controluser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['designerwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['designer_coords'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
     }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\'';
        PMA_query_as_controluser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
    }
}

?>
