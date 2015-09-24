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
 * Cleanup column related relation stuff
 *
 * @param string $db     database name
 * @param string $table  table name
 * @param string $column column name
 *
 * @return void
 */
function PMA_relationsCleanupColumn($db, $table, $column)
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['column_info'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\''
            . ' AND column_name = \'' . PMA_Util::sqlAddSlashes($column) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['table_info'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\''
            . ' AND display_field = \'' . PMA_Util::sqlAddSlashes($column) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['relation'])
            . ' WHERE master_db  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND master_table = \'' . PMA_Util::sqlAddSlashes($table) . '\''
            . ' AND master_field = \'' . PMA_Util::sqlAddSlashes($column) . '\'';
        PMA_queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['relation'])
            . ' WHERE foreign_db  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND foreign_table = \'' . PMA_Util::sqlAddSlashes($table) . '\''
            . ' AND foreign_field = \'' . PMA_Util::sqlAddSlashes($column) . '\'';
        PMA_queryAsControlUser($remove_query);
    }
}

/**
 * Cleanup table related relation stuff
 *
 * @param string $db    database name
 * @param string $table table name
 *
 * @return void
 */
function PMA_relationsCleanupTable($db, $table)
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['column_info'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['table_info'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['pdfwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['table_coords'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['relation'])
            . ' WHERE master_db  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND master_table = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['relation'])
            . ' WHERE foreign_db  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND foreign_table = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['uiprefswork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['table_uiprefs'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['navwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['navigationhiding'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND (table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\''
            . ' OR (item_name = \'' . PMA_Util::sqlAddSlashes($table) . '\''
            . ' AND item_type = \'table\'))';
        PMA_queryAsControlUser($remove_query);
    }
}

/**
 * Cleanup database related relation stuff
 *
 * @param string $db database name
 *
 * @return void
 */
function PMA_relationsCleanupDatabase($db)
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['commwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['column_info'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['bookmarkwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['bookmark'])
            . ' WHERE dbase  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['displaywork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['table_info'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['pdfwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['pdf_pages'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['table_coords'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['relwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['relation'])
            . ' WHERE master_db  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['relation'])
            . ' WHERE foreign_db  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['uiprefswork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['table_uiprefs'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['navwork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['navigationhiding'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['savedsearcheswork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['savedsearches'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['centralcolumnswork']) {
        $remove_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['central_columns'])
            . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
        PMA_queryAsControlUser($remove_query);
    }
}

/**
 * Cleanup user related relation stuff
 *
 * @param string $username username
 *
 * @return void
 */
function PMA_relationsCleanupUser($username)
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['bookmarkwork']) {
        $remove_query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['bookmark'])
            . " WHERE `user`  = '" . PMA_Util::sqlAddSlashes($username) . "'";
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['historywork']) {
        $remove_query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['history'])
            . " WHERE `username`  = '" . PMA_Util::sqlAddSlashes($username) . "'";
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['recentwork']) {
        $remove_query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['recent'])
            . " WHERE `username`  = '" . PMA_Util::sqlAddSlashes($username) . "'";
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['favoritework']) {
        $remove_query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['favorite'])
            . " WHERE `username`  = '" . PMA_Util::sqlAddSlashes($username) . "'";
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['uiprefswork']) {
        $remove_query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['table_uiprefs'])
            . " WHERE `username`  = '" . PMA_Util::sqlAddSlashes($username) . "'";
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['userconfigwork']) {
        $remove_query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['userconfig'])
            . " WHERE `username`  = '" . PMA_Util::sqlAddSlashes($username) . "'";
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['menuswork']) {
        $remove_query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['users'])
            . " WHERE `username`  = '" . PMA_Util::sqlAddSlashes($username) . "'";
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['navwork']) {
        $remove_query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['navigationhiding'])
            . " WHERE `username`  = '" . PMA_Util::sqlAddSlashes($username) . "'";
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['savedsearcheswork']) {
        $remove_query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['savedsearches'])
            . " WHERE `username`  = '" . PMA_Util::sqlAddSlashes($username) . "'";
        PMA_queryAsControlUser($remove_query);
    }

    if ($cfgRelation['designersettingswork']) {
        $remove_query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['designer_settings'])
            . " WHERE `username`  = '" . PMA_Util::sqlAddSlashes($username) . "'";
        PMA_queryAsControlUser($remove_query);
    }
}

