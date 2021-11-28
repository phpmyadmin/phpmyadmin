<?php
/**
 * Set of functions used with the relation and PDF feature
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Utils\Table as TableUtils;

use function __;
use function _pgettext;
use function array_keys;
use function array_reverse;
use function array_search;
use function array_shift;
use function asort;
use function bin2hex;
use function count;
use function explode;
use function file_get_contents;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_scalar;
use function is_string;
use function ksort;
use function mb_check_encoding;
use function mb_strlen;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function natcasesort;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_replace;
use function strlen;
use function trim;
use function uksort;
use function usort;

/**
 * Set of functions used with the relation and PDF feature
 */
class Relation
{
    /** @var DatabaseInterface */
    public $dbi;

    /** @var Template */
    public $template;

    /**
     * @param DatabaseInterface|null $dbi      Database interface
     * @param Template|null          $template Template instance
     */
    public function __construct(?DatabaseInterface $dbi, ?Template $template = null)
    {
        $this->dbi = $dbi;
        $this->template = $template ?? new Template();
    }

    /**
     * Executes a query as controluser if possible, otherwise as normal user
     *
     * @param string $sql        the query to execute
     * @param bool   $show_error whether to display SQL error messages or not
     * @param int    $options    query options
     *
     * @return mixed|bool the result set, or false if no result set
     *
     * @access public
     */
    public function queryAsControlUser($sql, $show_error = true, $options = 0)
    {
        // Avoid caching of the number of rows affected; for example, this function
        // is called for tracking purposes but we want to display the correct number
        // of rows affected by the original query, not by the query generated for
        // tracking.
        $cache_affected_rows = false;

        if ($show_error) {
            $result = $this->dbi->query($sql, DatabaseInterface::CONNECT_CONTROL, $options, $cache_affected_rows);
        } else {
            $result = @$this->dbi->tryQuery($sql, DatabaseInterface::CONNECT_CONTROL, $options, $cache_affected_rows);
        }

        if ($result) {
            return $result;
        }

        return false;
    }

    public function getRelationParameters(): RelationParameters
    {
        if (
            empty($_SESSION['relation'][$GLOBALS['server']])
            || empty($_SESSION['relation'][$GLOBALS['server']]['version'])
            || $_SESSION['relation'][$GLOBALS['server']]['version'] !== Version::VERSION
        ) {
            $_SESSION['relation'][$GLOBALS['server']] = $this->checkRelationsParam();
        }

        return RelationParameters::fromArray($_SESSION['relation'][$GLOBALS['server']]);
    }

    /**
     * prints out diagnostic info for pma relation feature
     *
     * @return string
     */
    public function getRelationsParamDiagnostic(RelationParameters $relationParameters)
    {
        $retval = '<br>';

        $messages = [];
        $messages['error'] = '<span class="text-danger"><strong>'
            . __('not OK')
            . '</strong></span>';

        $messages['ok'] = '<span class="text-success"><strong>'
            . _pgettext('Correctly working', 'OK')
            . '</strong></span>';

        $messages['enabled'] = '<span class="text-success">' . __('Enabled') . '</span>';
        $messages['disabled'] = '<span class="text-danger">' . __('Disabled') . '</span>';

        if (strlen((string) $relationParameters->db) === 0) {
            $retval .= '<p>' . __('Configuration of pmadb…') . ' '
                 . $messages['error']
                 . MySQLDocumentation::showDocumentation('setup', 'linked-tables')
                 . '</p><p>' . "\n"
                 . __('General relation features')
                 . ' <span class="text-success">' . __('Disabled')
                 . '</span></p>' . "\n";
            if ($GLOBALS['cfg']['ZeroConf']) {
                if (strlen($GLOBALS['db']) == 0) {
                    $retval .= $this->getHtmlFixPmaTables(true, true);
                } else {
                    $retval .= $this->getHtmlFixPmaTables(true);
                }
            }
        } else {
            $retval .= '<table class="table table-light table-striped w-auto">' . "\n";

            if (
                ! $relationParameters->allworks
                && $GLOBALS['cfg']['ZeroConf']
                // Avoid showing a "Create missing tables" link if it's a
                // problem of missing definition
                && $this->arePmadbTablesDefined()
            ) {
                $retval .= $this->getHtmlFixPmaTables(false);
                $retval .= '<br>';
            }

            $retval .= $this->getDiagMessageForParameter(
                'pmadb',
                isset($relationParameters->db),
                $messages,
                'pmadb'
            );
            $retval .= $this->getDiagMessageForParameter(
                'relation',
                isset($relationParameters->relation),
                $messages,
                'relation'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('General relation features'),
                'relwork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'table_info',
                isset($relationParameters->tableInfo),
                $messages,
                'table_info'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Display Features'),
                'displaywork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'table_coords',
                isset($relationParameters->tableCoords),
                $messages,
                'table_coords'
            );
            $retval .= $this->getDiagMessageForParameter(
                'pdf_pages',
                isset($relationParameters->pdfPages),
                $messages,
                'pdf_pages'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Designer and creation of PDFs'),
                'pdfwork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'column_info',
                isset($relationParameters->columnInfo),
                $messages,
                'column_info'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Displaying Column Comments'),
                'commwork',
                $messages,
                false
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Browser transformation'),
                'mimework',
                $messages
            );
            if ($relationParameters->commwork && ! $relationParameters->mimework) {
                $retval .= '<tr><td colspan=2 class="text-start error">';
                $retval .= __('Please see the documentation on how to update your column_info table.');
                $retval .= MySQLDocumentation::showDocumentation('config', 'cfg_Servers_column_info');
                $retval .= '</td></tr>';
            }

            $retval .= $this->getDiagMessageForParameter(
                'bookmarktable',
                isset($relationParameters->bookmark),
                $messages,
                'bookmark'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Bookmarked SQL query'),
                'bookmarkwork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'history',
                isset($relationParameters->history),
                $messages,
                'history'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('SQL history'),
                'historywork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'recent',
                isset($relationParameters->recent),
                $messages,
                'recent'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Persistent recently used tables'),
                'recentwork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'favorite',
                isset($relationParameters->favorite),
                $messages,
                'favorite'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Persistent favorite tables'),
                'favoritework',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'table_uiprefs',
                isset($relationParameters->tableUiprefs),
                $messages,
                'table_uiprefs'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Persistent tables\' UI preferences'),
                'uiprefswork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'tracking',
                isset($relationParameters->tracking),
                $messages,
                'tracking'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Tracking'),
                'trackingwork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'userconfig',
                isset($relationParameters->userconfig),
                $messages,
                'userconfig'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('User preferences'),
                'userconfigwork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'users',
                isset($relationParameters->users),
                $messages,
                'users'
            );
            $retval .= $this->getDiagMessageForParameter(
                'usergroups',
                isset($relationParameters->usergroups),
                $messages,
                'usergroups'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Configurable menus'),
                'menuswork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'navigationhiding',
                isset($relationParameters->navigationhiding),
                $messages,
                'navigationhiding'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Hide/show navigation items'),
                'navwork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'savedsearches',
                isset($relationParameters->savedsearches),
                $messages,
                'savedsearches'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Saving Query-By-Example searches'),
                'savedsearcheswork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'central_columns',
                isset($relationParameters->centralColumns),
                $messages,
                'central_columns'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Managing Central list of columns'),
                'centralcolumnswork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'designer_settings',
                isset($relationParameters->designerSettings),
                $messages,
                'designer_settings'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Remembering Designer Settings'),
                'designersettingswork',
                $messages
            );
            $retval .= $this->getDiagMessageForParameter(
                'export_templates',
                isset($relationParameters->exportTemplates),
                $messages,
                'export_templates'
            );
            $retval .= $this->getDiagMessageForFeature(
                __('Saving export templates'),
                'exporttemplateswork',
                $messages
            );
            $retval .= '</table>' . "\n";

            if (! $relationParameters->allworks) {
                $retval .= $this->template->render('relation/diagnostic_info', ['sql_dir' => SQL_DIR]);
            }
        }

        return $retval;
    }

    /**
     * prints out one diagnostic message for a feature
     *
     * @param string $feature_name       feature name in a message string
     * @param string $relation_parameter the relation parameter to check
     * @param array  $messages           utility messages
     * @param bool   $skip_line          whether to skip a line after the message
     *
     * @return string
     */
    public function getDiagMessageForFeature(
        $feature_name,
        $relation_parameter,
        array $messages,
        $skip_line = true
    ) {
        $relationParameters = $this->getRelationParameters();
        $retval = '    <tr><td colspan=2 class="text-end">' . $feature_name . ': ';
        if (isset($relationParameters->{$relation_parameter}) && $relationParameters->{$relation_parameter}) {
            $retval .= $messages['enabled'];
        } else {
            $retval .= $messages['disabled'];
        }

        $retval .= '</td></tr>';
        if ($skip_line) {
            $retval .= '<tr><td>&nbsp;</td></tr>';
        }

        return $retval;
    }

    /**
     * prints out one diagnostic message for a configuration parameter
     *
     * @param string $parameter            config parameter name to display
     * @param bool   $relationParameterSet whether this parameter is set
     * @param array  $messages             utility messages
     * @param string $docAnchor            anchor in documentation
     *
     * @return string
     */
    public function getDiagMessageForParameter(
        $parameter,
        $relationParameterSet,
        array $messages,
        $docAnchor
    ) {
        $retval = '<tr><th class="text-start">';
        $retval .= '$cfg[\'Servers\'][$i][\'' . $parameter . '\']  ... ';
        $retval .= '</th><td class="text-end">';
        if ($relationParameterSet) {
            $retval .= $messages['ok'];
        } else {
            $retval .= sprintf(
                $messages['error'],
                MySQLDocumentation::getDocumentationLink('config', 'cfg_Servers_' . $docAnchor)
            );
        }

        $retval .= '</td></tr>' . "\n";

        return $retval;
    }

    /**
     * @param array<string, bool|string|null> $relationParams
     *
     * @return array<string, bool|string|null>
     */
    private function checkTableAccess(array $relationParams): array
    {
        if (isset($relationParams['relation'], $relationParams['table_info'])) {
            if ($this->canAccessStorageTable((string) $relationParams['table_info'])) {
                $relationParams['displaywork'] = true;
            }
        }

        if (isset($relationParams['table_coords'], $relationParams['pdf_pages'])) {
            if ($this->canAccessStorageTable((string) $relationParams['table_coords'])) {
                if ($this->canAccessStorageTable((string) $relationParams['pdf_pages'])) {
                    $relationParams['pdfwork'] = true;
                }
            }
        }

        if (isset($relationParams['column_info'])) {
            if ($this->canAccessStorageTable((string) $relationParams['column_info'])) {
                $relationParams['commwork'] = true;
                // phpMyAdmin 4.3+
                // Check for input transformations upgrade.
                $relationParams['mimework'] = $this->tryUpgradeTransformations();
            }
        }

        if (isset($relationParams['users'], $relationParams['usergroups'])) {
            if ($this->canAccessStorageTable((string) $relationParams['users'])) {
                if ($this->canAccessStorageTable((string) $relationParams['usergroups'])) {
                    $relationParams['menuswork'] = true;
                }
            }
        }

        $settings = [
            'export_templates' => 'exporttemplateswork',
            'designer_settings' => 'designersettingswork',
            'central_columns' => 'centralcolumnswork',
            'savedsearches' => 'savedsearcheswork',
            'navigationhiding' => 'navwork',
            'bookmark' => 'bookmarkwork',
            'userconfig' => 'userconfigwork',
            'tracking' => 'trackingwork',
            'table_uiprefs' => 'uiprefswork',
            'favorite' => 'favoritework',
            'recent' => 'recentwork',
            'history' => 'historywork',
            'relation' => 'relwork',
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
    private function fillRelationParamsWithTableNames(array $relationParams): ?array
    {
        $tabQuery = 'SHOW TABLES FROM '
        . Util::backquote($GLOBALS['cfg']['Server']['pmadb']);
        $tableRes = $this->queryAsControlUser($tabQuery, false, DatabaseInterface::QUERY_STORE);
        if (is_bool($tableRes)) {
            return null;
        }

        while ($currTable = @$this->dbi->fetchRow($tableRes)) {
            if ($currTable[0] == $GLOBALS['cfg']['Server']['bookmarktable']) {
                $relationParams['bookmark'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['relation']) {
                $relationParams['relation'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['table_info']) {
                $relationParams['table_info'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['table_coords']) {
                $relationParams['table_coords'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['column_info']) {
                $relationParams['column_info'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['pdf_pages']) {
                $relationParams['pdf_pages'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['history']) {
                $relationParams['history'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['recent']) {
                $relationParams['recent'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['favorite']) {
                $relationParams['favorite'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['table_uiprefs']) {
                $relationParams['table_uiprefs'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['tracking']) {
                $relationParams['tracking'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['userconfig']) {
                $relationParams['userconfig'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['users']) {
                $relationParams['users'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['usergroups']) {
                $relationParams['usergroups'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['navigationhiding']) {
                $relationParams['navigationhiding'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['savedsearches']) {
                $relationParams['savedsearches'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['central_columns']) {
                $relationParams['central_columns'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['designer_settings']) {
                $relationParams['designer_settings'] = (string) $currTable[0];
            } elseif ($currTable[0] == $GLOBALS['cfg']['Server']['export_templates']) {
                $relationParams['export_templates'] = (string) $currTable[0];
            }
        }

        $this->dbi->freeResult($tableRes);

        return $relationParams;
    }

    /**
     * Defines the relation parameters for the current user
     * just a copy of the functions used for relations ;-)
     * but added some stuff to check what will work
     *
     * @return array<string, bool|string|null> the relation parameters for the current user
     *
     * @access protected
     */
    public function checkRelationsParam(): array
    {
        $relationParams = [];
        $relationParams['version'] = Version::VERSION;

        $workToTable = [
            'relwork' => 'relation',
            'displaywork' => [
                'relation',
                'table_info',
            ],
            'bookmarkwork' => 'bookmarktable',
            'pdfwork' => [
                'table_coords',
                'pdf_pages',
            ],
            'commwork' => 'column_info',
            'mimework' => 'column_info',
            'historywork' => 'history',
            'recentwork' => 'recent',
            'favoritework' => 'favorite',
            'uiprefswork' => 'table_uiprefs',
            'trackingwork' => 'tracking',
            'userconfigwork' => 'userconfig',
            'menuswork' => [
                'users',
                'usergroups',
            ],
            'navwork' => 'navigationhiding',
            'savedsearcheswork' => 'savedsearches',
            'centralcolumnswork' => 'central_columns',
            'designersettingswork' => 'designer_settings',
            'exporttemplateswork' => 'export_templates',
        ];

        foreach (array_keys($workToTable) as $work) {
            $relationParams[$work] = false;
        }

        $relationParams['allworks'] = false;
        $relationParams['user'] = null;
        $relationParams['db'] = null;

        if (
            $GLOBALS['server'] == 0
            || empty($GLOBALS['cfg']['Server']['pmadb'])
            || ! $this->dbi->selectDb($GLOBALS['cfg']['Server']['pmadb'], DatabaseInterface::CONNECT_CONTROL)
        ) {
            // No server selected -> no bookmark table
            // we return the array with the falses in it,
            // to avoid some 'Uninitialized string offset' errors later
            $GLOBALS['cfg']['Server']['pmadb'] = false;

            return $relationParams;
        }

        $relationParams['user'] = $GLOBALS['cfg']['Server']['user'];
        $relationParams['db'] = $GLOBALS['cfg']['Server']['pmadb'];

        //  Now I just check if all tables that i need are present so I can for
        //  example enable relations but not pdf...
        //  I was thinking of checking if they have all required columns but I
        //  fear it might be too slow

        $relationParamsFilled = $this->fillRelationParamsWithTableNames($relationParams);

        if ($relationParamsFilled === null) {
            // query failed ... ?
            //$GLOBALS['cfg']['Server']['pmadb'] = false;
            return $relationParams;
        }

        // Filling did success
        $relationParams = $relationParamsFilled;

        $relationParams = $this->checkTableAccess($relationParams);

        $allWorks = true;
        foreach ($workToTable as $work => $table) {
            if ($relationParams[$work]) {
                continue;
            }

            if (is_string($table)) {
                if (isset($GLOBALS['cfg']['Server'][$table]) && $GLOBALS['cfg']['Server'][$table] !== false) {
                    $allWorks = false;
                    break;
                }
            } else {
                $oneNull = false;
                foreach ($table as $t) {
                    if (isset($GLOBALS['cfg']['Server'][$t]) && $GLOBALS['cfg']['Server'][$t] === false) {
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

        $relationParams['allworks'] = $allWorks;

        return $relationParams;
    }

    /**
     * Check if the table is accessible
     *
     * @param string $tableDbName The table or table.db
     */
    public function canAccessStorageTable(string $tableDbName): bool
    {
        $result = $this->queryAsControlUser(
            'SELECT NULL FROM ' . Util::backquote($tableDbName) . ' LIMIT 0',
            false,
            DatabaseInterface::QUERY_STORE
        );

        return $result !== false;
    }

    /**
     * Check whether column_info table input transformation
     * upgrade is required and try to upgrade silently
     *
     * @access public
     */
    public function tryUpgradeTransformations(): bool
    {
        // From 4.3, new input oriented transformation feature was introduced.
        // Check whether column_info table has input transformation columns
        $new_cols = [
            'input_transformation',
            'input_transformation_options',
        ];
        $query = 'SHOW COLUMNS FROM '
            . Util::backquote($GLOBALS['cfg']['Server']['pmadb'])
            . '.' . Util::backquote($GLOBALS['cfg']['Server']['column_info'])
            . ' WHERE Field IN (\'' . implode('\', \'', $new_cols) . '\')';
        $result = $this->queryAsControlUser($query, false, DatabaseInterface::QUERY_STORE);
        if ($result) {
            $rows = $this->dbi->numRows($result);
            $this->dbi->freeResult($result);
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
                [
                    '`phpmyadmin`',
                    '`pma__column_info`',
                ],
                [
                    Util::backquote($GLOBALS['cfg']['Server']['pmadb']),
                    Util::backquote($GLOBALS['cfg']['Server']['column_info']),
                ],
                (string) $query
            );
            $this->dbi->tryMultiQuery($query, DatabaseInterface::CONNECT_CONTROL);
            // skips result sets of query as we are not interested in it
            do {
                $hasResult = (
                    $this->dbi->moreResults(DatabaseInterface::CONNECT_CONTROL)
                    && $this->dbi->nextResult(DatabaseInterface::CONNECT_CONTROL)
                );
            } while ($hasResult);

            $error = $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);

            // return true if no error exists otherwise false
            return empty($error);
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
     * @param string $source the source for foreign key information
     *
     * @return array    db,table,column
     *
     * @access public
     */
    public function getForeigners($db, $table, $column = '', $source = 'both')
    {
        $relationParameters = $this->getRelationParameters();
        $foreign = [];

        if ($relationParameters->relwork && ($source === 'both' || $source === 'internal')) {
            $rel_query = 'SELECT `master_field`, `foreign_db`, '
                . '`foreign_table`, `foreign_field`'
                . ' FROM ' . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->relation)
                . ' WHERE `master_db` = \'' . $this->dbi->escapeString($db) . '\''
                . ' AND `master_table` = \'' . $this->dbi->escapeString($table) . '\'';
            if (strlen($column) > 0) {
                $rel_query .= ' AND `master_field` = '
                    . '\'' . $this->dbi->escapeString($column) . '\'';
            }

            $foreign = $this->dbi->fetchResult($rel_query, 'master_field', null, DatabaseInterface::CONNECT_CONTROL);
        }

        if (($source === 'both' || $source === 'foreign') && strlen($table) > 0) {
            $tableObj = new Table($table, $db);
            $show_create_table = $tableObj->showCreate();
            if ($show_create_table) {
                $parser = new Parser($show_create_table);
                $stmt = $parser->statements[0];
                $foreign['foreign_keys_data'] = [];
                if ($stmt instanceof CreateStatement) {
                    $foreign['foreign_keys_data'] = TableUtils::getForeignKeys($stmt);
                }
            }
        }

        /**
         * Emulating relations for some information_schema tables
         */
        $isInformationSchema = mb_strtolower($db) === 'information_schema';
        $isMysql = mb_strtolower($db) === 'mysql';
        if (($isInformationSchema || $isMysql) && ($source === 'internal' || $source === 'both')) {
            if ($isInformationSchema) {
                $internalRelations = InternalRelations::getInformationSchema();
            } else {
                $internalRelations = InternalRelations::getMySql();
            }

            if (isset($internalRelations[$table])) {
                foreach ($internalRelations[$table] as $field => $relations) {
                    if (
                        (strlen($column) !== 0 && $column != $field)
                        || (isset($foreign[$field])
                        && strlen($foreign[$field]) !== 0)
                    ) {
                        continue;
                    }

                    $foreign[$field] = $relations;
                }
            }
        }

        return $foreign;
    }

    /**
     * Gets the display field of a table
     *
     * @param string $db    the name of the db to check for
     * @param string $table the name of the table to check for
     *
     * @return string|false field name or false
     *
     * @access public
     */
    public function getDisplayField($db, $table)
    {
        $relationParameters = $this->getRelationParameters();

        /**
         * Try to fetch the display field from DB.
         */
        if ($relationParameters->displaywork) {
            $disp_query = 'SELECT `display_field`'
                    . ' FROM ' . Util::backquote($relationParameters->db)
                    . '.' . Util::backquote($relationParameters->tableInfo)
                    . ' WHERE `db_name` = \'' . $this->dbi->escapeString((string) $db) . '\''
                    . ' AND `table_name` = \'' . $this->dbi->escapeString((string) $table) . '\'';

            $row = $this->dbi->fetchSingleRow(
                $disp_query,
                DatabaseInterface::FETCH_ASSOC,
                DatabaseInterface::CONNECT_CONTROL
            );
            if (isset($row['display_field'])) {
                return $row['display_field'];
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
        $columns = $this->dbi->getColumnsFull($db, $table);
        foreach ($columns as $column) {
            if ($this->dbi->types->getTypeClass($column['DATA_TYPE']) === 'CHAR') {
                return $column['COLUMN_NAME'];
            }
        }

        return false;
    }

    /**
     * Gets the comments for all columns of a table or the db itself
     *
     * @param string $db    the name of the db to check for
     * @param string $table the name of the table to check for
     *
     * @return array    [column_name] = comment
     *
     * @access public
     */
    public function getComments($db, $table = ''): array
    {
        if ($table === '') {
            return [$this->getDbComment($db)];
        }

        $comments = [];

        // MySQL native column comments
        $columns = $this->dbi->getColumns($db, $table, true);
        foreach ($columns as $column) {
            if (empty($column['Comment'])) {
                continue;
            }

            $comments[$column['Field']] = $column['Comment'];
        }

        return $comments;
    }

    /**
     * Gets the comment for a db
     *
     * @param string $db the name of the db to check for
     *
     * @access public
     */
    public function getDbComment(string $db): string
    {
        $relationParameters = $this->getRelationParameters();

        if ($relationParameters->commwork) {
            // pmadb internal db comment
            $com_qry = 'SELECT `comment`'
                    . ' FROM ' . Util::backquote($relationParameters->db)
                    . '.' . Util::backquote($relationParameters->columnInfo)
                    . ' WHERE db_name = \'' . $this->dbi->escapeString($db) . '\''
                    . ' AND table_name  = \'\''
                    . ' AND column_name = \'(db_comment)\'';
            $com_rs = $this->queryAsControlUser($com_qry, false, DatabaseInterface::QUERY_STORE);

            if ($com_rs && $this->dbi->numRows($com_rs) > 0) {
                $row = $this->dbi->fetchAssoc($com_rs);

                return (string) $row['comment'];
            }
        }

        return '';
    }

    /**
     * Gets the comment for a db
     *
     * @return array comments
     *
     * @access public
     */
    public function getDbComments()
    {
        $relationParameters = $this->getRelationParameters();
        $comments = [];

        if ($relationParameters->commwork) {
            // pmadb internal db comment
            $com_qry = 'SELECT `db_name`, `comment`'
                    . ' FROM ' . Util::backquote($relationParameters->db)
                    . '.' . Util::backquote($relationParameters->columnInfo)
                    . ' WHERE `column_name` = \'(db_comment)\'';
            $com_rs = $this->queryAsControlUser($com_qry, false, DatabaseInterface::QUERY_STORE);

            if ($com_rs && $this->dbi->numRows($com_rs) > 0) {
                while ($row = $this->dbi->fetchAssoc($com_rs)) {
                    $comments[$row['db_name']] = $row['comment'];
                }
            }

            $this->dbi->freeResult($com_rs);
        }

        return $comments;
    }

    /**
     * Set a database comment to a certain value.
     *
     * @param string $db      the name of the db
     * @param string $comment the value of the column
     *
     * @access public
     */
    public function setDbComment($db, $comment = ''): bool
    {
        $relationParameters = $this->getRelationParameters();

        if (! $relationParameters->commwork) {
            return false;
        }

        if (strlen($comment) > 0) {
            $upd_query = 'INSERT INTO '
                . Util::backquote($relationParameters->db) . '.'
                . Util::backquote($relationParameters->columnInfo)
                . ' (`db_name`, `table_name`, `column_name`, `comment`)'
                . ' VALUES (\''
                . $this->dbi->escapeString($db)
                . "', '', '(db_comment)', '"
                . $this->dbi->escapeString($comment)
                . "') "
                . ' ON DUPLICATE KEY UPDATE '
                . "`comment` = '" . $this->dbi->escapeString($comment) . "'";
        } else {
            $upd_query = 'DELETE FROM '
                . Util::backquote($relationParameters->db) . '.'
                . Util::backquote($relationParameters->columnInfo)
                . ' WHERE `db_name`     = \'' . $this->dbi->escapeString($db)
                . '\'
                    AND `table_name`  = \'\'
                    AND `column_name` = \'(db_comment)\'';
        }

        return (bool) $this->queryAsControlUser($upd_query);
    }

    /**
     * Set a SQL history entry
     *
     * @param string $db       the name of the db
     * @param string $table    the name of the table
     * @param string $username the username
     * @param string $sqlquery the sql query
     *
     * @access public
     */
    public function setHistory($db, $table, $username, $sqlquery): void
    {
        $maxCharactersInDisplayedSQL = $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'];
        // Prevent to run this automatically on Footer class destroying in testsuite
        if (mb_strlen($sqlquery) > $maxCharactersInDisplayedSQL) {
            return;
        }

        $relationParameters = $this->getRelationParameters();

        if (! isset($_SESSION['sql_history'])) {
            $_SESSION['sql_history'] = [];
        }

        $_SESSION['sql_history'][] = [
            'db' => $db,
            'table' => $table,
            'sqlquery' => $sqlquery,
        ];

        if (count($_SESSION['sql_history']) > $GLOBALS['cfg']['QueryHistoryMax']) {
            // history should not exceed a maximum count
            array_shift($_SESSION['sql_history']);
        }

        if (! $relationParameters->historywork || ! $GLOBALS['cfg']['QueryHistoryDB']) {
            return;
        }

        $this->queryAsControlUser(
            'INSERT INTO '
            . Util::backquote($relationParameters->db) . '.'
            . Util::backquote($relationParameters->history) . '
                  (`username`,
                    `db`,
                    `table`,
                    `timevalue`,
                    `sqlquery`)
            VALUES
                  (\'' . $this->dbi->escapeString($username) . '\',
                   \'' . $this->dbi->escapeString($db) . '\',
                   \'' . $this->dbi->escapeString($table) . '\',
                   NOW(),
                   \'' . $this->dbi->escapeString($sqlquery) . '\')'
        );

        $this->purgeHistory($username);
    }

    /**
     * Gets a SQL history entry
     *
     * @param string $username the username
     *
     * @return array|bool list of history items
     *
     * @access public
     */
    public function getHistory($username)
    {
        $relationParameters = $this->getRelationParameters();

        if (! $relationParameters->historywork) {
            return false;
        }

        /**
         * if db-based history is disabled but there exists a session-based
         * history, use it
         */
        if (! $GLOBALS['cfg']['QueryHistoryDB']) {
            if (isset($_SESSION['sql_history'])) {
                return array_reverse($_SESSION['sql_history']);
            }

            return false;
        }

        $hist_query = '
             SELECT `db`,
                    `table`,
                    `sqlquery`,
                    `timevalue`
               FROM ' . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->history) . '
              WHERE `username` = \'' . $this->dbi->escapeString($username) . '\'
           ORDER BY `id` DESC';

        return $this->dbi->fetchResult($hist_query, null, null, DatabaseInterface::CONNECT_CONTROL);
    }

    /**
     * purges SQL history
     *
     * deletes entries that exceeds $cfg['QueryHistoryMax'], oldest first, for the
     * given user
     *
     * @param string $username the username
     *
     * @access public
     */
    public function purgeHistory($username): void
    {
        $relationParameters = $this->getRelationParameters();
        if (! $GLOBALS['cfg']['QueryHistoryDB'] || ! $relationParameters->historywork) {
            return;
        }

        $search_query = '
            SELECT `timevalue`
            FROM ' . Util::backquote($relationParameters->db)
                . '.' . Util::backquote($relationParameters->history) . '
            WHERE `username` = \'' . $this->dbi->escapeString($username) . '\'
            ORDER BY `timevalue` DESC
            LIMIT ' . $GLOBALS['cfg']['QueryHistoryMax'] . ', 1';

        $max_time = $this->dbi->fetchValue($search_query, 0, 0, DatabaseInterface::CONNECT_CONTROL);

        if (! $max_time) {
            return;
        }

        $this->queryAsControlUser(
            'DELETE FROM '
            . Util::backquote($relationParameters->db) . '.'
            . Util::backquote($relationParameters->history) . '
              WHERE `username` = \'' . $this->dbi->escapeString($username)
            . '\'
                AND `timevalue` <= \'' . $max_time . '\''
        );
    }

    /**
     * Prepares the dropdown for one mode
     *
     * @param array  $foreign the keys and values for foreigns
     * @param string $data    the current data of the dropdown
     * @param string $mode    the needed mode
     *
     * @return string[] the <option value=""><option>s
     *
     * @access protected
     */
    public function buildForeignDropdown(array $foreign, $data, $mode): array
    {
        $reloptions = [];

        // id-only is a special mode used when no foreign display column
        // is available
        if ($mode === 'id-content' || $mode === 'id-only') {
            // sort for id-content
            if ($GLOBALS['cfg']['NaturalOrder']) {
                uksort($foreign, 'strnatcasecmp');
            } else {
                ksort($foreign);
            }
        } elseif ($mode === 'content-id') {
            // sort for content-id
            if ($GLOBALS['cfg']['NaturalOrder']) {
                natcasesort($foreign);
            } else {
                asort($foreign);
            }
        }

        foreach ($foreign as $key => $value) {
            $key = (string) $key;
            $value = (string) $value;
            $data = (string) $data;

            if (mb_check_encoding($key, 'utf-8') && ! preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', $key)) {
                $selected = ($key == $data);
                // show as text if it's valid utf-8
                $key = htmlspecialchars($key);
            } else {
                $key = '0x' . bin2hex($key);
                if (str_contains($data, '0x')) {
                    $selected = ($key == trim($data));
                } else {
                    $selected = ($key == '0x' . $data);
                }
            }

            if (
                mb_check_encoding($value, 'utf-8')
                && ! preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', $value)
            ) {
                if (mb_strlen($value) <= $GLOBALS['cfg']['LimitChars']) {
                    // show as text if it's valid utf-8
                    $value = htmlspecialchars($value);
                } else {
                    // show as truncated text if it's valid utf-8
                    $value = htmlspecialchars(
                        mb_substr(
                            $value,
                            0,
                            (int) $GLOBALS['cfg']['LimitChars']
                        ) . '...'
                    );
                }
            } else {
                $value = '0x' . bin2hex($value);
            }

            $reloption = '<option value="' . $key . '"';

            if ($selected) {
                $reloption .= ' selected="selected"';
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
     * @param array[] $disp_row        array of the displayed row
     * @param string  $foreign_field   the foreign field
     * @param string  $foreign_display the foreign field to display
     * @param string  $data            the current data of the dropdown (field in row)
     * @param int     $max             maximum number of items in the dropdown
     *
     * @return string   the <option value=""><option>s
     *
     * @access public
     */
    public function foreignDropdown(
        array $disp_row,
        $foreign_field,
        string $foreign_display,
        $data,
        $max = null
    ): string {
        if ($max === null) {
            $max = $GLOBALS['cfg']['ForeignKeyMaxLimit'];
        }

        $foreign = [];

        // collect the data
        foreach ($disp_row as $relrow) {
            $key = $relrow[$foreign_field];

            // if the display field has been defined for this foreign table
            if ($foreign_display) {
                $value = $relrow[$foreign_display];
            } else {
                $value = '';
            }

            $foreign[$key] = $value;
        }

        // put the dropdown sections in correct order
        $top = [];
        $bottom = [];
        if ($foreign_display) {
            if (
                isset($GLOBALS['cfg']['ForeignKeyDropdownOrder'])
                && is_array($GLOBALS['cfg']['ForeignKeyDropdownOrder'])
            ) {
                if (
                    isset($GLOBALS['cfg']['ForeignKeyDropdownOrder'][0])
                    && is_scalar($GLOBALS['cfg']['ForeignKeyDropdownOrder'][0])
                    && strlen((string) $GLOBALS['cfg']['ForeignKeyDropdownOrder'][0]) > 0
                ) {
                    $top = $this->buildForeignDropdown(
                        $foreign,
                        $data,
                        (string) $GLOBALS['cfg']['ForeignKeyDropdownOrder'][0]
                    );
                }

                if (
                    isset($GLOBALS['cfg']['ForeignKeyDropdownOrder'][1])
                    && is_scalar($GLOBALS['cfg']['ForeignKeyDropdownOrder'][1])
                    && strlen((string) $GLOBALS['cfg']['ForeignKeyDropdownOrder'][1]) > 0
                ) {
                    $bottom = $this->buildForeignDropdown(
                        $foreign,
                        $data,
                        (string) $GLOBALS['cfg']['ForeignKeyDropdownOrder'][1]
                    );
                }
            } else {
                $top = $this->buildForeignDropdown($foreign, $data, 'id-content');
                $bottom = $this->buildForeignDropdown($foreign, $data, 'content-id');
            }
        } else {
            $top = $this->buildForeignDropdown($foreign, $data, 'id-only');
        }

        // beginning of dropdown
        $ret = '<option value="">&nbsp;</option>';
        $top_count = count($top);
        if ($max == -1 || $top_count < $max) {
            $ret .= implode('', $top);
            if ($foreign_display && $top_count > 0) {
                // this empty option is to visually mark the beginning of the
                // second series of values (bottom)
                $ret .= '<option value="">&nbsp;</option>';
            }
        }

        if ($foreign_display) {
            $ret .= implode('', $bottom);
        }

        return $ret;
    }

    /**
     * Gets foreign keys in preparation for a drop-down selector
     *
     * @param array|bool $foreigners     array of the foreign keys
     * @param string     $field          the foreign field name
     * @param bool       $override_total whether to override the total
     * @param string     $foreign_filter a possible filter
     * @param string     $foreign_limit  a possible LIMIT clause
     * @param bool       $get_total      optional, whether to get total num of rows
     *                                   in $foreignData['the_total;]
     *                                   (has an effect of performance)
     *
     * @return array<string, mixed>    data about the foreign keys
     * @psalm-return array{
     *     foreign_link: bool,
     *     the_total: mixed,
     *     foreign_display: string,
     *     disp_row: ?list<non-empty-array>,
     *     foreign_field: mixed
     * }
     *
     * @access public
     */
    public function getForeignData(
        $foreigners,
        $field,
        $override_total,
        string $foreign_filter,
        $foreign_limit,
        $get_total = false
    ): array {
        // we always show the foreign field in the drop-down; if a display
        // field is defined, we show it besides the foreign field
        $foreign_link = false;
        $disp_row = $foreign_display = $the_total = $foreign_field = null;
        do {
            if (! $foreigners) {
                break;
            }

            $foreigner = $this->searchColumnInForeigners($foreigners, $field);
            if ($foreigner == false) {
                break;
            }

            $foreign_db = $foreigner['foreign_db'];
            $foreign_table = $foreigner['foreign_table'];
            $foreign_field = $foreigner['foreign_field'];

            // Count number of rows in the foreign table. Currently we do
            // not use a drop-down if more than ForeignKeyMaxLimit rows in the
            // foreign table,
            // for speed reasons and because we need a better interface for this.
            //
            // We could also do the SELECT anyway, with a LIMIT, and ensure that
            // the current value of the field is one of the choices.

            // Check if table has more rows than specified by
            // $GLOBALS['cfg']['ForeignKeyMaxLimit']
            $moreThanLimit = $this->dbi->getTable($foreign_db, $foreign_table)
                ->checkIfMinRecordsExist($GLOBALS['cfg']['ForeignKeyMaxLimit']);

            if ($override_total === true || ! $moreThanLimit) {
                // foreign_display can be false if no display field defined:
                $foreign_display = $this->getDisplayField($foreign_db, $foreign_table);

                $f_query_main = 'SELECT ' . Util::backquote($foreign_field)
                    . (
                        $foreign_display === false
                            ? ''
                            : ', ' . Util::backquote($foreign_display)
                    );
                $f_query_from = ' FROM ' . Util::backquote($foreign_db)
                    . '.' . Util::backquote($foreign_table);
                $f_query_filter = $foreign_filter === '' ? '' : ' WHERE '
                    . Util::backquote($foreign_field)
                    . ' LIKE "%' . $this->dbi->escapeString($foreign_filter) . '%"'
                    . (
                        $foreign_display === false
                        ? ''
                        : ' OR ' . Util::backquote($foreign_display)
                        . ' LIKE "%' . $this->dbi->escapeString($foreign_filter)
                        . '%"'
                    );
                $f_query_order = $foreign_display === false ? '' : ' ORDER BY '
                    . Util::backquote($foreign_table) . '.'
                    . Util::backquote($foreign_display);

                $f_query_limit = $foreign_limit ?: '';

                if ($foreign_filter !== '') {
                    $the_total = $this->dbi->fetchValue('SELECT COUNT(*)' . $f_query_from . $f_query_filter);
                    if ($the_total === false) {
                        $the_total = 0;
                    }
                }

                $disp = $this->dbi->tryQuery(
                    $f_query_main . $f_query_from . $f_query_filter
                    . $f_query_order . $f_query_limit
                );
                if ($disp && $this->dbi->numRows($disp) > 0) {
                    // If a resultset has been created, pre-cache it in the $disp_row
                    // array. This helps us from not needing to use mysql_data_seek by
                    // accessing a pre-cached PHP array. Usually those resultsets are
                    // not that big, so a performance hit should not be expected.
                    $disp_row = [];
                    while ($single_disp_row = @$this->dbi->fetchAssoc($disp)) {
                        $disp_row[] = $single_disp_row;
                    }

                    @$this->dbi->freeResult($disp);
                } else {
                    // Either no data in the foreign table or
                    // user does not have select permission to foreign table/field
                    // Show an input field with a 'Browse foreign values' link
                    $disp_row = null;
                    $foreign_link = true;
                }
            } else {
                $disp_row = null;
                $foreign_link = true;
            }
        } while (false);

        if ($get_total && isset($foreign_db, $foreign_table)) {
            $the_total = $this->dbi->getTable($foreign_db, $foreign_table)
                ->countRecords(true);
        }

        return [
            'foreign_link' => $foreign_link,
            'the_total' => $the_total,
            'foreign_display' => $foreign_display ?: '',
            'disp_row' => $disp_row,
            'foreign_field' => $foreign_field,
        ];
    }

    /**
     * Rename a field in relation tables
     *
     * usually called after a column in a table was renamed
     *
     * @param string $db       database name
     * @param string $table    table name
     * @param string $field    old field name
     * @param string $new_name new field name
     */
    public function renameField($db, $table, $field, $new_name): void
    {
        $relationParameters = $this->getRelationParameters();

        if ($relationParameters->displaywork) {
            $table_query = 'UPDATE '
                . Util::backquote($relationParameters->db) . '.'
                . Util::backquote($relationParameters->tableInfo)
                . '   SET display_field = \'' . $this->dbi->escapeString($new_name) . '\''
                . ' WHERE db_name       = \'' . $this->dbi->escapeString($db)
                . '\''
                . '   AND table_name    = \'' . $this->dbi->escapeString($table)
                . '\''
                . '   AND display_field = \'' . $this->dbi->escapeString($field)
                . '\'';
            $this->queryAsControlUser($table_query);
        }

        if (! $relationParameters->relwork) {
            return;
        }

        $table_query = 'UPDATE '
            . Util::backquote($relationParameters->db) . '.'
            . Util::backquote($relationParameters->relation)
            . '   SET master_field = \'' . $this->dbi->escapeString($new_name) . '\''
            . ' WHERE master_db    = \'' . $this->dbi->escapeString($db)
            . '\''
            . '   AND master_table = \'' . $this->dbi->escapeString($table)
            . '\''
            . '   AND master_field = \'' . $this->dbi->escapeString($field)
            . '\'';
        $this->queryAsControlUser($table_query);

        $table_query = 'UPDATE '
            . Util::backquote($relationParameters->db) . '.'
            . Util::backquote($relationParameters->relation)
            . '   SET foreign_field = \'' . $this->dbi->escapeString($new_name) . '\''
            . ' WHERE foreign_db    = \'' . $this->dbi->escapeString($db)
            . '\''
            . '   AND foreign_table = \'' . $this->dbi->escapeString($table)
            . '\''
            . '   AND foreign_field = \'' . $this->dbi->escapeString($field)
            . '\'';
        $this->queryAsControlUser($table_query);
    }

    /**
     * Performs SQL query used for renaming table.
     *
     * @param string $table        Relation table to use
     * @param string $source_db    Source database name
     * @param string $target_db    Target database name
     * @param string $source_table Source table name
     * @param string $target_table Target table name
     * @param string $db_field     Name of database field
     * @param string $table_field  Name of table field
     */
    public function renameSingleTable(
        string $table,
        string $source_db,
        string $target_db,
        string $source_table,
        string $target_table,
        string $db_field,
        string $table_field
    ): void {
        $relationParameters = $this->getRelationParameters();
        $query = 'UPDATE '
            . Util::backquote($relationParameters->db) . '.'
            . Util::backquote((string) $relationParameters->{$table})
            . ' SET '
            . $db_field . ' = \'' . $this->dbi->escapeString($target_db)
            . '\', '
            . $table_field . ' = \'' . $this->dbi->escapeString($target_table)
            . '\''
            . ' WHERE '
            . $db_field . '  = \'' . $this->dbi->escapeString($source_db) . '\''
            . ' AND '
            . $table_field . ' = \'' . $this->dbi->escapeString($source_table)
            . '\'';
        $this->queryAsControlUser($query);
    }

    /**
     * Rename a table in relation tables
     *
     * usually called after table has been moved
     *
     * @param string $source_db    Source database name
     * @param string $target_db    Target database name
     * @param string $source_table Source table name
     * @param string $target_table Target table name
     */
    public function renameTable($source_db, $target_db, $source_table, $target_table): void
    {
        $relationParameters = $this->getRelationParameters();

        // Move old entries from PMA-DBs to new table
        if ($relationParameters->commwork) {
            $this->renameSingleTable(
                'columnInfo',
                $source_db,
                $target_db,
                $source_table,
                $target_table,
                'db_name',
                'table_name'
            );
        }

        // updating bookmarks is not possible since only a single table is
        // moved, and not the whole DB.

        if ($relationParameters->displaywork) {
            $this->renameSingleTable(
                'tableInfo',
                $source_db,
                $target_db,
                $source_table,
                $target_table,
                'db_name',
                'table_name'
            );
        }

        if ($relationParameters->relwork) {
            $this->renameSingleTable(
                'relation',
                $source_db,
                $target_db,
                $source_table,
                $target_table,
                'foreign_db',
                'foreign_table'
            );

            $this->renameSingleTable(
                'relation',
                $source_db,
                $target_db,
                $source_table,
                $target_table,
                'master_db',
                'master_table'
            );
        }

        if ($relationParameters->pdfwork) {
            if ($source_db == $target_db) {
                // rename within the database can be handled
                $this->renameSingleTable(
                    'tableCoords',
                    $source_db,
                    $target_db,
                    $source_table,
                    $target_table,
                    'db_name',
                    'table_name'
                );
            } else {
                // if the table is moved out of the database we can no longer keep the
                // record for table coordinate
                $remove_query = 'DELETE FROM '
                    . Util::backquote($relationParameters->db) . '.'
                    . Util::backquote($relationParameters->tableCoords)
                    . " WHERE db_name  = '" . $this->dbi->escapeString($source_db) . "'"
                    . " AND table_name = '" . $this->dbi->escapeString($source_table)
                    . "'";
                $this->queryAsControlUser($remove_query);
            }
        }

        if ($relationParameters->uiprefswork) {
            $this->renameSingleTable(
                'tableUiprefs',
                $source_db,
                $target_db,
                $source_table,
                $target_table,
                'db_name',
                'table_name'
            );
        }

        if (! $relationParameters->navwork) {
            return;
        }

        // update hidden items inside table
        $this->renameSingleTable(
            'navigationhiding',
            $source_db,
            $target_db,
            $source_table,
            $target_table,
            'db_name',
            'table_name'
        );

        // update data for hidden table
        $query = 'UPDATE '
            . Util::backquote($relationParameters->db) . '.'
            . Util::backquote($relationParameters->navigationhiding)
            . " SET db_name = '" . $this->dbi->escapeString($target_db)
            . "',"
            . " item_name = '" . $this->dbi->escapeString($target_table)
            . "'"
            . " WHERE db_name  = '" . $this->dbi->escapeString($source_db)
            . "'"
            . " AND item_name = '" . $this->dbi->escapeString($source_table)
            . "'"
            . " AND item_type = 'table'";
        $this->queryAsControlUser($query);
    }

    /**
     * Create a PDF page
     *
     * @param string|null $newpage name of the new PDF page
     * @param string      $db      database name
     *
     * @return int|false
     */
    public function createPage(?string $newpage, RelationParameters $relationParameters, $db)
    {
        $ins_query = 'INSERT INTO '
            . Util::backquote($relationParameters->db) . '.'
            . Util::backquote($relationParameters->pdfPages)
            . ' (db_name, page_descr)'
            . ' VALUES (\''
            . $this->dbi->escapeString($db) . '\', \''
            . $this->dbi->escapeString($newpage ?: __('no description')) . '\')';
        $this->queryAsControlUser($ins_query, false);

        return $this->dbi->insertId(DatabaseInterface::CONNECT_CONTROL);
    }

    /**
     * Get child table references for a table column.
     * This works only if 'DisableIS' is false. An empty array is returned otherwise.
     *
     * @param string $db     name of master table db.
     * @param string $table  name of master table.
     * @param string $column name of master table column.
     */
    public function getChildReferences($db, $table, $column = ''): array
    {
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $rel_query = 'SELECT `column_name`, `table_name`,'
                . ' `table_schema`, `referenced_column_name`'
                . ' FROM `information_schema`.`key_column_usage`'
                . " WHERE `referenced_table_name` = '"
                . $this->dbi->escapeString($table) . "'"
                . " AND `referenced_table_schema` = '"
                . $this->dbi->escapeString($db) . "'";
            if ($column) {
                $rel_query .= " AND `referenced_column_name` = '"
                    . $this->dbi->escapeString($column) . "'";
            }

            return $this->dbi->fetchResult(
                $rel_query,
                [
                    'referenced_column_name',
                    null,
                ]
            );
        }

        return [];
    }

    /**
     * Check child table references and foreign key for a table column.
     *
     * @param string     $db                    name of master table db.
     * @param string     $table                 name of master table.
     * @param string     $column                name of master table column.
     * @param array|null $foreigners_full       foreigners array for the whole table.
     * @param array|null $child_references_full child references for the whole table.
     *
     * @return array<string, mixed> telling about references if foreign key.
     * @psalm-return array{isEditable: bool, isForeignKey: bool, isReferenced: bool, references: string[]}
     */
    public function checkChildForeignReferences(
        $db,
        $table,
        $column,
        $foreigners_full = null,
        $child_references_full = null
    ): array {
        $column_status = [
            'isEditable' => true,
            'isReferenced' => false,
            'isForeignKey' => false,
            'references' => [],
        ];

        $foreigners = [];
        if ($foreigners_full !== null) {
            if (isset($foreigners_full[$column])) {
                $foreigners[$column] = $foreigners_full[$column];
            }

            if (isset($foreigners_full['foreign_keys_data'])) {
                $foreigners['foreign_keys_data'] = $foreigners_full['foreign_keys_data'];
            }
        } else {
            $foreigners = $this->getForeigners($db, $table, $column, 'foreign');
        }

        $foreigner = $this->searchColumnInForeigners($foreigners, $column);

        $child_references = [];
        if ($child_references_full !== null) {
            if (isset($child_references_full[$column])) {
                $child_references = $child_references_full[$column];
            }
        } else {
            $child_references = $this->getChildReferences($db, $table, $column);
        }

        if (count($child_references) > 0 || $foreigner) {
            $column_status['isEditable'] = false;
            if (count($child_references) > 0) {
                $column_status['isReferenced'] = true;
                foreach ($child_references as $columns) {
                    $column_status['references'][] = Util::backquote($columns['table_schema'])
                        . '.' . Util::backquote($columns['table_name']);
                }
            }

            if ($foreigner) {
                $column_status['isForeignKey'] = true;
            }
        }

        return $column_status;
    }

    /**
     * Search a table column in foreign data.
     *
     * @param array  $foreigners Table Foreign data
     * @param string $column     Column name
     *
     * @return array|false
     */
    public function searchColumnInForeigners(array $foreigners, $column)
    {
        if (isset($foreigners[$column])) {
            return $foreigners[$column];
        }

        if (! isset($foreigners['foreign_keys_data'])) {
            return false;
        }

        $foreigner = [];
        foreach ($foreigners['foreign_keys_data'] as $one_key) {
            $column_index = array_search($column, $one_key['index_list']);
            if ($column_index !== false) {
                $foreigner['foreign_field'] = $one_key['ref_index_list'][$column_index];
                $foreigner['foreign_db'] = $one_key['ref_db_name'] ?? $GLOBALS['db'];
                $foreigner['foreign_table'] = $one_key['ref_table_name'];
                $foreigner['constraint'] = $one_key['constraint'];
                $foreigner['on_update'] = $one_key['on_update'] ?? 'RESTRICT';
                $foreigner['on_delete'] = $one_key['on_delete'] ?? 'RESTRICT';

                return $foreigner;
            }
        }

        return false;
    }

    /**
     * Returns default PMA table names and their create queries.
     *
     * @return array<string,string> table name, create query
     */
    public function getDefaultPmaTableNames(array $tableNameReplacements): array
    {
        $pma_tables = [];
        $create_tables_file = (string) file_get_contents(SQL_DIR . 'create_tables.sql');

        $queries = explode(';', $create_tables_file);

        foreach ($queries as $query) {
            if (! preg_match('/CREATE TABLE IF NOT EXISTS `(.*)` \(/', $query, $table)) {
                continue;
            }

            // The following redundant cast is needed for PHPStan
            $tableName = (string) $table[1];

            // Replace the table name with another one
            if (isset($tableNameReplacements[$tableName])) {
                $query = str_replace($tableName, $tableNameReplacements[$tableName], $query);
            }

            $pma_tables[$tableName] = $query . ';';
        }

        return $pma_tables;
    }

    /**
     * Create a database to be used as configuration storage
     */
    public function createPmaDatabase(string $configurationStorageDbName): bool
    {
        $this->dbi->tryQuery(
            'CREATE DATABASE IF NOT EXISTS ' . Util::backquote($configurationStorageDbName),
            DatabaseInterface::CONNECT_CONTROL
        );

        $error = $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
        if (! $error) {
            // Re-build the cache to show the list of tables created or not
            // This is the case when the DB could be created but no tables just after
            // So just purge the cache and show the new configuration storage state
            $_SESSION['relation'][$GLOBALS['server']] = $this->checkRelationsParam();

            return true;
        }

        $GLOBALS['message'] = $error;

        if ($GLOBALS['errno'] === 1044) {
            $GLOBALS['message'] = sprintf(
                __(
                    'You do not have necessary privileges to create a database named'
                    . ' \'%s\'. You may go to \'Operations\' tab of any'
                    . ' database to set up the phpMyAdmin configuration storage there.'
                ),
                $configurationStorageDbName
            );
        }

        return false;
    }

    /**
     * Creates PMA tables in the given db, updates if already exists.
     *
     * @param string $db     database
     * @param bool   $create whether to create tables if they don't exist.
     */
    public function fixPmaTables($db, $create = true): void
    {
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

        $existingTables = $this->dbi->getTables($db, DatabaseInterface::CONNECT_CONTROL);

        /** @var array<string,string> $tableNameReplacements */
        $tableNameReplacements = [];

        // Build a map of replacements between default table names and name built by the user
        foreach ($tablesToFeatures as $table => $feature) {
            // Empty, we can not do anything about it
            if (empty($GLOBALS['cfg']['Server'][$feature])) {
                continue;
            }

            // Default table name, nothing to do
            if ($GLOBALS['cfg']['Server'][$feature] === $table) {
                continue;
            }

            // Set the replacement to transform the default table name into a custom name
            $tableNameReplacements[$table] = $GLOBALS['cfg']['Server'][$feature];
        }

        $createQueries = null;
        $foundOne = false;
        foreach ($tablesToFeatures as $table => $feature) {
            // Check if the table already exists
            // use the possible replaced name first and fallback on the table name
            // if no replacement exists
            if (! in_array($tableNameReplacements[$table] ?? $table, $existingTables)) {
                if ($create) {
                    if ($createQueries == null) { // first create
                        $createQueries = $this->getDefaultPmaTableNames($tableNameReplacements);
                        if (! $this->dbi->selectDb($db, DatabaseInterface::CONNECT_CONTROL)) {
                            $GLOBALS['message'] = $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);

                            return;
                        }
                    }

                    $this->dbi->tryQuery($createQueries[$table], DatabaseInterface::CONNECT_CONTROL);

                    $error = $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);
                    if ($error) {
                        $GLOBALS['message'] = $error;

                        return;
                    }

                    $foundOne = true;
                    if (empty($GLOBALS['cfg']['Server'][$feature])) {
                        // Do not override a user defined value, only fill if empty
                        $GLOBALS['cfg']['Server'][$feature] = $table;
                    }
                }
            } else {
                $foundOne = true;
                if (empty($GLOBALS['cfg']['Server'][$feature])) {
                    // Do not override a user defined value, only fill if empty
                    $GLOBALS['cfg']['Server'][$feature] = $table;
                }
            }
        }

        if (! $foundOne) {
            return;
        }

        $GLOBALS['cfg']['Server']['pmadb'] = $db;
        $_SESSION['relation'][$GLOBALS['server']] = $this->checkRelationsParam();

        $relationParameters = $this->getRelationParameters();
        if (! $relationParameters->recentwork && ! $relationParameters->favoritework) {
            return;
        }

        // Since configuration storage is updated, we need to
        // re-initialize the favorite and recent tables stored in the
        // session from the current configuration storage.
        if ($relationParameters->favoritework) {
            $fav_tables = RecentFavoriteTable::getInstance('favorite');
            $_SESSION['tmpval']['favoriteTables'][$GLOBALS['server']] = $fav_tables->getFromDb();
        }

        if ($relationParameters->recentwork) {
            $recent_tables = RecentFavoriteTable::getInstance('recent');
            $_SESSION['tmpval']['recentTables'][$GLOBALS['server']] = $recent_tables->getFromDb();
        }

        // Reload navi panel to update the recent/favorite lists.
        $GLOBALS['reload'] = true;
    }

    /**
     * Get Html for PMA tables fixing anchor.
     *
     * @param bool $allTables whether to create all tables
     * @param bool $createDb  whether to create the pmadb also
     *
     * @return string Html
     */
    public function getHtmlFixPmaTables($allTables, $createDb = false)
    {
        $retval = '';

        $params = [
            'db' => $GLOBALS['db'],
            'goto' => Url::getFromRoute('/database/operations'),
        ];

        if ($allTables) {
            if ($createDb) {
                $params['create_pmadb'] = 1;
                $message = Message::notice(
                    __(
                        '%sCreate%s a database named \'%s\' and setup the phpMyAdmin configuration storage there.'
                    )
                );
            } else {
                $params['fixall_pmadb'] = 1;
                $message = Message::notice(
                    __(
                        '%sCreate%s the phpMyAdmin configuration storage in the current database.'
                    )
                );
            }
        } else {
            $params['fix_pmadb'] = 1;
            $message = Message::notice(
                __('%sCreate%s missing phpMyAdmin configuration storage tables.')
            );
        }

        $message->addParamHtml(
            '<a href="' . Url::getFromRoute('/check-relations') . '" data-post="' . Url::getCommon($params, '') . '">'
        );
        $message->addParamHtml('</a>');

        if ($allTables && $createDb) {
            $message->addParam(
                $this->getConfigurationStorageDbName()
            );
        }

        return $retval . $message->getDisplay();
    }

    /**
     * Gets the relations info and status, depending on the condition
     *
     * @param bool   $condition whether to look for foreigners or not
     * @param string $db        database name
     * @param string $table     table name
     *
     * @return array ($res_rel, $have_rel)
     * @psalm-return array{array, bool}
     */
    public function getRelationsAndStatus(bool $condition, $db, $table)
    {
        $have_rel = false;
        $res_rel = [];
        if ($condition) {
            // Find which tables are related with the current one and write it in
            // an array
            $res_rel = $this->getForeigners($db, $table);

            $have_rel = count($res_rel) > 0;
        }

        return [
            $res_rel,
            $have_rel,
        ];
    }

    /**
     * Verifies if all the pmadb tables are defined
     */
    public function arePmadbTablesDefined(): bool
    {
        return ! (empty($GLOBALS['cfg']['Server']['bookmarktable'])
            || empty($GLOBALS['cfg']['Server']['relation'])
            || empty($GLOBALS['cfg']['Server']['table_info'])
            || empty($GLOBALS['cfg']['Server']['table_coords'])
            || empty($GLOBALS['cfg']['Server']['column_info'])
            || empty($GLOBALS['cfg']['Server']['pdf_pages'])
            || empty($GLOBALS['cfg']['Server']['history'])
            || empty($GLOBALS['cfg']['Server']['recent'])
            || empty($GLOBALS['cfg']['Server']['favorite'])
            || empty($GLOBALS['cfg']['Server']['table_uiprefs'])
            || empty($GLOBALS['cfg']['Server']['tracking'])
            || empty($GLOBALS['cfg']['Server']['userconfig'])
            || empty($GLOBALS['cfg']['Server']['users'])
            || empty($GLOBALS['cfg']['Server']['usergroups'])
            || empty($GLOBALS['cfg']['Server']['navigationhiding'])
            || empty($GLOBALS['cfg']['Server']['savedsearches'])
            || empty($GLOBALS['cfg']['Server']['central_columns'])
            || empty($GLOBALS['cfg']['Server']['designer_settings'])
            || empty($GLOBALS['cfg']['Server']['export_templates']));
    }

    /**
     * Get tables for foreign key constraint
     *
     * @param string $foreignDb        Database name
     * @param string $tblStorageEngine Table storage engine
     *
     * @return array Table names
     */
    public function getTables($foreignDb, $tblStorageEngine)
    {
        $tables = [];
        $tablesRows = $this->dbi->query(
            'SHOW TABLE STATUS FROM ' . Util::backquote($foreignDb),
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        while ($row = $this->dbi->fetchRow($tablesRows)) {
            if (! isset($row[1]) || mb_strtoupper($row[1]) != $tblStorageEngine) {
                continue;
            }

            $tables[] = $row[0];
        }

        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($tables, 'strnatcasecmp');
        }

        return $tables;
    }

    public function getConfigurationStorageDbName(): string
    {
        global $cfg;

        $cfgStorageDbName = $cfg['Server']['pmadb'] ?? '';

        // Use "phpmyadmin" as a default database name to check to keep the behavior consistent
        return empty($cfgStorageDbName) ? 'phpmyadmin' : $cfgStorageDbName;
    }
}
