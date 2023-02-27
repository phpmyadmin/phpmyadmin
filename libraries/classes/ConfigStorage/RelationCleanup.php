<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Util;

use function sprintf;

/**
 * Set of functions used for cleaning up phpMyAdmin tables
 */
class RelationCleanup
{
    public function __construct(public DatabaseInterface $dbi, public Relation $relation)
    {
    }

    /**
     * Cleanup column related relation stuff
     *
     * @param string $db     database name
     * @param string $table  table name
     * @param string $column column name
     */
    public function column(string $db, string $table, string $column): void
    {
        $relationParameters = $this->relation->getRelationParameters();
        $columnCommentsFeature = $relationParameters->columnCommentsFeature;
        $displayFeature = $relationParameters->displayFeature;
        $relationFeature = $relationParameters->relationFeature;

        if ($columnCommentsFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s AND table_name = %s AND column_name = %s',
                Util::backquote($columnCommentsFeature->database),
                Util::backquote($columnCommentsFeature->columnInfo),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
                $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
                $this->dbi->quoteString($column, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($displayFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s AND table_name = %s AND display_field = %s',
                Util::backquote($displayFeature->database),
                Util::backquote($displayFeature->tableInfo),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
                $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
                $this->dbi->quoteString($column, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($relationFeature === null) {
            return;
        }

        $statement = sprintf(
            'DELETE FROM %s.%s WHERE master_db = %s AND master_table = %s AND master_field = %s',
            Util::backquote($relationFeature->database),
            Util::backquote($relationFeature->relation),
            $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($column, Connection::TYPE_CONTROL),
        );
        $this->dbi->queryAsControlUser($statement);

        $statement = sprintf(
            'DELETE FROM %s.%s WHERE foreign_db = %s AND foreign_table = %s AND foreign_field = %s',
            Util::backquote($relationFeature->database),
            Util::backquote($relationFeature->relation),
            $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($column, Connection::TYPE_CONTROL),
        );
        $this->dbi->queryAsControlUser($statement);
    }

    /**
     * Cleanup table related relation stuff
     *
     * @param string $db    database name
     * @param string $table table name
     */
    public function table(string $db, string $table): void
    {
        $relationParameters = $this->relation->getRelationParameters();
        $columnCommentsFeature = $relationParameters->columnCommentsFeature;
        $displayFeature = $relationParameters->displayFeature;
        $pdfFeature = $relationParameters->pdfFeature;
        $relationFeature = $relationParameters->relationFeature;
        $uiPreferencesFeature = $relationParameters->uiPreferencesFeature;
        $navigationItemsHidingFeature = $relationParameters->navigationItemsHidingFeature;

        if ($columnCommentsFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s AND table_name = %s',
                Util::backquote($columnCommentsFeature->database),
                Util::backquote($columnCommentsFeature->columnInfo),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
                $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($displayFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s AND table_name = %s',
                Util::backquote($displayFeature->database),
                Util::backquote($displayFeature->tableInfo),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
                $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($pdfFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s AND table_name = %s',
                Util::backquote($pdfFeature->database),
                Util::backquote($pdfFeature->tableCoords),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
                $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($relationFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE master_db = %s AND master_table = %s',
                Util::backquote($relationFeature->database),
                Util::backquote($relationFeature->relation),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
                $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);

            $statement = sprintf(
                'DELETE FROM %s.%s WHERE foreign_db = %s AND foreign_table = %s',
                Util::backquote($relationFeature->database),
                Util::backquote($relationFeature->relation),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
                $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($uiPreferencesFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s AND table_name = %s',
                Util::backquote($uiPreferencesFeature->database),
                Util::backquote($uiPreferencesFeature->tableUiPrefs),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
                $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($navigationItemsHidingFeature === null) {
            return;
        }

        $statement = sprintf(
            'DELETE FROM %s.%s WHERE db_name = %s AND (table_name = %s OR (item_name = %s AND item_type = \'table\'))',
            Util::backquote($navigationItemsHidingFeature->database),
            Util::backquote($navigationItemsHidingFeature->navigationHiding),
            $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
        );
        $this->dbi->queryAsControlUser($statement);
    }

    /**
     * Cleanup database related relation stuff
     *
     * @param string $db database name
     */
    public function database(string $db): void
    {
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->db === null) {
            return;
        }

        $columnCommentsFeature = $relationParameters->columnCommentsFeature;
        $bookmarkFeature = $relationParameters->bookmarkFeature;
        $displayFeature = $relationParameters->displayFeature;
        $pdfFeature = $relationParameters->pdfFeature;
        $relationFeature = $relationParameters->relationFeature;
        $uiPreferencesFeature = $relationParameters->uiPreferencesFeature;
        $navigationItemsHidingFeature = $relationParameters->navigationItemsHidingFeature;
        $savedQueryByExampleSearchesFeature = $relationParameters->savedQueryByExampleSearchesFeature;
        $centralColumnsFeature = $relationParameters->centralColumnsFeature;

        if ($columnCommentsFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s',
                Util::backquote($columnCommentsFeature->database),
                Util::backquote($columnCommentsFeature->columnInfo),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($bookmarkFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE dbase = %s',
                Util::backquote($bookmarkFeature->database),
                Util::backquote($bookmarkFeature->bookmark),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($displayFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s',
                Util::backquote($displayFeature->database),
                Util::backquote($displayFeature->tableInfo),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($pdfFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s',
                Util::backquote($pdfFeature->database),
                Util::backquote($pdfFeature->pdfPages),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);

            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s',
                Util::backquote($pdfFeature->database),
                Util::backquote($pdfFeature->tableCoords),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($relationFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE master_db = %s',
                Util::backquote($relationFeature->database),
                Util::backquote($relationFeature->relation),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);

            $statement = sprintf(
                'DELETE FROM %s.%s WHERE foreign_db = %s',
                Util::backquote($relationFeature->database),
                Util::backquote($relationFeature->relation),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($uiPreferencesFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s',
                Util::backquote($uiPreferencesFeature->database),
                Util::backquote($uiPreferencesFeature->tableUiPrefs),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($navigationItemsHidingFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s',
                Util::backquote($navigationItemsHidingFeature->database),
                Util::backquote($navigationItemsHidingFeature->navigationHiding),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($savedQueryByExampleSearchesFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE db_name = %s',
                Util::backquote($savedQueryByExampleSearchesFeature->database),
                Util::backquote($savedQueryByExampleSearchesFeature->savedSearches),
                $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($centralColumnsFeature === null) {
            return;
        }

        $statement = sprintf(
            'DELETE FROM %s.%s WHERE db_name = %s',
            Util::backquote($centralColumnsFeature->database),
            Util::backquote($centralColumnsFeature->centralColumns),
            $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
        );
        $this->dbi->queryAsControlUser($statement);
    }

    /**
     * Cleanup user related relation stuff
     *
     * @param string $username username
     */
    public function user(string $username): void
    {
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->db === null) {
            return;
        }

        $bookmarkFeature = $relationParameters->bookmarkFeature;
        $sqlHistoryFeature = $relationParameters->sqlHistoryFeature;
        $recentlyUsedTablesFeature = $relationParameters->recentlyUsedTablesFeature;
        $favoriteTablesFeature = $relationParameters->favoriteTablesFeature;
        $uiPreferencesFeature = $relationParameters->uiPreferencesFeature;
        $userPreferencesFeature = $relationParameters->userPreferencesFeature;
        $configurableMenusFeature = $relationParameters->configurableMenusFeature;
        $navigationItemsHidingFeature = $relationParameters->navigationItemsHidingFeature;
        $savedQueryByExampleSearchesFeature = $relationParameters->savedQueryByExampleSearchesFeature;
        $databaseDesignerSettingsFeature = $relationParameters->databaseDesignerSettingsFeature;

        if ($bookmarkFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE `user` = %s',
                Util::backquote($bookmarkFeature->database),
                Util::backquote($bookmarkFeature->bookmark),
                $this->dbi->quoteString($username, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($sqlHistoryFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE `username` = %s',
                Util::backquote($sqlHistoryFeature->database),
                Util::backquote($sqlHistoryFeature->history),
                $this->dbi->quoteString($username, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($recentlyUsedTablesFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE `username` = %s',
                Util::backquote($recentlyUsedTablesFeature->database),
                Util::backquote($recentlyUsedTablesFeature->recent),
                $this->dbi->quoteString($username, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($favoriteTablesFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE `username` = %s',
                Util::backquote($favoriteTablesFeature->database),
                Util::backquote($favoriteTablesFeature->favorite),
                $this->dbi->quoteString($username, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($uiPreferencesFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE `username` = %s',
                Util::backquote($uiPreferencesFeature->database),
                Util::backquote($uiPreferencesFeature->tableUiPrefs),
                $this->dbi->quoteString($username, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($userPreferencesFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE `username` = %s',
                Util::backquote($userPreferencesFeature->database),
                Util::backquote($userPreferencesFeature->userConfig),
                $this->dbi->quoteString($username, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($configurableMenusFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE `username` = %s',
                Util::backquote($configurableMenusFeature->database),
                Util::backquote($configurableMenusFeature->users),
                $this->dbi->quoteString($username, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($navigationItemsHidingFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE `username` = %s',
                Util::backquote($navigationItemsHidingFeature->database),
                Util::backquote($navigationItemsHidingFeature->navigationHiding),
                $this->dbi->quoteString($username, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($savedQueryByExampleSearchesFeature !== null) {
            $statement = sprintf(
                'DELETE FROM %s.%s WHERE `username` = %s',
                Util::backquote($savedQueryByExampleSearchesFeature->database),
                Util::backquote($savedQueryByExampleSearchesFeature->savedSearches),
                $this->dbi->quoteString($username, Connection::TYPE_CONTROL),
            );
            $this->dbi->queryAsControlUser($statement);
        }

        if ($databaseDesignerSettingsFeature === null) {
            return;
        }

        $statement = sprintf(
            'DELETE FROM %s.%s WHERE `username` = %s',
            Util::backquote($databaseDesignerSettingsFeature->database),
            Util::backquote($databaseDesignerSettingsFeature->designerSettings),
            $this->dbi->quoteString($username, Connection::TYPE_CONTROL),
        );
        $this->dbi->queryAsControlUser($statement);
    }
}
