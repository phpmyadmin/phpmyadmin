<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Set of functions used for cleaning up phpMyAdmin tables
 */


require_once('./libraries/relation.lib.php');
$cfgRelation = PMA_getRelationsParam();

function PMA_relationsCleanupColumn($db, $table, $column) {
    global $cfgRelation;
    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                    . ' AND column_name = \'' . PMA_sqlAddslashes(urldecode($column)) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\''
                    . ' AND display_field = \'' . PMA_sqlAddslashes(urldecode($column)) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\''
                    . ' AND master_field = \'' . PMA_sqlAddslashes(urldecode($column)) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND foreign_table = \'' . PMA_sqlAddslashes($table) . '\''
                    . ' AND foreign_field = \'' . PMA_sqlAddslashes(urldecode($column)) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }
}

function PMA_relationsCleanupTable($db, $table) {
    global $cfgRelation;

    if ($cfgRelation['commwork']) {
            $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['column_info'])
                        . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                        . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
            $rmv_rs    = PMA_query_as_cu($remove_query);
            unset($rmv_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }

    if ($cfgRelation['pdfwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\''
                    . ' AND foreign_table = \'' . PMA_sqlAddslashes($table) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }
}

function PMA_relationsCleanupDatabase($db) {
    global $cfgRelation;

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['column_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }

    if ($cfgRelation['bookmarkwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['bookmark'])
                    . ' WHERE dbase  = \'' . PMA_sqlAddslashes($db) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_info'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }

    if ($cfgRelation['pdfwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['pdf_pages'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_coords'])
                    . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);

        $remove_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                    . ' WHERE foreign_db  = \'' . PMA_sqlAddslashes($db) . '\'';
        $rmv_rs    = PMA_query_as_cu($remove_query);
        unset($rmv_query);
    }
}

?>
