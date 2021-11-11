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
     */
    public function column($db, $table, $column): void
    {
        $relationParameters = $this->relation->getRelationParameters();

        if ($relationParameters->commwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->columnInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\''
                . ' AND column_name = \'' . $this->dbi->escapeString($column)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->displaywork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->tableInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\''
                . ' AND display_field = \'' . $this->dbi->escapeString($column)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if (! $relationParameters->relwork) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($relationParameters->db)
            . '.' . Util::backquote($relationParameters->relation)
            . ' WHERE master_db  = \'' . $this->dbi->escapeString($db)
            . '\''
            . ' AND master_table = \'' . $this->dbi->escapeString($table)
            . '\''
            . ' AND master_field = \'' . $this->dbi->escapeString($column)
            . '\'';
        $this->relation->queryAsControlUser($remove_query);

        $remove_query = 'DELETE FROM '
            . Util::backquote($relationParameters->db)
            . '.' . Util::backquote($relationParameters->relation)
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
     */
    public function table($db, $table): void
    {
        $relationParameters = $this->relation->getRelationParameters();

        if ($relationParameters->commwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->columnInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->displaywork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->tableInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->pdfwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->tableCoords)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->relwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->relation)
                . ' WHERE master_db  = \'' . $this->dbi->escapeString($db)
                . '\''
                . ' AND master_table = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);

            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->relation)
                . ' WHERE foreign_db  = \'' . $this->dbi->escapeString($db)
                . '\''
                . ' AND foreign_table = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->uiprefswork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->tableUiprefs)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND table_name = \'' . $this->dbi->escapeString($table)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if (! $relationParameters->navwork) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($relationParameters->db)
            . '.' . Util::backquote($relationParameters->navigationhiding)
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
     */
    public function database($db): void
    {
        $relationParameters = $this->relation->getRelationParameters();

        if ($relationParameters->commwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->columnInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->bookmarkwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->bookmark)
                . ' WHERE dbase  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->displaywork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->tableInfo)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->pdfwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->pdfPages)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);

            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->tableCoords)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->relwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->relation)
                . ' WHERE master_db  = \''
                . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);

            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->relation)
                . ' WHERE foreign_db  = \'' . $this->dbi->escapeString($db)
                . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->uiprefswork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->tableUiprefs)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->navwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->navigationhiding)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->savedsearcheswork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->savedsearches)
                . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
            $this->relation->queryAsControlUser($remove_query);
        }

        if (! $relationParameters->centralcolumnswork) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($relationParameters->db)
            . '.' . Util::backquote($relationParameters->centralColumns)
            . ' WHERE db_name  = \'' . $this->dbi->escapeString($db) . '\'';
        $this->relation->queryAsControlUser($remove_query);
    }

    /**
     * Cleanup user related relation stuff
     *
     * @param string $username username
     */
    public function user($username): void
    {
        $relationParameters = $this->relation->getRelationParameters();

        if ($relationParameters->bookmarkwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->bookmark)
                . " WHERE `user`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->historywork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->history)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->recentwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->recent)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->favoritework) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->favorite)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->uiprefswork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->tableUiprefs)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->userconfigwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->userconfig)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->menuswork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->users)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->navwork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->navigationhiding)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if ($relationParameters->savedsearcheswork) {
            $remove_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->savedsearches)
                . " WHERE `username`  = '" . $this->dbi->escapeString($username)
                . "'";
            $this->relation->queryAsControlUser($remove_query);
        }

        if (! $relationParameters->designersettingswork) {
            return;
        }

        $remove_query = 'DELETE FROM '
            . Util::backquote($relationParameters->db)
            . '.' . Util::backquote($relationParameters->designerSettings)
            . " WHERE `username`  = '" . $this->dbi->escapeString($username)
            . "'";
        $this->relation->queryAsControlUser($remove_query);
    }
}
