<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Replication\Replication;
use PhpMyAdmin\Replication\ReplicationInfo;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracking\TrackedTable;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Tracking\TrackingChecker;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_search;
use function ceil;
use function count;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_string;
use function max;
use function mb_substr;
use function md5;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_replace;
use function strlen;
use function strtotime;
use function urlencode;

/**
 * Handles database structure logic
 */
class StructureController extends AbstractController
{
    /** @var int Number of tables */
    protected int $numTables = 0;

    /** @var int Current position in the list */
    protected int $position = 0;

    /** @var bool DB is information_schema */
    protected bool $dbIsSystemSchema = false;

    /** @var int Number of tables */
    protected int $totalNumTables = 0;

    /** @var mixed[] Tables in the database */
    protected array $tables = [];

    /** @var bool whether stats show or not */
    protected bool $isShowStats = false;

    private ReplicationInfo $replicationInfo;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Relation $relation,
        private Replication $replication,
        private DatabaseInterface $dbi,
        private TrackingChecker $trackingChecker,
    ) {
        parent::__construct($response, $template);

        $this->replicationInfo = new ReplicationInfo($this->dbi);
    }

    /**
     * Retrieves database information for further use.
     */
    private function getDatabaseInfo(ServerRequest $request): void
    {
        [$tables, $numTables, $totalNumTables] = Util::getDbInfo($request, $GLOBALS['db']);

        $this->tables = $tables;
        $this->numTables = $numTables;
        $this->position = Util::getTableListPosition($request, $GLOBALS['db']);
        $this->totalNumTables = $totalNumTables;

        /**
         * whether to display extended stats
         */
        $this->isShowStats = $GLOBALS['cfg']['ShowStats'];

        /**
         * whether selected db is information_schema
         */
        $this->dbIsSystemSchema = false;

        if (! Utilities::isSystemSchema($GLOBALS['db'])) {
            return;
        }

        $this->isShowStats = false;
        $this->dbIsSystemSchema = true;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] ??= null;

        $parameters = ['sort' => $_REQUEST['sort'] ?? null, 'sort_order' => $_REQUEST['sort_order'] ?? null];

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $this->addScriptFiles(['database/structure.js', 'table/change.js']);

        // Gets the database structure
        $this->getDatabaseInfo($request);

        // Checks if there are any tables to be shown on current page.
        // If there are no tables, the user is redirected to the last page
        // having any.
        if ($this->totalNumTables > 0 && $this->position > $this->totalNumTables) {
            $this->redirect('/database/structure', [
                'db' => $GLOBALS['db'],
                'pos' => max(0, $this->totalNumTables - $GLOBALS['cfg']['MaxTableList']),
                'reload' => 1,
            ]);
        }

        $this->replicationInfo->load($request->getParsedBodyParam('primary_connection'));
        $replicaInfo = $this->replicationInfo->getReplicaInfo();

        $pageSettings = new PageSettings('DbStructure');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        if ($this->numTables > 0) {
            $urlParams = ['pos' => $this->position, 'db' => $GLOBALS['db']];
            if (isset($parameters['sort'])) {
                $urlParams['sort'] = $parameters['sort'];
            }

            if (isset($parameters['sort_order'])) {
                $urlParams['sort_order'] = $parameters['sort_order'];
            }

            $listNavigator = Generator::getListNavigator(
                $this->totalNumTables,
                $this->position,
                $urlParams,
                Url::getFromRoute('/database/structure'),
                'frame_content',
                $GLOBALS['cfg']['MaxTableList'],
            );

            $tableList = $this->displayTableList($replicaInfo);
        }

        $createTable = '';
        if (! $this->dbIsSystemSchema) {
            $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
            $checkUserPrivileges->getPrivileges();

            $createTable = $this->template->render('database/create_table', ['db' => $GLOBALS['db']]);
        }

        $this->render('database/structure/index', [
            'database' => $GLOBALS['db'],
            'has_tables' => $this->numTables > 0,
            'list_navigator_html' => $listNavigator ?? '',
            'table_list_html' => $tableList ?? '',
            'is_system_schema' => $this->dbIsSystemSchema,
            'create_table_html' => $createTable,
        ]);
    }

    /** @param mixed[] $replicaInfo */
    protected function displayTableList(array $replicaInfo): string
    {
        $html = '';

        // filtering
        $html .= $this->template->render('filter', ['filter_value' => '']);

        $i = $sumEntries = 0;
        $overheadCheck = false;
        $createTimeAll = '';
        $updateTimeAll = '';
        $checkTimeAll = '';
        $numColumns = $GLOBALS['cfg']['PropertiesNumColumns'] > 1
            ? ceil($this->numTables / $GLOBALS['cfg']['PropertiesNumColumns']) + 1
            : 0;
        $rowCount = 0;
        $sumSize = 0;
        $overheadSize = 0;

        $hiddenFields = [];
        $overallApproxRows = false;
        $structureTableRows = [];
        $trackedTables = $this->trackingChecker->getTrackedTables($GLOBALS['db']);
        foreach ($this->tables as $currentTable) {
            // Get valid statistics whatever is the table type

            $dropQuery = '';
            $dropMessage = '';
            $overhead = '';
            $inputClass = ['checkall'];

            // Sets parameters for links
            $tableUrlParams = ['db' => $GLOBALS['db'], 'table' => $currentTable['TABLE_NAME']];
            // do not list the previous table's size info for a view

            [
                $currentTable,
                $formattedSize,
                $unit,
                $formattedOverhead,
                $overheadUnit,
                $overheadSize,
                $tableIsView,
                $sumSize,
            ] = $this->getStuffForEngineTypeTable($currentTable, $sumSize, $overheadSize);

            $curTable = $this->dbi
                ->getTable($GLOBALS['db'], $currentTable['TABLE_NAME']);
            if (! $curTable->isMerge()) {
                $sumEntries += $currentTable['TABLE_ROWS'];
            }

            $collationDefinition = '---';
            if (isset($currentTable['Collation'])) {
                $tableCollation = Charsets::findCollationByName(
                    $this->dbi,
                    $GLOBALS['cfg']['Server']['DisableIS'],
                    $currentTable['Collation'],
                );
                if ($tableCollation !== null) {
                    $collationDefinition = $this->template->render('database/structure/collation_definition', [
                        'valueTitle' => $tableCollation->getDescription(),
                        'value' => $tableCollation->getName(),
                    ]);
                }
            }

            if ($this->isShowStats) {
                $overhead = '-';
                if ($formattedOverhead != '') {
                    $overhead = $this->template->render('database/structure/overhead', [
                        'table_url_params' => $tableUrlParams,
                        'formatted_overhead' => $formattedOverhead,
                        'overhead_unit' => $overheadUnit,
                    ]);
                    $overheadCheck = true;
                    $inputClass[] = 'tbl-overhead';
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureCharset']) {
                $charset = '';
                if (isset($tableCollation)) {
                    $charset = $tableCollation->getCharset();
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
                $createTime = $currentTable['Create_time'] ?? '';
                if ($createTime && (! $createTimeAll || $createTime < $createTimeAll)) {
                    $createTimeAll = $createTime;
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
                $updateTime = $currentTable['Update_time'] ?? '';
                if ($updateTime && (! $updateTimeAll || $updateTime < $updateTimeAll)) {
                    $updateTimeAll = $updateTime;
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
                $checkTime = $currentTable['Check_time'] ?? '';
                if ($checkTime && (! $checkTimeAll || $checkTime < $checkTimeAll)) {
                    $checkTimeAll = $checkTime;
                }
            }

            $truename = $currentTable['TABLE_NAME'];

            $i++;

            $rowCount++;
            if ($tableIsView) {
                $hiddenFields[] = '<input type="hidden" name="views[]" value="'
                    . htmlspecialchars($currentTable['TABLE_NAME']) . '">';
            }

            /**
             * Always activate links for Browse, Search and Empty, even if
             * the icons are greyed, because
             * 1. for views, we don't know the number of rows at this point
             * 2. for tables, another source could have populated them since the
             *    page was generated
             *
             * I could have used the PHP ternary conditional operator but I find
             * the code easier to read without this operator.
             */
            $mayHaveRows = $currentTable['TABLE_ROWS'] > 0 || $tableIsView;

            if (! $this->dbIsSystemSchema) {
                $dropQuery = sprintf(
                    'DROP %s %s',
                    $tableIsView || $currentTable['ENGINE'] == null ? 'VIEW'
                    : 'TABLE',
                    Util::backquote(
                        $currentTable['TABLE_NAME'],
                    ),
                );
                $dropMessage = sprintf(
                    ($tableIsView || $currentTable['ENGINE'] == null
                        ? __('View %s has been dropped.')
                        : __('Table %s has been dropped.')),
                    str_replace(
                        ' ',
                        '&nbsp;',
                        htmlspecialchars($currentTable['TABLE_NAME']),
                    ),
                );
            }

            if ($numColumns > 0 && $this->numTables > $numColumns && ($rowCount % $numColumns) == 0) {
                $rowCount = 1;

                $html .= $this->template->render('database/structure/table_header', [
                    'db' => $GLOBALS['db'],
                    'db_is_system_schema' => $this->dbIsSystemSchema,
                    'replication' => $replicaInfo['status'],
                    'properties_num_columns' => $GLOBALS['cfg']['PropertiesNumColumns'],
                    'is_show_stats' => $this->isShowStats,
                    'show_charset' => $GLOBALS['cfg']['ShowDbStructureCharset'],
                    'show_comment' => $GLOBALS['cfg']['ShowDbStructureComment'],
                    'show_creation' => $GLOBALS['cfg']['ShowDbStructureCreation'],
                    'show_last_update' => $GLOBALS['cfg']['ShowDbStructureLastUpdate'],
                    'show_last_check' => $GLOBALS['cfg']['ShowDbStructureLastCheck'],
                    'num_favorite_tables' => $GLOBALS['cfg']['NumFavoriteTables'],
                    'structure_table_rows' => $structureTableRows,
                ]);
                $structureTableRows = [];
            }

            [$approxRows, $showSuperscript] = $this->isRowCountApproximated($currentTable, $tableIsView);

            [$do, $ignored] = $this->getReplicationStatus($replicaInfo, $truename);

            $structureTableRows[] = [
                'table_name_hash' => md5($currentTable['TABLE_NAME']),
                'db_table_name_hash' => md5($GLOBALS['db'] . '.' . $currentTable['TABLE_NAME']),
                'db' => $GLOBALS['db'],
                'curr' => $i,
                'input_class' => implode(' ', $inputClass),
                'table_is_view' => $tableIsView,
                'current_table' => $currentTable,
                'may_have_rows' => $mayHaveRows,
                'browse_table_label_title' => htmlspecialchars($currentTable['TABLE_COMMENT']),
                'browse_table_label_truename' => $truename,
                'empty_table_sql_query' => 'TRUNCATE ' . Util::backquote($currentTable['TABLE_NAME']),
                'empty_table_message_to_show' => urlencode(
                    sprintf(
                        __('Table %s has been emptied.'),
                        htmlspecialchars(
                            $currentTable['TABLE_NAME'],
                        ),
                    ),
                ),
                'tracking_icon' => $this->getTrackingIcon($truename, $trackedTables[$truename] ?? null),
                'server_replica_status' => $replicaInfo['status'],
                'table_url_params' => $tableUrlParams,
                'db_is_system_schema' => $this->dbIsSystemSchema,
                'drop_query' => $dropQuery,
                'drop_message' => $dropMessage,
                'collation' => $collationDefinition,
                'formatted_size' => $formattedSize,
                'unit' => $unit,
                'overhead' => $overhead,
                'create_time' => isset($createTime) && $createTime
                        ? Util::localisedDate(strtotime($createTime)) : '-',
                'update_time' => isset($updateTime) && $updateTime
                        ? Util::localisedDate(strtotime($updateTime)) : '-',
                'check_time' => isset($checkTime) && $checkTime
                        ? Util::localisedDate(strtotime($checkTime)) : '-',
                'charset' => $charset ?? '',
                'is_show_stats' => $this->isShowStats,
                'ignored' => $ignored,
                'do' => $do,
                'approx_rows' => $approxRows,
                'show_superscript' => $showSuperscript,
                'already_favorite' => $this->checkFavoriteTable($currentTable['TABLE_NAME']),
                'num_favorite_tables' => $GLOBALS['cfg']['NumFavoriteTables'],
                'properties_num_columns' => $GLOBALS['cfg']['PropertiesNumColumns'],
                'limit_chars' => $GLOBALS['cfg']['LimitChars'],
                'show_charset' => $GLOBALS['cfg']['ShowDbStructureCharset'],
                'show_comment' => $GLOBALS['cfg']['ShowDbStructureComment'],
                'show_creation' => $GLOBALS['cfg']['ShowDbStructureCreation'],
                'show_last_update' => $GLOBALS['cfg']['ShowDbStructureLastUpdate'],
                'show_last_check' => $GLOBALS['cfg']['ShowDbStructureLastCheck'],
            ];

            $overallApproxRows = $overallApproxRows || $approxRows;
        }

        $databaseCollation = [];
        $databaseCharset = '';
        $collation = Charsets::findCollationByName(
            $this->dbi,
            $GLOBALS['cfg']['Server']['DisableIS'],
            $this->dbi->getDbCollation($GLOBALS['db']),
        );
        if ($collation !== null) {
            $databaseCollation = ['name' => $collation->getName(), 'description' => $collation->getDescription()];
            $databaseCharset = $collation->getCharset();
        }

        $relationParameters = $this->relation->getRelationParameters();

        return $html . $this->template->render('database/structure/table_header', [
            'db' => $GLOBALS['db'],
            'db_is_system_schema' => $this->dbIsSystemSchema,
            'replication' => $replicaInfo['status'],
            'properties_num_columns' => $GLOBALS['cfg']['PropertiesNumColumns'],
            'is_show_stats' => $this->isShowStats,
            'show_charset' => $GLOBALS['cfg']['ShowDbStructureCharset'],
            'show_comment' => $GLOBALS['cfg']['ShowDbStructureComment'],
            'show_creation' => $GLOBALS['cfg']['ShowDbStructureCreation'],
            'show_last_update' => $GLOBALS['cfg']['ShowDbStructureLastUpdate'],
            'show_last_check' => $GLOBALS['cfg']['ShowDbStructureLastCheck'],
            'num_favorite_tables' => $GLOBALS['cfg']['NumFavoriteTables'],
            'structure_table_rows' => $structureTableRows,
            'body_for_table_summary' => [
                'num_tables' => $this->numTables,
                'server_replica_status' => $replicaInfo['status'],
                'db_is_system_schema' => $this->dbIsSystemSchema,
                'sum_entries' => $sumEntries,
                'database_collation' => $databaseCollation,
                'is_show_stats' => $this->isShowStats,
                'database_charset' => $databaseCharset,
                'sum_size' => $sumSize,
                'overhead_size' => $overheadSize,
                'create_time_all' => $createTimeAll ? Util::localisedDate(strtotime($createTimeAll)) : '-',
                'update_time_all' => $updateTimeAll ? Util::localisedDate(strtotime($updateTimeAll)) : '-',
                'check_time_all' => $checkTimeAll ? Util::localisedDate(strtotime($checkTimeAll)) : '-',
                'approx_rows' => $overallApproxRows,
                'num_favorite_tables' => $GLOBALS['cfg']['NumFavoriteTables'],
                'db' => $GLOBALS['db'],
                'properties_num_columns' => $GLOBALS['cfg']['PropertiesNumColumns'],
                'dbi' => $this->dbi,
                'show_charset' => $GLOBALS['cfg']['ShowDbStructureCharset'],
                'show_comment' => $GLOBALS['cfg']['ShowDbStructureComment'],
                'show_creation' => $GLOBALS['cfg']['ShowDbStructureCreation'],
                'show_last_update' => $GLOBALS['cfg']['ShowDbStructureLastUpdate'],
                'show_last_check' => $GLOBALS['cfg']['ShowDbStructureLastCheck'],
            ],
            'check_all_tables' => [
                'text_dir' => $GLOBALS['text_dir'],
                'overhead_check' => $overheadCheck,
                'db_is_system_schema' => $this->dbIsSystemSchema,
                'hidden_fields' => $hiddenFields,
                'disable_multi_table' => $GLOBALS['cfg']['DisableMultiTableMaintenance'],
                'central_columns_work' => $relationParameters->centralColumnsFeature !== null,
            ],
        ]);
    }

    /**
     * Returns the tracking icon if the table is tracked
     *
     * @return string HTML for tracking icon
     */
    protected function getTrackingIcon(string $table, TrackedTable|null $trackedTable): string
    {
        $trackingIcon = '';
        if (Tracker::isActive()) {
            if ($trackedTable !== null) {
                $trackingIcon = $this->template->render('database/structure/tracking_icon', [
                    'db' => $GLOBALS['db'],
                    'table' => $table,
                    'is_tracked' => $trackedTable->active,
                ]);
            }
        }

        return $trackingIcon;
    }

    /**
     * Returns whether the row count is approximated
     *
     * @param mixed[] $currentTable array containing details about the table
     * @param bool    $tableIsView  whether the table is a view
     *
     * @return mixed[]
     */
    protected function isRowCountApproximated(
        array $currentTable,
        bool $tableIsView,
    ): array {
        $approxRows = false;
        $showSuperscript = '';

        // there is a null value in the ENGINE
        // - when the table needs to be repaired, or
        // - when it's a view
        //  so ensure that we'll display "in use" below for a table
        //  that needs to be repaired
        if (isset($currentTable['TABLE_ROWS']) && ($currentTable['ENGINE'] != null || $tableIsView)) {
            // InnoDB/TokuDB table: we did not get an accurate row count
            $approxRows = ! $tableIsView
                && in_array($currentTable['ENGINE'], ['InnoDB', 'TokuDB'])
                && ! $currentTable['COUNTED'];

            if ($tableIsView && $currentTable['TABLE_ROWS'] >= $GLOBALS['cfg']['MaxExactCountViews']) {
                $approxRows = true;
                $showSuperscript = Generator::showHint(
                    Sanitize::sanitizeMessage(
                        sprintf(
                            __(
                                'This view has at least this number of rows. Please refer to %sdocumentation%s.',
                            ),
                            '[doc@cfg_MaxExactCountViews]',
                            '[/doc]',
                        ),
                    ),
                );
            }
        }

        return [$approxRows, $showSuperscript];
    }

    /**
     * Returns the replication status of the table.
     *
     * @param mixed[] $replicaInfo
     * @param string  $table       table name
     *
     * @return mixed[]
     */
    protected function getReplicationStatus(array $replicaInfo, string $table): array
    {
        $do = $ignored = false;
        if ($replicaInfo['status']) {
            $nbServReplicaDoDb = count($replicaInfo['Do_DB']);
            $nbServReplicaIgnoreDb = count($replicaInfo['Ignore_DB']);
            $searchDoDBInTruename = array_search($table, $replicaInfo['Do_DB']);
            $searchDoDBInDB = array_search($GLOBALS['db'], $replicaInfo['Do_DB']);

            $do = (is_string($searchDoDBInTruename) && strlen($searchDoDBInTruename) > 0)
                || (is_string($searchDoDBInDB) && strlen($searchDoDBInDB) > 0)
                || ($nbServReplicaDoDb == 0 && $nbServReplicaIgnoreDb == 0)
                || $this->hasTable($replicaInfo['Wild_Do_Table'], $table);

            $searchDb = array_search($GLOBALS['db'], $replicaInfo['Ignore_DB']);
            $searchTable = array_search($table, $replicaInfo['Ignore_Table']);
            $ignored = (is_string($searchTable) && strlen($searchTable) > 0)
                || (is_string($searchDb) && strlen($searchDb) > 0)
                || $this->hasTable($replicaInfo['Wild_Ignore_Table'], $table);
        }

        return [$do, $ignored];
    }

    /**
     * Function to check if a table is already in favorite list.
     *
     * @param string $currentTable current table
     */
    protected function checkFavoriteTable(string $currentTable): bool
    {
        // ensure $_SESSION['tmpval']['favoriteTables'] is initialized
        RecentFavoriteTable::getInstance('favorite');
        $favoriteTables = $_SESSION['tmpval']['favoriteTables'][$GLOBALS['server']] ?? [];
        foreach ($favoriteTables as $value) {
            if ($value['db'] == $GLOBALS['db'] && $value['table'] == $currentTable) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find table with truename
     *
     * @param mixed[] $db       DB to look into
     * @param string  $truename Table name
     */
    protected function hasTable(array $db, string $truename): bool
    {
        foreach ($db as $dbTable) {
            if (
                $GLOBALS['db'] == $this->replication->extractDbOrTable($dbTable)
                && preg_match(
                    '@^' .
                    preg_quote(mb_substr($this->replication->extractDbOrTable($dbTable, 'table'), 0, -1), '@') . '@',
                    $truename,
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the value set for ENGINE table,
     *
     * @internal param bool $table_is_view whether table is view or not
     *
     * @param mixed[] $currentTable current table
     * @param int     $sumSize      total table size
     * @param int     $overheadSize overhead size
     *
     * @return mixed[]
     */
    protected function getStuffForEngineTypeTable(
        array $currentTable,
        int $sumSize,
        int $overheadSize,
    ): array {
        $formattedSize = '-';
        $unit = '';
        $formattedOverhead = '';
        $overheadUnit = '';
        $tableIsView = false;

        switch ($currentTable['ENGINE']) {
            // MyISAM, ISAM or Heap table: Row count, data size and index size
            // are accurate; data size is accurate for ARCHIVE
            case 'MyISAM':
            case 'ISAM':
            case 'HEAP':
            case 'MEMORY':
            case 'ARCHIVE':
            case 'Aria':
            case 'Maria':
                [
                    $currentTable,
                    $formattedSize,
                    $unit,
                    $formattedOverhead,
                    $overheadUnit,
                    $overheadSize,
                    $sumSize,
                ] = $this->getValuesForAriaTable(
                    $currentTable,
                    $sumSize,
                    $overheadSize,
                    $formattedSize,
                    $unit,
                    $formattedOverhead,
                    $overheadUnit,
                );
                break;
            case 'InnoDB':
            case 'PBMS':
            case 'TokuDB':
                // InnoDB table: Row count is not accurate but data and index sizes are.
                // PBMS table in Drizzle: TABLE_ROWS is taken from table cache,
                // so it may be unavailable
                [$currentTable, $formattedSize, $unit, $sumSize] = $this->getValuesForInnodbTable(
                    $currentTable,
                    $sumSize,
                );
                break;
            // Mysql 5.0.x (and lower) uses MRG_MyISAM
            // and MySQL 5.1.x (and higher) uses MRG_MYISAM
            // Both are aliases for MERGE
            case 'MRG_MyISAM':
            case 'MRG_MYISAM':
            case 'MERGE':
            case 'BerkeleyDB':
                // Merge or BerkleyDB table: Only row count is accurate.
                if ($this->isShowStats) {
                    $formattedSize = ' - ';
                }

                break;
            // for a view, the ENGINE is sometimes reported as null,
            // or on some servers it's reported as "SYSTEM VIEW"
            case null:
            case 'SYSTEM VIEW':
                // possibly a view, do nothing
                break;
            case 'Mroonga':
                // The idea is to show the size only if Mroonga is available,
                // in other case the old unknown message will appear
                if (StorageEngine::hasMroongaEngine()) {
                    [$currentTable, $formattedSize, $unit, $sumSize] = $this->getValuesForMroongaTable(
                        $currentTable,
                        $sumSize,
                    );
                    break;
                }
                // no break, go to default case
            default:
                // Unknown table type.
                if ($this->isShowStats) {
                    $formattedSize = __('unknown');
                }
        }

        if ($currentTable['TABLE_TYPE'] === 'VIEW' || $currentTable['TABLE_TYPE'] === 'SYSTEM VIEW') {
            // countRecords() takes care of $cfg['MaxExactCountViews']
            $currentTable['TABLE_ROWS'] = $this->dbi
                ->getTable($GLOBALS['db'], $currentTable['TABLE_NAME'])
                ->countRecords(true);
            $tableIsView = true;
        }

        return [
            $currentTable,
            $formattedSize,
            $unit,
            $formattedOverhead,
            $overheadUnit,
            $overheadSize,
            $tableIsView,
            $sumSize,
        ];
    }

    /**
     * Get values for ARIA/MARIA tables
     *
     * @param mixed[] $currentTable      current table
     * @param int     $sumSize           sum size
     * @param int     $overheadSize      overhead size
     * @param string  $formattedSize     formatted size
     * @param string  $unit              unit
     * @param string  $formattedOverhead overhead formatted
     * @param string  $overheadUnit      overhead unit
     *
     * @return mixed[]
     */
    protected function getValuesForAriaTable(
        array $currentTable,
        int $sumSize,
        int $overheadSize,
        string $formattedSize,
        string $unit,
        string $formattedOverhead,
        string $overheadUnit,
    ): array {
        if ($this->dbIsSystemSchema) {
            $currentTable['Rows'] = $this->dbi
                ->getTable($GLOBALS['db'], $currentTable['Name'])
                ->countRecords();
        }

        if ($this->isShowStats) {
            /** @var int $tblsize */
            $tblsize = $currentTable['Data_length']
                + $currentTable['Index_length'];
            $sumSize += $tblsize;
            [$formattedSize, $unit] = Util::formatByteDown($tblsize, 3, $tblsize > 0 ? 1 : 0);
            if (isset($currentTable['Data_free']) && $currentTable['Data_free'] > 0) {
                [$formattedOverhead, $overheadUnit] = Util::formatByteDown($currentTable['Data_free'], 3, 1);
                $overheadSize += $currentTable['Data_free'];
            }
        }

        return [$currentTable, $formattedSize, $unit, $formattedOverhead, $overheadUnit, $overheadSize, $sumSize];
    }

    /**
     * Get values for InnoDB table
     *
     * @param mixed[] $currentTable current table
     * @param int     $sumSize      sum size
     *
     * @return mixed[]
     */
    protected function getValuesForInnodbTable(
        array $currentTable,
        int $sumSize,
    ): array {
        $formattedSize = $unit = '';

        if (
            (in_array($currentTable['ENGINE'], ['InnoDB', 'TokuDB'])
            && $currentTable['TABLE_ROWS'] < $GLOBALS['cfg']['MaxExactCount'])
            || ! isset($currentTable['TABLE_ROWS'])
        ) {
            $currentTable['COUNTED'] = true;
            $currentTable['TABLE_ROWS'] = $this->dbi
                ->getTable($GLOBALS['db'], $currentTable['TABLE_NAME'])
                ->countRecords(true);
        } else {
            $currentTable['COUNTED'] = false;
        }

        if ($this->isShowStats) {
            /** @var int $tblsize */
            $tblsize = $currentTable['Data_length']
                + $currentTable['Index_length'];
            $sumSize += $tblsize;
            [$formattedSize, $unit] = Util::formatByteDown($tblsize, 3, ($tblsize > 0 ? 1 : 0));
        }

        return [$currentTable, $formattedSize, $unit, $sumSize];
    }

    /**
     * Get values for Mroonga table
     *
     * @param mixed[] $currentTable current table
     * @param int     $sumSize      sum size
     *
     * @return mixed[]
     */
    protected function getValuesForMroongaTable(
        array $currentTable,
        int $sumSize,
    ): array {
        $formattedSize = '';
        $unit = '';

        if ($this->isShowStats) {
            /** @var int $tblsize */
            $tblsize = $currentTable['Data_length'] + $currentTable['Index_length'];
            $sumSize += $tblsize;
            [$formattedSize, $unit] = Util::formatByteDown($tblsize, 3, ($tblsize > 0 ? 1 : 0));
        }

        return [$currentTable, $formattedSize, $unit, $sumSize];
    }
}
