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
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddSlashes($table) . '\''
                    . ' AND column_name = \'' . PMA_sqlAddSlashes($column) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddSlashes($table) . '\''
                    . ' AND display_field = \'' . PMA_sqlAddSlashes($column) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . PMA_sqlAddSlashes($db) . '\''
                    . ' AND master_table = \'' . PMA_sqlAddSlashes($table) . '\''
                    . ' AND master_field = \'' . PMA_sqlAddSlashes($column) . '\'';
        PMA_query_as_controluser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . PMA_sqlAddSlashes($db) . '\''
                    . ' AND foreign_table = \'' . PMA_sqlAddSlashes($table) . '\''
                    . ' AND foreign_field = \'' . PMA_sqlAddSlashes($column) . '\'';
        PMA_query_as_controluser($remove_query);
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
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddSlashes($table) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddSlashes($table) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['pdfwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddSlashes($table) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['designerwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['designer_coords'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddSlashes($table) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . PMA_sqlAddSlashes($db) . '\''
                    . ' AND master_table = \'' . PMA_sqlAddSlashes($table) . '\'';
        PMA_query_as_controluser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . PMA_sqlAddSlashes($db) . '\''
                    . ' AND foreign_table = \'' . PMA_sqlAddSlashes($table) . '\'';
        PMA_query_as_controluser($remove_query);
    }
}

/**
 * Cleanup database related relation stuff
 *
 * @param string $db
 */
function PMA_relationsCleanupDatabase($db)
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['bookmarkwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['bookmark'])
                    . ' WHERE dbase  = \'' . PMA_sqlAddSlashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['pdfwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['pdf_pages'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\'';
        PMA_query_as_controluser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
    }

    if ($cfgRelation['designerwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['designer_coords'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddSlashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
     }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . PMA_sqlAddSlashes($db) . '\'';
        PMA_query_as_controluser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . PMA_sqlAddSlashes($db) . '\'';
        PMA_query_as_controluser($remove_query);
    }
}

?>
