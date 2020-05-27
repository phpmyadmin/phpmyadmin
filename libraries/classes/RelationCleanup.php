<?php
/**
 * Set of functions used for cleaning up phpMyAdmin tables
 */

declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * PhpMyAdmin\RelationCleanup class
 */
class RelationCleanup
{
    /** @var Relation */
    public $relation;

    /** @var DatabaseInterface */
    public $dbi;

    /**
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Relation          $relation Relation object
     */
    public function __construct($dbi, Relation $relation)
    {
        $this->dbi = $dbi;
        $this->relation = $relation;
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
    public function column($db, $table, $column)
    {
        $cfgRelation = $this->relation->getRelationsParam();

        if ($cfgRelation['commwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['column_info'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\''
                . ' AND column_name = \'' . $this->dbi->escapeString($column)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['displaywork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['table_info'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\''
                . ' AND display_field = \'' . $this->dbi->escapeString($column)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if (! $cfgRelation['relwork']) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['relation'])
            . ' WHERE master_db  = \'' . $this->dbi->escapeString($db)
            . '\''
            . ' AND master_table = \'' . $this->dbi->escapeString($table)
            . '\''
            . ' AND master_field = \'' . $this->dbi->escapeString($column)
            . '\'';
        $this->relation->queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM '
            . Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['relation'])
            . ' WHERE foreign_db  = \'' . $this->dbi->escapeString($db)
            . '\''
            . ' AND foreign_table = \'' . $this->dbi->escapeString($table)
            . '\''
            . ' AND foreign_field = \'' . $this->dbi->escapeString($column)
            . '\'';
        $this->relation->queryAsControlUser($remove_query);
    }

    /**
     * Cleanup table related relation stuff
     *
     * @param string $db    database name
     * @param string $table table name
     *
     * @return void
     */
    public function table($db, $table)
    {
        $cfgRelation = $this->relation->getRelationsParam();

        if ($cfgRelation['commwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['column_info'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['displaywork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['table_info'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['pdfwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['table_coords'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['relwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['relation'])
                . ' WHERE master_db  = \'' . $this->dbi->escapeString($db)
                . '\''
                . ' AND master_table = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);

            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['relation'])
                . ' WHERE foreign_db  = \'' . $this->dbi->escapeString($db)
                . '\''
                . ' AND foreign_table = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['uiprefswork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['table_uiprefs'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if (! $cfgRelation['navwork']) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['navigationhiding'])
            . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
            . ' AND (table_name = \'' . $this->dbi->escapeString($table)
            . '\''
            . ' OR (item_name = \'' . $this->dbi->escapeString($table)
            . '\''
            . ' AND item_type = \'table\'))';
        $this->relation->queryAsControlUser($remove_query);
    }

    /**
     * Cleanup database related relation stuff
     *
     * @param string $db database name
     *
     * @return void
     */
    public function database($db)
    {
        $cfgRelation = $this->relation->getRelationsParam();

        if ($cfgRelation['commwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['column_info'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['bookmarkwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['bookmark'])
                . ' WHERE dbase  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['displaywork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['table_info'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['pdfwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['pdf_pages'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);

            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['table_coords'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['relwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['relation'])
                . ' WHERE master_db  = \''
                . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);

            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['relation'])
                . ' WHERE foreign_db  = \'' . $this->dbi->escapeString($db)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['uiprefswork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['table_uiprefs'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['navwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['navigationhiding'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['savedsearcheswork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['savedsearches'])
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if (! $cfgRelation['centralcolumnswork']) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['central_columns'])
            . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
        $this->relation->queryAsControlUser($remove_query);
    }

    /**
     * Cleanup user related relation stuff
     *
     * @param string $username username
     *
     * @return void
     */
    public function user($username)
    {
        $cfgRelation = $this->relation->getRelationsParam();

        if ($cfgRelation['bookmarkwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['bookmark'])
                . " WHERE `user`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['historywork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['history'])
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['recentwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['recent'])
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['favoritework']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['favorite'])
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['uiprefswork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['table_uiprefs'])
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['userconfigwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['userconfig'])
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['menuswork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['users'])
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['navwork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['navigationhiding'])
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($cfgRelation['savedsearcheswork']) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['savedsearches'])
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if (! $cfgRelation['designersettingswork']) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['designer_settings'])
            . " WHERE `username`  = '" . $this->dbi->escapeString($username)
            . "'";
        $this->relation->queryAsControlUser($remove_query);
    }
}
