<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\PdfFeature;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\InternalRelations;
use PhpMyAdmin\Message;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Utils\ForeignKey;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\TypeClass;
use PhpMyAdmin\Util;
use PhpMyAdmin\Version;

use function __;
use function array_fill_keys;
use function array_keys;
use function array_search;
use function asort;
use function bin2hex;
use function count;
use function explode;
use function file_get_contents;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_string;
use function ksort;
use function mb_check_encoding;
use function mb_strlen;
use function mb_substr;
use function natcasesort;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_replace;
use function strnatcasecmp;
use function strtolower;
use function trim;
use function uksort;
use function usort;

use const SQL_DIR;

/**
 * Set of functions used with the relation and PDF feature
 */
class Relation
{
    private static RelationParameters|null $cache = null;
    private readonly Config $config;

    public function __construct(public DatabaseInterface $dbi, Config|null $config = null)
    {
        $this->config = $config ?? Config::getInstance();
    }

    public function getRelationParameters(): RelationParameters
    {
        if (self::$cache === null) {
            self::$cache = RelationParameters::fromArray($this->checkRelationsParam());
        }

        return self::$cache;
    }

    /**
     * @param array<string, bool|string|null> $relationParams
     *
     * @return array<string, bool|string|null>
     */
    private function checkTableAccess(array $relationParams): array
    {
        if (isset($relationParams[RelationParameters::RELATION], $relationParams[RelationParameters::TABLE_INFO])) {
            if ($this->canAccessStorageTable((string) $relationParams[RelationParameters::TABLE_INFO])) {
                $relationParams[RelationParameters::DISPLAY_WORK] = true;
            }
        }

        if (isset($relationParams[RelationParameters::TABLE_COORDS], $relationParams[RelationParameters::PDF_PAGES])) {
            if ($this->canAccessStorageTable((string) $relationParams[RelationParameters::TABLE_COORDS])) {
                if ($this->canAccessStorageTable((string) $relationParams[RelationParameters::PDF_PAGES])) {
                    $relationParams[RelationParameters::PDF_WORK] = true;
                }
            }
        }

        if (isset($relationParams[RelationParameters::COLUMN_INFO])) {
            if ($this->canAccessStorageTable((string) $relationParams[RelationParameters::COLUMN_INFO])) {
                $relationParams[RelationParameters::COMM_WORK] = true;
                // phpMyAdmin 4.3+
                // Check for input transformations upgrade.
                $relationParams[RelationParameters::MIME_WORK] = $this->tryUpgradeTransformations();
            }
        }

        if (isset($relationParams[RelationParameters::USERS], $relationParams[RelationParameters::USER_GROUPS])) {
            if ($this->canAccessStorageTable((string) $relationParams[RelationParameters::USERS])) {
                if ($this->canAccessStorageTable((string) $relationParams[RelationParameters::USER_GROUPS])) {
                    $relationParams[RelationParameters::MENUS_WORK] = true;
                }
            }
        }

        $settings = [
            RelationParameters::EXPORT_TEMPLATES => RelationParameters::EXPORT_TEMPLATES_WORK,
            RelationParameters::DESIGNER_SETTINGS => RelationParameters::DESIGNER_SETTINGS_WORK,
            RelationParameters::CENTRAL_COLUMNS => RelationParameters::CENTRAL_COLUMNS_WORK,
            RelationParameters::SAVED_SEARCHES => RelationParameters::SAVED_SEARCHES_WORK,
            RelationParameters::NAVIGATION_HIDING => RelationParameters::NAV_WORK,
            RelationParameters::BOOKMARK => RelationParameters::BOOKMARK_WORK,
            RelationParameters::USER_CONFIG => RelationParameters::USER_CONFIG_WORK,
            RelationParameters::TRACKING => RelationParameters::TRACKING_WORK,
            RelationParameters::TABLE_UI_PREFS => RelationParameters::UI_PREFS_WORK,
            RelationParameters::FAVORITE => RelationParameters::FAVORITE_WORK,
            RelationParameters::RECENT => RelationParameters::RECENT_WORK,
            RelationParameters::HISTORY => RelationParameters::HISTORY_WORK,
            RelationParameters::RELATION => RelationParameters::REL_WORK,
        ];

        foreach ($settings as $setingName => $worksKey) {
            if (! isset($relationParams[$setingName])) {
                continue;
            }

            if (! $this->canAccessStorageTable((string) $relationParams[$setingName])) {
                continue;
            }

            $relationParams[$worksKey] = true;
        }

        return $relationParams;
    }

    /**
     * @param array<string, bool|string|null> $relationParams
     *
     * @return array<string, bool|string|null>|null
     */
    private function fillRelationParamsWithTableNames(array $relationParams): array|null
    {
        if ($this->arePmadbTablesAllDisabled()) {
            return null;
        }

        $tables = $this->dbi->getTables($this->config->selectedServer['pmadb'], ConnectionType::ControlUser);
        if ($tables === []) {
            return null;
        }

        foreach ($tables as $table) {
            if ($table === $this->config->selectedServer['bookmarktable']) {
                $relationParams[RelationParameters::BOOKMARK] = $table;
            } elseif ($table === $this->config->selectedServer['relation']) {
                $relationParams[RelationParameters::RELATION] = $table;
            } elseif ($table === $this->config->selectedServer['table_info']) {
                $relationParams[RelationParameters::TABLE_INFO] = $table;
            } elseif ($table === $this->config->selectedServer['table_coords']) {
                $relationParams[RelationParameters::TABLE_COORDS] = $table;
            } elseif ($table === $this->config->selectedServer['column_info']) {
                $relationParams[RelationParameters::COLUMN_INFO] = $table;
            } elseif ($table === $this->config->selectedServer['pdf_pages']) {
                $relationParams[RelationParameters::PDF_PAGES] = $table;
            } elseif ($table === $this->config->selectedServer['history']) {
                $relationParams[RelationParameters::HISTORY] = $table;
            } elseif ($table === $this->config->selectedServer['recent']) {
                $relationParams[RelationParameters::RECENT] = $table;
            } elseif ($table === $this->config->selectedServer['favorite']) {
                $relationParams[RelationParameters::FAVORITE] = $table;
            } elseif ($table === $this->config->selectedServer['table_uiprefs']) {
                $relationParams[RelationParameters::TABLE_UI_PREFS] = $table;
            } elseif ($table === $this->config->selectedServer['tracking']) {
                $relationParams[RelationParameters::TRACKING] = $table;
            } elseif ($table === $this->config->selectedServer['userconfig']) {
                $relationParams[RelationParameters::USER_CONFIG] = $table;
            } elseif ($table === $this->config->selectedServer['users']) {
                $relationParams[RelationParameters::USERS] = $table;
            } elseif ($table === $this->config->selectedServer['usergroups']) {
                $relationParams[RelationParameters::USER_GROUPS] = $table;
            } elseif ($table === $this->config->selectedServer['navigationhiding']) {
                $relationParams[RelationParameters::NAVIGATION_HIDING] = $table;
            } elseif ($table === $this->config->selectedServer['savedsearches']) {
                $relationParams[RelationParameters::SAVED_SEARCHES] = $table;
            } elseif ($table === $this->config->selectedServer['central_columns']) {
                $relationParams[RelationParameters::CENTRAL_COLUMNS] = $table;
            } elseif ($table === $this->config->selectedServer['designer_settings']) {
                $relationParams[RelationParameters::DESIGNER_SETTINGS] = $table;
            } elseif ($table === $this->config->selectedServer['export_templates']) {
                $relationParams[RelationParameters::EXPORT_TEMPLATES] = $table;
            }
        }

        return $relationParams;
    }

    /**
     * Defines the relation parameters for the current user
     * just a copy of the functions used for relations ;-)
     * but added some stuff to check what will work
     *
     * @return array<string, bool|string|null> the relation parameters for the current user
     */
    private function checkRelationsParam(): array
    {
        $workToTable = [
            RelationParameters::REL_WORK => 'relation',
            RelationParameters::DISPLAY_WORK => ['relation', 'table_info'],
            RelationParameters::BOOKMARK_WORK => 'bookmarktable',
            RelationParameters::PDF_WORK => ['table_coords', 'pdf_pages'],
            RelationParameters::COMM_WORK => 'column_info',
            RelationParameters::MIME_WORK => 'column_info',
            RelationParameters::HISTORY_WORK => 'history',
            RelationParameters::RECENT_WORK => 'recent',
            RelationParameters::FAVORITE_WORK => 'favorite',
            RelationParameters::UI_PREFS_WORK => 'table_uiprefs',
            RelationParameters::TRACKING_WORK => 'tracking',
            RelationParameters::USER_CONFIG_WORK => 'userconfig',
            RelationParameters::MENUS_WORK => ['users', 'usergroups'],
            RelationParameters::NAV_WORK => 'navigationhiding',
            RelationParameters::SAVED_SEARCHES_WORK => 'savedsearches',
            RelationParameters::CENTRAL_COLUMNS_WORK => 'central_columns',
            RelationParameters::DESIGNER_SETTINGS_WORK => 'designer_settings',
            RelationParameters::EXPORT_TEMPLATES_WORK => 'export_templates',
        ];

        $relationParams = array_fill_keys(array_keys($workToTable), false);

        $relationParams[RelationParameters::VERSION] = Version::VERSION;
        $relationParams[RelationParameters::ALL_WORKS] = false;
        $relationParams[RelationParameters::USER] = null;
        $relationParams[RelationParameters::DATABASE] = null;

        if (
            Current::$server === 0
            || $this->config->selectedServer['pmadb'] === ''
            || ! $this->dbi->selectDb($this->config->selectedServer['pmadb'], ConnectionType::ControlUser)
        ) {
            $this->config->selectedServer['pmadb'] = '';

            return $relationParams;
        }

        $relationParams[RelationParameters::USER] = $this->config->selectedServer['user'];
        $relationParams[RelationParameters::DATABASE] = $this->config->selectedServer['pmadb'];

        $relationParamsFilled = $this->fillRelationParamsWithTableNames($relationParams);

        if ($relationParamsFilled === null) {
            return $relationParams;
        }

        $relationParams = $this->checkTableAccess($relationParamsFilled);

        $allWorks = true;
        foreach ($workToTable as $work => $table) {
            if ($relationParams[$work]) {
                continue;
            }

            if (is_string($table)) {
                if (isset($this->config->selectedServer[$table]) && $this->config->selectedServer[$table] !== false) {
                    $allWorks = false;
                    break;
                }
            } else {
                $oneNull = false;
                foreach ($table as $t) {
                    if (isset($this->config->selectedServer[$t]) && $this->config->selectedServer[$t] === false) {
                        $oneNull = true;
                        break;
                    }
                }

                if (! $oneNull) {
                    $allWorks = false;
                    break;
                }
            }
        }

        $relationParams[RelationParameters::ALL_WORKS] = $allWorks;

        return $relationParams;
    }

    /**
     * Check if the table is accessible
     *
     * @param string $tableDbName The table or table.db
     */
    public function canAccessStorageTable(string $tableDbName): bool
    {
        $result = $this->dbi->tryQueryAsControlUser('SELECT NULL FROM ' . Util::backquote($tableDbName) . ' LIMIT 0');

        return $result !== false;
    }

    /**
     * Check whether column_info table input transformation
     * upgrade is required and try to upgrade silently
     */
    public function tryUpgradeTransformations(): bool
    {
        // From 4.3, new input oriented transformation feature was introduced.
        // Check whether column_info table has input transformation columns
        $newCols = ['input_transformation', 'input_transformation_options'];
        $query = 'SHOW COLUMNS FROM '
            . Util::backquote($this->config->selectedServer['pmadb'])
            . '.' . Util::backquote($this->config->selectedServer['column_info'])
            . ' WHERE Field IN (\'' . implode('\', \'', $newCols) . '\')';
        $result = $this->dbi->tryQueryAsControlUser($query);
        if ($result) {
            $rows = $result->numRows();
            unset($result);
            // input transformations are present
            // no need to upgrade
            if ($rows === 2) {
                return true;

                // try silent upgrade without disturbing the user
            }

            // read upgrade query file
            $query = @file_get_contents(SQL_DIR . 'upgrade_column_info_4_3_0+.sql');
            // replace database name from query to with set in config.inc.php
            // replace pma__column_info table name from query
            // to with set in config.inc.php
            $query = str_replace(
                ['`phpmyadmin`', '`pma__column_info`'],
                [
                    Util::backquote($this->config->selectedServer['pmadb']),
                    Util::backquote($this->config->selectedServer['column_info']),
                ],
                (string) $query,
            );
            $this->dbi->tryMultiQuery($query, ConnectionType::ControlUser);
            // skips result sets of query as we are not interested in it
            /** @infection-ignore-all */
            do {
                $hasResult = $this->dbi->nextResult(ConnectionType::ControlUser);
            } while ($hasResult !== false);

            $error = $this->dbi->getError(ConnectionType::ControlUser);

            // return true if no error exists otherwise false
            return $error === '';
        }

        // some failure, either in upgrading or something else
        // make some noise, time to wake up user.
        return false;
    }

    /**
     * Gets all Relations to foreign tables for a given table or
     * optionally a given column in a table
     *
     * @param string $db     the name of the db to check for
     * @param string $table  the name of the table to check for
     * @param string $column the name of the column to check for
     */
    public function getForeigners(string $db, string $table, string $column = ''): Foreigners
    {
        return new Foreigners(
            $this->getForeignersInternal($db, $table, $column),
            $this->getForeignKeysData($db, $table),
        );
    }

    /**
     * Gets all Relations to foreign tables for a given table or
     * optionally a given column in a table
     *
     * @return array<array<string|null>>
     */
    public function getForeignersInternal(string $db, string $table, string $column = ''): array
    {
        $relationFeature = $this->getRelationParameters()->relationFeature;
        $foreign = [];

        if ($relationFeature !== null) {
            $relQuery = 'SELECT `master_field`, `foreign_db`, '
                . '`foreign_table`, `foreign_field`'
                . ' FROM ' . Util::backquote($relationFeature->database)
                . '.' . Util::backquote($relationFeature->relation)
                . ' WHERE `master_db` = ' . $this->dbi->quoteString($db)
                . ' AND `master_table` = ' . $this->dbi->quoteString($table);
            if ($column !== '') {
                $relQuery .= ' AND `master_field` = ' . $this->dbi->quoteString($column);
            }

            $foreign = $this->dbi->fetchResult($relQuery, 'master_field', null, ConnectionType::ControlUser);
        }

        /**
         * Emulating relations for some information_schema tables
         */
        if (in_array(strtolower($db), ['information_schema', 'mysql'], true)) {
            $internalRelations = strtolower($db) === 'information_schema'
                ? InternalRelations::INFORMATION_SCHEMA
                : InternalRelations::MYSQL;

            if (isset($internalRelations[$table])) {
                foreach ($internalRelations[$table] as $field => $relations) {
                    if (($column !== '' && $column !== $field) || isset($foreign[$field])) {
                        continue;
                    }

                    $foreign[$field] = $relations;
                }
            }
        }

        return $foreign;
    }

    /** @return list<ForeignKey> */
    public function getForeignKeysData(string $db, string $table): array
    {
        if ($table === '') {
            return [];
        }

        $tableObj = new Table($table, $db, $this->dbi);
        $showCreateTable = $tableObj->showCreate();
        if ($showCreateTable !== '') {
            $parser = new Parser($showCreateTable);
            $stmt = $parser->statements[0];
            if ($stmt instanceof CreateStatement) {
                return $stmt->getForeignKeys();
            }
        }

        return [];
    }

    /**
     * Gets the display field of a table
     *
     * @param string $db    the name of the db to check for
     * @param string $table the name of the table to check for
     *
     * @return string field name
     */
    public function getDisplayField(string $db, string $table): string
    {
        $displayFeature = $this->getRelationParameters()->displayFeature;

        /**
         * Try to fetch the display field from DB.
         */
        if ($displayFeature !== null) {
            $dispQuery = 'SELECT `display_field`'
                    . ' FROM ' . Util::backquote($displayFeature->database)
                    . '.' . Util::backquote($displayFeature->tableInfo)
                    . ' WHERE `db_name` = ' . $this->dbi->quoteString($db)
                    . ' AND `table_name` = ' . $this->dbi->quoteString($table);

            $displayField = $this->dbi->fetchValue($dispQuery, 0, ConnectionType::ControlUser);
            if (is_string($displayField)) {
                return $displayField;
            }
        }

        /**
         * Emulating the display field for some information_schema tables.
         */
        if ($db === 'information_schema') {
            switch ($table) {
                case 'CHARACTER_SETS':
                    return 'DESCRIPTION';

                case 'TABLES':
                    return 'TABLE_COMMENT';
            }
        }

        /**
         * Pick first char field
         */
        $columns = $this->dbi->getColumns($db, $table);
        foreach ($columns as $column) {
            $columnType = preg_replace('@(\(.*)|(\s/.*)@s', '', $column->type);
            if ($this->dbi->types->getTypeClass($columnType) === TypeClass::Char) {
                return $column->field;
            }
        }

        return '';
    }

    /**
     * Gets the comments for all columns of a table or the db itself
     *
     * @param string $db    the name of the db to check for
     * @param string $table the name of the table to check for
     *
     * @return string[]    [column_name] = comment
     */
    public function getComments(string $db, string $table = ''): array
    {
        if ($table === '') {
            return [$this->getDbComment($db)];
        }

        $comments = [];

        // MySQL native column comments
        $columns = $this->dbi->getColumns($db, $table);
        foreach ($columns as $column) {
            if ($column->comment === '') {
                continue;
            }

            $comments[$column->field] = $column->comment;
        }

        return $comments;
    }

    /**
     * Gets the comment for a db
     *
     * @param string $db the name of the db to check for
     */
    public function getDbComment(string $db): string
    {
        $columnCommentsFeature = $this->getRelationParameters()->columnCommentsFeature;
        if ($columnCommentsFeature !== null) {
            // pmadb internal db comment
            $comQry = 'SELECT `comment`'
                    . ' FROM ' . Util::backquote($columnCommentsFeature->database)
                    . '.' . Util::backquote($columnCommentsFeature->columnInfo)
                    . ' WHERE db_name = ' . $this->dbi->quoteString($db, ConnectionType::ControlUser)
                    . ' AND table_name  = \'\''
                    . ' AND column_name = \'(db_comment)\'';
            $comRs = $this->dbi->tryQueryAsControlUser($comQry);

            if ($comRs && $comRs->numRows() > 0) {
                $row = $comRs->fetchAssoc();

                return (string) $row['comment'];
            }
        }

        return '';
    }

    /**
     * Set a database comment to a certain value.
     *
     * @param string $db      the name of the db
     * @param string $comment the value of the column
     */
    public function setDbComment(string $db, string $comment = ''): bool
    {
        $columnCommentsFeature = $this->getRelationParameters()->columnCommentsFeature;
        if ($columnCommentsFeature === null) {
            return false;
        }

        if ($comment !== '') {
            $updQuery = 'INSERT INTO '
                . Util::backquote($columnCommentsFeature->database) . '.'
                . Util::backquote($columnCommentsFeature->columnInfo)
                . ' (`db_name`, `table_name`, `column_name`, `comment`)'
                . ' VALUES ('
                . $this->dbi->quoteString($db, ConnectionType::ControlUser)
                . ", '', '(db_comment)', "
                . $this->dbi->quoteString($comment, ConnectionType::ControlUser)
                . ') '
                . ' ON DUPLICATE KEY UPDATE '
                . '`comment` = ' . $this->dbi->quoteString($comment, ConnectionType::ControlUser);
        } else {
            $updQuery = 'DELETE FROM '
                . Util::backquote($columnCommentsFeature->database) . '.'
                . Util::backquote($columnCommentsFeature->columnInfo)
                . ' WHERE `db_name`     = ' . $this->dbi->quoteString($db, ConnectionType::ControlUser)
                . '
                    AND `table_name`  = \'\'
                    AND `column_name` = \'(db_comment)\'';
        }

        return (bool) $this->dbi->queryAsControlUser($updQuery);
    }

    /**
     * Prepares the dropdown for one mode
     *
     * @param array<string|null> $foreign the keys and values for foreigns
     * @param string             $data    the current data of the dropdown
     * @param string             $mode    the needed mode
     *
     * @return string[] the <option value=""><option>s
     */
    public function buildForeignDropdown(array $foreign, string $data, string $mode): array
    {
        $reloptions = [];

        // id-only is a special mode used when no foreign display column
        // is available
        if ($mode === 'id-content' || $mode === 'id-only') {
            // sort for id-content
            if ($this->config->settings['NaturalOrder']) {
                uksort($foreign, strnatcasecmp(...));
            } else {
                ksort($foreign);
            }
        } elseif ($mode === 'content-id') {
            // sort for content-id
            if ($this->config->settings['NaturalOrder']) {
                natcasesort($foreign);
            } else {
                asort($foreign);
            }
        }

        foreach ($foreign as $key => $value) {
            $key = (string) $key;
            $value = (string) $value;

            if (
                mb_check_encoding($key, 'utf-8')
                && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', $key) !== 1
            ) {
                $selected = $key === $data;
                // show as text if it's valid utf-8
                $key = htmlspecialchars($key);
            } else {
                $key = '0x' . bin2hex($key);
                if (str_contains($data, '0x')) {
                    $selected = $key === trim($data);
                } else {
                    $selected = $key === '0x' . $data;
                }
            }

            if (
                mb_check_encoding($value, 'utf-8')
                && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', $value) !== 1
            ) {
                if (mb_strlen($value) <= $this->config->settings['LimitChars']) {
                    // show as text if it's valid utf-8
                    $value = htmlspecialchars($value);
                } else {
                    // show as truncated text if it's valid utf-8
                    $value = htmlspecialchars(
                        mb_substr(
                            $value,
                            0,
                            $this->config->settings['LimitChars'],
                        ) . '...',
                    );
                }
            } else {
                $value = '0x' . bin2hex($value);
            }

            $reloption = '<option value="' . $key . '"';

            if ($selected) {
                $reloption .= ' selected';
            }

            if ($mode === 'content-id') {
                $reloptions[] = $reloption . '>'
                    . $value . '&nbsp;-&nbsp;' . $key . '</option>';
            } elseif ($mode === 'id-content') {
                $reloptions[] = $reloption . '>'
                    . $key . '&nbsp;-&nbsp;' . $value . '</option>';
            } elseif ($mode === 'id-only') {
                $reloptions[] = $reloption . '>'
                    . $key . '</option>';
            }
        }

        return $reloptions;
    }

    /**
     * Outputs dropdown with values of foreign fields
     *
     * @param list<array<array-key, string|null>> $dispRow
     *
     * @return string   the <option value=""><option>s
     */
    public function foreignDropdown(
        array $dispRow,
        string $foreignField,
        string $foreignDisplay,
        string $data,
        int|null $maxNumberOfItems = null,
    ): string {
        if ($maxNumberOfItems === null) {
            $maxNumberOfItems = $this->config->settings['ForeignKeyMaxLimit'];
        }

        $foreign = [];

        // collect the data
        foreach ($dispRow as $relrow) {
            $key = $relrow[$foreignField];

            // if the display field has been defined for this foreign table
            $value = $foreignDisplay !== '' ? $relrow[$foreignDisplay] : '';

            $foreign[$key] = $value;
        }

        // put the dropdown sections in correct order
        $bottom = [];
        if ($foreignDisplay !== '') {
            $top = $this->buildForeignDropdown($foreign, $data, $this->config->settings['ForeignKeyDropdownOrder'][0]);

            if (isset($this->config->settings['ForeignKeyDropdownOrder'][1])) {
                $bottom = $this->buildForeignDropdown(
                    $foreign,
                    $data,
                    $this->config->settings['ForeignKeyDropdownOrder'][1],
                );
            }
        } else {
            $top = $this->buildForeignDropdown($foreign, $data, 'id-only');
        }

        // beginning of dropdown
        $ret = '<option value="">&nbsp;</option>';
        $topCount = count($top);
        if ($maxNumberOfItems === -1 || $topCount < $maxNumberOfItems) {
            $ret .= implode('', $top);
            if ($foreignDisplay && $topCount > 0) {
                // this empty option is to visually mark the beginning of the
                // second series of values (bottom)
                $ret .= '<option value="">&nbsp;</option>';
            }
        }

        if ($foreignDisplay !== '') {
            $ret .= implode('', $bottom);
        }

        return $ret;
    }

    /**
     * Gets foreign keys in preparation for a drop-down selector
     *
     * @param string $field         the foreign field name
     * @param bool   $overrideTotal whether to override the total
     * @param string $foreignFilter a possible filter
     * @param string $foreignLimit  a possible LIMIT clause
     * @param bool   $getTotal      optional, whether to get total num of rows
     *                              in $foreignData['the_total;]
     *                              (has an effect of performance)
     */
    public function getForeignData(
        Foreigners $foreigners,
        string $field,
        bool $overrideTotal,
        string $foreignFilter,
        string $foreignLimit,
        bool $getTotal = false,
    ): ForeignData {
        // we always show the foreign field in the drop-down; if a display
        // field is defined, we show it besides the foreign field
        $foreignLink = false;
        $dispRow = $foreignDisplay = $theTotal = null;
        $foreignField = '';
        do {
            if ($foreigners->isEmpty()) {
                break;
            }

            $foreigner = $this->searchColumnInForeigners($foreigners, $field);
            if ($foreigner === false || $foreigner === []) {
                break;
            }

            $foreignDb = $foreigner['foreign_db'];
            $foreignTable = $foreigner['foreign_table'];
            $foreignField = $foreigner['foreign_field'];

            // Count number of rows in the foreign table. Currently we do
            // not use a drop-down if more than ForeignKeyMaxLimit rows in the
            // foreign table,
            // for speed reasons and because we need a better interface for this.
            //
            // We could also do the SELECT anyway, with a LIMIT, and ensure that
            // the current value of the field is one of the choices.

            // Check if table has more rows than specified by ForeignKeyMaxLimit
            $moreThanLimit = $this->dbi->getTable($foreignDb, $foreignTable)
                ->checkIfMinRecordsExist($this->config->settings['ForeignKeyMaxLimit']);

            if ($overrideTotal || ! $moreThanLimit) {
                // foreign_display can be false if no display field defined:
                $foreignDisplay = $this->getDisplayField($foreignDb, $foreignTable);

                $fQueryMain = 'SELECT ' . Util::backquote($foreignField)
                    . (
                        $foreignDisplay === ''
                            ? ''
                            : ', ' . Util::backquote($foreignDisplay)
                    );
                $fQueryFrom = ' FROM ' . Util::backquote($foreignDb)
                    . '.' . Util::backquote($foreignTable);
                $fQueryFilter = $foreignFilter === '' ? '' : ' WHERE '
                    . Util::backquote($foreignField)
                    . ' LIKE ' . $this->dbi->quoteString(
                        '%' . $this->dbi->escapeMysqlWildcards($foreignFilter) . '%',
                    )
                    . (
                        $foreignDisplay === ''
                        ? ''
                        : ' OR ' . Util::backquote($foreignDisplay)
                        . ' LIKE ' . $this->dbi->quoteString(
                            '%' . $this->dbi->escapeMysqlWildcards($foreignFilter) . '%',
                        )
                    );
                $fQueryOrder = $foreignDisplay === '' ? '' : ' ORDER BY '
                    . Util::backquote($foreignTable) . '.'
                    . Util::backquote($foreignDisplay);

                $fQueryLimit = $foreignLimit;

                if ($foreignFilter !== '') {
                    $theTotal = $this->dbi->fetchValue('SELECT COUNT(*)' . $fQueryFrom . $fQueryFilter);
                }

                $disp = $this->dbi->tryQuery($fQueryMain . $fQueryFrom . $fQueryFilter . $fQueryOrder . $fQueryLimit);
                if ($disp && $disp->numRows() > 0) {
                    $dispRow = $disp->fetchAllAssoc();
                } else {
                    // Either no data in the foreign table or
                    // user does not have select permission to foreign table/field
                    // Show an input field with a 'Browse foreign values' link
                    $dispRow = null;
                    $foreignLink = true;
                }
            } else {
                $dispRow = null;
                $foreignLink = true;
            }
        } while (false);

        if ($getTotal && isset($foreignDb, $foreignTable)) {
            $theTotal = $this->dbi->getTable($foreignDb, $foreignTable)
                ->countRecords(true);
        }

        return new ForeignData(
            $foreignLink,
            (int) $theTotal,
            is_string($foreignDisplay) ? $foreignDisplay : '',
            $dispRow,
            $foreignField,
        );
    }

    /**
     * Rename a field in relation tables
     *
     * usually called after a column in a table was renamed
     *
     * @param string $db      database name
     * @param string $table   table name
     * @param string $field   old field name
     * @param string $newName new field name
     */
    public function renameField(string $db, string $table, string $field, string $newName): void
    {
        $relationParameters = $this->getRelationParameters();

        if ($relationParameters->displayFeature !== null) {
            $tableQuery = 'UPDATE '
                . Util::backquote($relationParameters->displayFeature->database) . '.'
                . Util::backquote($relationParameters->displayFeature->tableInfo)
                . '   SET display_field = ' . $this->dbi->quoteString($newName, ConnectionType::ControlUser)
                . ' WHERE db_name       = ' . $this->dbi->quoteString($db, ConnectionType::ControlUser)
                . '   AND table_name    = ' . $this->dbi->quoteString($table, ConnectionType::ControlUser)
                . '   AND display_field = ' . $this->dbi->quoteString($field, ConnectionType::ControlUser);
            $this->dbi->queryAsControlUser($tableQuery);
        }

        if ($relationParameters->relationFeature === null) {
            return;
        }

        $tableQuery = 'UPDATE '
            . Util::backquote($relationParameters->relationFeature->database) . '.'
            . Util::backquote($relationParameters->relationFeature->relation)
            . '   SET master_field = ' . $this->dbi->quoteString($newName, ConnectionType::ControlUser)
            . ' WHERE master_db    = ' . $this->dbi->quoteString($db, ConnectionType::ControlUser)
            . '   AND master_table = ' . $this->dbi->quoteString($table, ConnectionType::ControlUser)
            . '   AND master_field = ' . $this->dbi->quoteString($field, ConnectionType::ControlUser);
        $this->dbi->queryAsControlUser($tableQuery);

        $tableQuery = 'UPDATE '
            . Util::backquote($relationParameters->relationFeature->database) . '.'
            . Util::backquote($relationParameters->relationFeature->relation)
            . '   SET foreign_field = ' . $this->dbi->quoteString($newName, ConnectionType::ControlUser)
            . ' WHERE foreign_db    = ' . $this->dbi->quoteString($db, ConnectionType::ControlUser)
            . '   AND foreign_table = ' . $this->dbi->quoteString($table, ConnectionType::ControlUser)
            . '   AND foreign_field = ' . $this->dbi->quoteString($field, ConnectionType::ControlUser);
        $this->dbi->queryAsControlUser($tableQuery);
    }

    /**
     * Performs SQL query used for renaming table.
     *
     * @param string $sourceDb    Source database name
     * @param string $targetDb    Target database name
     * @param string $sourceTable Source table name
     * @param string $targetTable Target table name
     * @param string $dbField     Name of database field
     * @param string $tableField  Name of table field
     */
    public function renameSingleTable(
        DatabaseName $configStorageDatabase,
        TableName $configStorageTable,
        string $sourceDb,
        string $targetDb,
        string $sourceTable,
        string $targetTable,
        string $dbField,
        string $tableField,
    ): void {
        $query = 'UPDATE '
            . Util::backquote($configStorageDatabase) . '.'
            . Util::backquote($configStorageTable)
            . ' SET '
            . $dbField . ' = ' . $this->dbi->quoteString($targetDb, ConnectionType::ControlUser)
            . ', '
            . $tableField . ' = ' . $this->dbi->quoteString($targetTable, ConnectionType::ControlUser)
            . ' WHERE '
            . $dbField . '  = ' . $this->dbi->quoteString($sourceDb, ConnectionType::ControlUser)
            . ' AND '
            . $tableField . ' = ' . $this->dbi->quoteString($sourceTable, ConnectionType::ControlUser);
        $this->dbi->queryAsControlUser($query);
    }

    /**
     * Rename a table in relation tables
     *
     * usually called after table has been moved
     *
     * @param string $sourceDb    Source database name
     * @param string $targetDb    Target database name
     * @param string $sourceTable Source table name
     * @param string $targetTable Target table name
     */
    public function renameTable(string $sourceDb, string $targetDb, string $sourceTable, string $targetTable): void
    {
        $relationParameters = $this->getRelationParameters();

        // Move old entries from PMA-DBs to new table
        if ($relationParameters->columnCommentsFeature !== null) {
            $this->renameSingleTable(
                $relationParameters->columnCommentsFeature->database,
                $relationParameters->columnCommentsFeature->columnInfo,
                $sourceDb,
                $targetDb,
                $sourceTable,
                $targetTable,
                'db_name',
                'table_name',
            );
        }

        // updating bookmarks is not possible since only a single table is
        // moved, and not the whole DB.

        if ($relationParameters->displayFeature !== null) {
            $this->renameSingleTable(
                $relationParameters->displayFeature->database,
                $relationParameters->displayFeature->tableInfo,
                $sourceDb,
                $targetDb,
                $sourceTable,
                $targetTable,
                'db_name',
                'table_name',
            );
        }

        if ($relationParameters->relationFeature !== null) {
            $this->renameSingleTable(
                $relationParameters->relationFeature->database,
                $relationParameters->relationFeature->relation,
                $sourceDb,
                $targetDb,
                $sourceTable,
                $targetTable,
                'foreign_db',
                'foreign_table',
            );

            $this->renameSingleTable(
                $relationParameters->relationFeature->database,
                $relationParameters->relationFeature->relation,
                $sourceDb,
                $targetDb,
                $sourceTable,
                $targetTable,
                'master_db',
                'master_table',
            );
        }

        if ($relationParameters->pdfFeature !== null) {
            if ($sourceDb === $targetDb) {
                // rename within the database can be handled
                $this->renameSingleTable(
                    $relationParameters->pdfFeature->database,
                    $relationParameters->pdfFeature->tableCoords,
                    $sourceDb,
                    $targetDb,
                    $sourceTable,
                    $targetTable,
                    'db_name',
                    'table_name',
                );
            } else {
                // if the table is moved out of the database we can no longer keep the
                // record for table coordinate
                $removeQuery = 'DELETE FROM '
                    . Util::backquote($relationParameters->pdfFeature->database) . '.'
                    . Util::backquote($relationParameters->pdfFeature->tableCoords)
                    . ' WHERE db_name  = ' . $this->dbi->quoteString($sourceDb, ConnectionType::ControlUser)
                    . ' AND table_name = ' . $this->dbi->quoteString($sourceTable, ConnectionType::ControlUser);
                $this->dbi->queryAsControlUser($removeQuery);
            }
        }

        if ($relationParameters->uiPreferencesFeature !== null) {
            $this->renameSingleTable(
                $relationParameters->uiPreferencesFeature->database,
                $relationParameters->uiPreferencesFeature->tableUiPrefs,
                $sourceDb,
                $targetDb,
                $sourceTable,
                $targetTable,
                'db_name',
                'table_name',
            );
        }

        if ($relationParameters->navigationItemsHidingFeature === null) {
            return;
        }

        // update hidden items inside table
        $this->renameSingleTable(
            $relationParameters->navigationItemsHidingFeature->database,
            $relationParameters->navigationItemsHidingFeature->navigationHiding,
            $sourceDb,
            $targetDb,
            $sourceTable,
            $targetTable,
            'db_name',
            'table_name',
        );

        // update data for hidden table
        $query = 'UPDATE '
            . Util::backquote($relationParameters->navigationItemsHidingFeature->database) . '.'
            . Util::backquote($relationParameters->navigationItemsHidingFeature->navigationHiding)
            . ' SET db_name = ' . $this->dbi->quoteString($targetDb, ConnectionType::ControlUser)
            . ','
            . ' item_name = ' . $this->dbi->quoteString($targetTable, ConnectionType::ControlUser)
            . ' WHERE db_name  = ' . $this->dbi->quoteString($sourceDb, ConnectionType::ControlUser)
            . ' AND item_name = ' . $this->dbi->quoteString($sourceTable, ConnectionType::ControlUser)
            . " AND item_type = 'table'";
        $this->dbi->queryAsControlUser($query);
    }

    /**
     * Create a PDF page
     *
     * @param string $newpage name of the new PDF page
     * @param string $db      database name
     */
    public function createPage(string $newpage, PdfFeature $pdfFeature, string $db): int
    {
        $insQuery = 'INSERT INTO '
            . Util::backquote($pdfFeature->database) . '.'
            . Util::backquote($pdfFeature->pdfPages)
            . ' (db_name, page_descr)'
            . ' VALUES ('
            . $this->dbi->quoteString($db, ConnectionType::ControlUser) . ', '
            . $this->dbi->quoteString(
                $newpage !== '' ? $newpage : __('no description'),
                ConnectionType::ControlUser,
            ) . ')';
        $this->dbi->tryQueryAsControlUser($insQuery);

        return $this->dbi->insertId(ConnectionType::ControlUser);
    }

    /**
     * Get child table references for a table column.
     * This works only if 'DisableIS' is false. An empty array is returned otherwise.
     *
     * @param string $db     name of master table db.
     * @param string $table  name of master table.
     * @param string $column name of master table column.
     *
     * @return array<list<mixed[]>>
     */
    public function getChildReferences(string $db, string $table, string $column = ''): array
    {
        if (! $this->config->selectedServer['DisableIS']) {
            $relQuery = 'SELECT `column_name`, `table_name`,'
                . ' `table_schema`, `referenced_column_name`'
                . ' FROM `information_schema`.`key_column_usage`'
                . ' WHERE `referenced_table_name` = '
                . $this->dbi->quoteString($table)
                . ' AND `referenced_table_schema` = '
                . $this->dbi->quoteString($db);
            if ($column !== '') {
                $relQuery .= ' AND `referenced_column_name` = '
                    . $this->dbi->quoteString($column);
            }

            return $this->dbi->fetchResultMultidimensional(
                $relQuery,
                ['referenced_column_name', null],
            );
        }

        return [];
    }

    /**
     * Check child table references and foreign key for a table column.
     *
     * @param string                    $db                  name of master table db.
     * @param string                    $table               name of master table.
     * @param string                    $column              name of master table column.
     * @param list<ForeignKey>|null     $foreignersFull      foreigners array for the whole table.
     * @param array<list<mixed[]>>|null $childReferencesFull child references for the whole table.
     *
     * @return array<string, mixed> telling about references if foreign key.
     * @psalm-return array{isEditable: bool, isForeignKey: bool, isReferenced: bool, references: string[]}
     */
    public function checkChildForeignReferences(
        string $db,
        string $table,
        string $column,
        array|null $foreignersFull = null,
        array|null $childReferencesFull = null,
    ): array {
        $columnStatus = ['isEditable' => true, 'isReferenced' => false, 'isForeignKey' => false, 'references' => []];

        $foreigners = $foreignersFull ?? $this->getForeignKeysData($db, $table);

        $foreigner = $this->getColumnFromForeignKeysData($foreigners, $column);

        $childReferences = [];
        if ($childReferencesFull !== null) {
            if (isset($childReferencesFull[$column])) {
                $childReferences = $childReferencesFull[$column];
            }
        } else {
            $childReferences = $this->getChildReferences($db, $table, $column);
        }

        if ($childReferences !== [] || $foreigner !== false) {
            $columnStatus['isEditable'] = false;
            if ($childReferences !== []) {
                $columnStatus['isReferenced'] = true;
                foreach ($childReferences as $columns) {
                    $columnStatus['references'][] = Util::backquote($columns['table_schema'])
                        . '.' . Util::backquote($columns['table_name']);
                }
            }

            if ($foreigner !== false) {
                $columnStatus['isForeignKey'] = true;
            }
        }

        return $columnStatus;
    }

    public function searchColumnInForeigners(Foreigners $foreigners, string $column): array|false
    {
        if (isset($foreigners->data[$column])) {
            return $foreigners->data[$column];
        }

        return $this->getColumnFromForeignKeysData($foreigners->keysData, $column);
    }

    /**
     * @param list<ForeignKey> $foreignKeysData
     *
     * @return false|array{
     *  foreign_field: string,
     *  foreign_db: string,
     *  foreign_table: string|null,
     *  constraint: string|null,
     *  on_update: string,
     *  on_delete: string
     * }
     */
    public function getColumnFromForeignKeysData(array $foreignKeysData, string $column): array|false
    {
        $foreigner = [];
        foreach ($foreignKeysData as $oneKey) {
            $columnIndex = array_search($column, $oneKey->indexList);
            if ($columnIndex !== false) {
                $foreigner['foreign_field'] = $oneKey->refIndexList[$columnIndex];
                $foreigner['foreign_db'] = $oneKey->refDbName ?? Current::$database;
                $foreigner['foreign_table'] = $oneKey->refTableName;
                $foreigner['constraint'] = $oneKey->constraint;
                $foreigner['on_update'] = $oneKey->onUpdate ?? 'RESTRICT';
                $foreigner['on_delete'] = $oneKey->onDelete ?? 'RESTRICT';

                return $foreigner;
            }
        }

        return false;
    }

    /**
     * Returns default PMA table names and their create queries.
     *
     * @param array<string, string> $tableNameReplacements
     *
     * @return array<string, string> table name, create query
     */
    public function getCreateTableSqlQueries(array $tableNameReplacements): array
    {
        $pmaTables = [];
        $createTablesFile = (string) file_get_contents(SQL_DIR . 'create_tables.sql');

        $queries = explode(';', $createTablesFile);

        foreach ($queries as $query) {
            if (preg_match('/CREATE TABLE IF NOT EXISTS `(.*)` \(/', $query, $table) !== 1) {
                continue;
            }

            $tableName = $table[1];

            // Replace the table name with another one
            if (isset($tableNameReplacements[$tableName])) {
                $query = str_replace($tableName, $tableNameReplacements[$tableName], $query);
            }

            $pmaTables[$tableName] = $query . ';';
        }

        return $pmaTables;
    }

    /**
     * Create a database to be used as configuration storage
     */
    public function createPmaDatabase(string $configurationStorageDbName): bool
    {
        $this->dbi->tryQuery(
            'CREATE DATABASE IF NOT EXISTS ' . Util::backquote($configurationStorageDbName),
            ConnectionType::ControlUser,
        );

        $error = $this->dbi->getError(ConnectionType::ControlUser);
        if ($error === '') {
            // Re-build the cache to show the list of tables created or not
            // This is the case when the DB could be created but no tables just after
            // So just purge the cache and show the new configuration storage state
            self::$cache = null;
            $this->getRelationParameters();

            return true;
        }

        Current::$message = Message::error($error);

        if (DatabaseInterface::$errorNumber === 1044) {
            Current::$message = Message::error(sprintf(
                __(
                    'You do not have necessary privileges to create a database named'
                    . ' \'%s\'. You may go to \'Operations\' tab of any'
                    . ' database to set up the phpMyAdmin configuration storage there.',
                ),
                $configurationStorageDbName,
            ));
        }

        return false;
    }

    /**
     * Creates PMA tables in the given db, updates if already exists.
     *
     * @param string $db     database
     * @param bool   $create whether to create tables if they don't exist.
     */
    public function fixPmaTables(string $db, bool $create = true): void
    {
        if ($this->arePmadbTablesAllDisabled()) {
            return;
        }

        $tablesToFeatures = [
            'pma__bookmark' => 'bookmarktable',
            'pma__relation' => 'relation',
            'pma__table_info' => 'table_info',
            'pma__table_coords' => 'table_coords',
            'pma__pdf_pages' => 'pdf_pages',
            'pma__column_info' => 'column_info',
            'pma__history' => 'history',
            'pma__recent' => 'recent',
            'pma__favorite' => 'favorite',
            'pma__table_uiprefs' => 'table_uiprefs',
            'pma__tracking' => 'tracking',
            'pma__userconfig' => 'userconfig',
            'pma__users' => 'users',
            'pma__usergroups' => 'usergroups',
            'pma__navigationhiding' => 'navigationhiding',
            'pma__savedsearches' => 'savedsearches',
            'pma__central_columns' => 'central_columns',
            'pma__designer_settings' => 'designer_settings',
            'pma__export_templates' => 'export_templates',
        ];

        $existingTables = $this->dbi->getTables($db, ConnectionType::ControlUser);

        $tableNameReplacements = $this->getTableReplacementNames($tablesToFeatures);

        $createQueries = [];
        if ($create) {
            $createQueries = $this->getCreateTableSqlQueries($tableNameReplacements);
            if (! $this->dbi->selectDb($db, ConnectionType::ControlUser)) {
                Current::$message = Message::error($this->dbi->getError(ConnectionType::ControlUser));

                return;
            }
        }

        $foundOne = false;
        foreach ($tablesToFeatures as $table => $feature) {
            if (($this->config->selectedServer[$feature] ?? null) === false) {
                // The feature is disabled by the user in config
                continue;
            }

            // Check if the table already exists
            // use the possible replaced name first and fallback on the table name
            // if no replacement exists
            if (! in_array($tableNameReplacements[$table] ?? $table, $existingTables, true)) {
                if (! $create) {
                    continue;
                }

                $this->dbi->tryQuery($createQueries[$table], ConnectionType::ControlUser);

                $error = $this->dbi->getError(ConnectionType::ControlUser);
                if ($error !== '') {
                    Current::$message = Message::error($error);

                    return;
                }
            }

            $foundOne = true;

            // Do not override a user defined value, only fill if empty
            if (isset($this->config->selectedServer[$feature]) && $this->config->selectedServer[$feature] !== '') {
                continue;
            }

            // Fill it with the default table name
            $this->config->selectedServer[$feature] = $table;
        }

        if (! $foundOne) {
            return;
        }

        $this->config->selectedServer['pmadb'] = $db;

        // Unset the cache as new tables might have been added
        self::$cache = null;
        // Fill back the cache
        $this->getRelationParameters();
    }

    /**
     * Verifies that all pmadb features are disabled
     */
    public function arePmadbTablesAllDisabled(): bool
    {
        return $this->config->selectedServer['bookmarktable'] === false
            && ($this->config->selectedServer['relation'] ) === false
            && ($this->config->selectedServer['table_info'] ) === false
            && ($this->config->selectedServer['table_coords'] ) === false
            && ($this->config->selectedServer['column_info'] ) === false
            && ($this->config->selectedServer['pdf_pages'] ) === false
            && ($this->config->selectedServer['history'] ) === false
            && ($this->config->selectedServer['recent'] ) === false
            && ($this->config->selectedServer['favorite'] ) === false
            && ($this->config->selectedServer['table_uiprefs'] ) === false
            && ($this->config->selectedServer['tracking'] ) === false
            && ($this->config->selectedServer['userconfig'] ) === false
            && ($this->config->selectedServer['users'] ) === false
            && ($this->config->selectedServer['usergroups'] ) === false
            && ($this->config->selectedServer['navigationhiding'] ) === false
            && ($this->config->selectedServer['savedsearches'] ) === false
            && ($this->config->selectedServer['central_columns'] ) === false
            && ($this->config->selectedServer['designer_settings'] ) === false
            && ($this->config->selectedServer['export_templates'] ) === false;
    }

    /**
     * Verifies if all the pmadb tables are defined
     */
    public function arePmadbTablesDefined(): bool
    {
        return ! (empty($this->config->selectedServer['bookmarktable'])
            || empty($this->config->selectedServer['relation'])
            || empty($this->config->selectedServer['table_info'])
            || empty($this->config->selectedServer['table_coords'])
            || empty($this->config->selectedServer['column_info'])
            || empty($this->config->selectedServer['pdf_pages'])
            || empty($this->config->selectedServer['history'])
            || empty($this->config->selectedServer['recent'])
            || empty($this->config->selectedServer['favorite'])
            || empty($this->config->selectedServer['table_uiprefs'])
            || empty($this->config->selectedServer['tracking'])
            || empty($this->config->selectedServer['userconfig'])
            || empty($this->config->selectedServer['users'])
            || empty($this->config->selectedServer['usergroups'])
            || empty($this->config->selectedServer['navigationhiding'])
            || empty($this->config->selectedServer['savedsearches'])
            || empty($this->config->selectedServer['central_columns'])
            || empty($this->config->selectedServer['designer_settings'])
            || empty($this->config->selectedServer['export_templates']));
    }

    /**
     * Get tables for foreign key constraint
     *
     * @param string $foreignDb     Database name
     * @param string $storageEngine Table storage engine
     *
     * @return string[] Table names
     */
    public function getTables(string $foreignDb, string $storageEngine): array
    {
        $tablesRows = $this->dbi->query(
            'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '
            . $this->dbi->quoteString($foreignDb)
            . ' AND ENGINE = ' . $this->dbi->quoteString($storageEngine),
        );
        /** @var list<string> $tables */
        $tables = $tablesRows->fetchAllColumn();

        if ($this->config->settings['NaturalOrder']) {
            usort($tables, strnatcasecmp(...));
        }

        return $tables;
    }

    public function getConfigurationStorageDbName(): string
    {
        $cfgStorageDbName = $this->config->selectedServer['pmadb'];

        // Use "phpmyadmin" as a default database name to check to keep the behavior consistent
        return empty($cfgStorageDbName) ? 'phpmyadmin' : $cfgStorageDbName;
    }

    /**
     * This function checks and initializes the phpMyAdmin configuration
     * storage state before it is used into session cache.
     */
    public function initRelationParamsCache(): void
    {
        $storageDbName = $this->config->selectedServer['pmadb'];
        // Use "phpmyadmin" as a default database name to check to keep the behavior consistent
        $storageDbName = $storageDbName !== '' ? $storageDbName : 'phpmyadmin';

        // This will make users not having explicitly listed databases
        // have config values filled by the default phpMyAdmin storage table name values
        $this->fixPmaTables($storageDbName, false);

        // This global will be changed if fixPmaTables did find one valid table
        // Empty means that until now no pmadb was found eligible
        if ($this->config->selectedServer['pmadb'] !== '') {
            return;
        }

        $this->fixPmaTables(Current::$database, false);
    }

    /**
     * @param non-empty-array<string, string> $tablesToFeatures
     *
     * @return array<string, string>
     */
    private function getTableReplacementNames(array $tablesToFeatures): array
    {
        $tableNameReplacements = [];

        foreach ($tablesToFeatures as $table => $feature) {
            if (empty($this->config->selectedServer[$feature]) || $this->config->selectedServer[$feature] === $table) {
                continue;
            }

            // Set the replacement to transform the default table name into a custom name
            $tableNameReplacements[$table] = $this->config->selectedServer[$feature];
        }

        return $tableNameReplacements;
    }
}
