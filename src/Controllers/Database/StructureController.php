<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Utilities;
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
use function strtotime;
use function urlencode;

/**
 * Handles database structure logic
 */
final class StructureController extends AbstractController
{
    /** @var int Number of tables */
    private int $numTables = 0;

    /** @var int Current position in the list */
    private int $position = 0;

    /** @var bool DB is information_schema */
    private bool $dbIsSystemSchema = false;

    /** @var int Number of tables */
    private int $totalNumTables = 0;

    /** @var mixed[] Tables in the database */
    private array $tables = [];

    /** @var bool whether stats show or not */
    private bool $isShowStats = false;

    private ReplicationInfo $replicationInfo;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Relation $relation,
        private Replication $replication,
        private DatabaseInterface $dbi,
        private TrackingChecker $trackingChecker,
        private PageSettings $pageSettings,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);

        $this->replicationInfo = new ReplicationInfo($this->dbi);
    }

    /**
     * Retrieves database information for further use.
     */
    private function getDatabaseInfo(ServerRequest $request): void
    {
        [$tables, $totalNumTables] = Util::getDbInfo($request, Current::$database);

        $this->tables = $tables;
        $this->numTables = count($tables);
        $this->position = Util::getTableListPosition($request, Current::$database);
        $this->totalNumTables = $totalNumTables;

        /**
         * whether to display extended stats
         */
        $this->isShowStats = Config::getInstance()->settings['ShowStats'];

        /**
         * whether selected db is information_schema
         */
        $this->dbIsSystemSchema = false;

        if (! Utilities::isSystemSchema(Current::$database)) {
            return;
        }

        $this->isShowStats = false;
        $this->dbIsSystemSchema = true;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] ??= null;

        $parameters = ['sort' => $_REQUEST['sort'] ?? null, 'sort_order' => $_REQUEST['sort_order'] ?? null];

        if (! $this->checkParameters(['db'])) {
            return;
        }

        $config = Config::getInstance();
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

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
                'db' => Current::$database,
                'pos' => max(0, $this->totalNumTables - $config->settings['MaxTableList']),
                'reload' => 1,
            ]);
        }

        $this->replicationInfo->load($request->getParsedBodyParam('primary_connection'));
        $replicaInfo = $this->replicationInfo->getReplicaInfo();

        $this->pageSettings->init('DbStructure');
        $this->response->addHTML($this->pageSettings->getErrorHTML());
        $this->response->addHTML($this->pageSettings->getHTML());

        if ($this->numTables > 0) {
            $urlParams = ['pos' => $this->position, 'db' => Current::$database];
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
                $config->settings['MaxTableList'],
            );

            $tableList = $this->displayTableList($replicaInfo);
        }

        $createTable = '';
        if (! $this->dbIsSystemSchema) {
            $createTable = $this->template->render('database/create_table', ['db' => Current::$database]);
        }

        $this->render('database/structure/index', [
            'database' => Current::$database,
            'has_tables' => $this->numTables > 0,
            'list_navigator_html' => $listNavigator ?? '',
            'table_list_html' => $tableList ?? '',
            'is_system_schema' => $this->dbIsSystemSchema,
            'create_table_html' => $createTable,
        ]);
    }

    /** @param mixed[] $replicaInfo */
    private function displayTableList(array $replicaInfo): string
    {
        $html = '';

        // filtering
        $html .= $this->template->render('filter', ['filter_value' => '']);

        $i = $sumEntries = 0;
        $overheadCheck = false;
        $createTimeAll = '';
        $updateTimeAll = '';
        $checkTimeAll = '';
        $config = Config::getInstance();
        $numColumns = $config->settings['PropertiesNumColumns'] > 1
            ? ceil($this->numTables / $config->settings['PropertiesNumColumns']) + 1
            : 0;
        $rowCount = 0;
        $sumSize = 0;
        $overheadSize = 0;

        $hiddenFields = [];
        $overallApproxRows = false;
        $structureTableRows = [];
        $trackedTables = $this->trackingChecker->getTrackedTables(Current::$database);
        $recentFavoriteTables = RecentFavoriteTables::getInstance(TableType::Favorite);
        foreach ($this->tables as $currentTable) {
            // Get valid statistics whatever is the table type

            $dropQuery = '';
            $dropMessage = '';
            $overhead = '';
            $inputClass = ['checkall'];

            // Sets parameters for links
            $tableUrlParams = ['db' => Current::$database, 'table' => $currentTable['TABLE_NAME']];
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
                ->getTable(Current::$database, $currentTable['TABLE_NAME']);
            if (! $curTable->isMerge()) {
                $sumEntries += $currentTable['TABLE_ROWS'];
            }

            $collationDefinition = '---';
            if (isset($currentTable['Collation'])) {
                $tableCollation = Charsets::findCollationByName(
                    $this->dbi,
                    $config->selectedServer['DisableIS'],
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

            if ($config->settings['ShowDbStructureCharset']) {
                $charset = '';
                if (isset($tableCollation)) {
                    $charset = $tableCollation->getCharset();
                }
            }

            if ($config->settings['ShowDbStructureCreation']) {
                $createTime = $currentTable['Create_time'] ?? '';
                if ($createTime && (! $createTimeAll || $createTime < $createTimeAll)) {
                    $createTimeAll = $createTime;
                }
            }

            if ($config->settings['ShowDbStructureLastUpdate']) {
                $updateTime = $currentTable['Update_time'] ?? '';
                if ($updateTime && (! $updateTimeAll || $updateTime < $updateTimeAll)) {
                    $updateTimeAll = $updateTime;
                }
            }

            if ($config->settings['ShowDbStructureLastCheck']) {
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

            if ($numColumns > 0 && $this->numTables > $numColumns && ($rowCount % $numColumns) === 0) {
                $rowCount = 1;

                $html .= $this->template->render('database/structure/table_header', [
                    'db' => Current::$database,
                    'db_is_system_schema' => $this->dbIsSystemSchema,
                    'replication' => $replicaInfo['status'],
                    'properties_num_columns' => $config->settings['PropertiesNumColumns'],
                    'is_show_stats' => $this->isShowStats,
                    'show_charset' => $config->settings['ShowDbStructureCharset'],
                    'show_comment' => $config->settings['ShowDbStructureComment'],
                    'show_creation' => $config->settings['ShowDbStructureCreation'],
                    'show_last_update' => $config->settings['ShowDbStructureLastUpdate'],
                    'show_last_check' => $config->settings['ShowDbStructureLastCheck'],
                    'num_favorite_tables' => $config->settings['NumFavoriteTables'],
                    'structure_table_rows' => $structureTableRows,
                ]);
                $structureTableRows = [];
            }

            [$approxRows, $showSuperscript] = $this->isRowCountApproximated($currentTable, $tableIsView);

            [$do, $ignored] = $this->getReplicationStatus($replicaInfo, $truename);

            $structureTableRows[] = [
                'table_name_hash' => md5($currentTable['TABLE_NAME']),
                'db_table_name_hash' => md5(Current::$database . '.' . $currentTable['TABLE_NAME']),
                'db' => Current::$database,
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
                'already_favorite' => $recentFavoriteTables->contains(
                    new RecentFavoriteTable(
                        DatabaseName::from(Current::$database),
                        TableName::from($currentTable['TABLE_NAME']),
                    ),
                ),
                'num_favorite_tables' => $config->settings['NumFavoriteTables'],
                'properties_num_columns' => $config->settings['PropertiesNumColumns'],
                'limit_chars' => $config->settings['LimitChars'],
                'show_charset' => $config->settings['ShowDbStructureCharset'],
                'show_comment' => $config->settings['ShowDbStructureComment'],
                'show_creation' => $config->settings['ShowDbStructureCreation'],
                'show_last_update' => $config->settings['ShowDbStructureLastUpdate'],
                'show_last_check' => $config->settings['ShowDbStructureLastCheck'],
            ];

            $overallApproxRows = $overallApproxRows || $approxRows;
        }

        $databaseCollation = [];
        $databaseCharset = '';
        $collation = Charsets::findCollationByName(
            $this->dbi,
            $config->selectedServer['DisableIS'],
            $this->dbi->getDbCollation(Current::$database),
        );
        if ($collation !== null) {
            $databaseCollation = ['name' => $collation->getName(), 'description' => $collation->getDescription()];
            $databaseCharset = $collation->getCharset();
        }

        $relationParameters = $this->relation->getRelationParameters();

        $defaultStorageEngine = '';
        if ($config->settings['PropertiesNumColumns'] < 2) {
            // MySQL <= 5.5.2
            $defaultStorageEngine = $this->dbi->fetchValue('SELECT @@storage_engine;');
            if (! is_string($defaultStorageEngine) || $defaultStorageEngine === '') {
                // MySQL >= 5.5.3
                $defaultStorageEngine = $this->dbi->fetchValue('SELECT @@default_storage_engine;');
            }
        }

        return $html . $this->template->render('database/structure/table_header', [
            'db' => Current::$database,
            'db_is_system_schema' => $this->dbIsSystemSchema,
            'replication' => $replicaInfo['status'],
            'properties_num_columns' => $config->settings['PropertiesNumColumns'],
            'is_show_stats' => $this->isShowStats,
            'show_charset' => $config->settings['ShowDbStructureCharset'],
            'show_comment' => $config->settings['ShowDbStructureComment'],
            'show_creation' => $config->settings['ShowDbStructureCreation'],
            'show_last_update' => $config->settings['ShowDbStructureLastUpdate'],
            'show_last_check' => $config->settings['ShowDbStructureLastCheck'],
            'num_favorite_tables' => $config->settings['NumFavoriteTables'],
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
                'num_favorite_tables' => $config->settings['NumFavoriteTables'],
                'db' => Current::$database,
                'properties_num_columns' => $config->settings['PropertiesNumColumns'],
                'default_storage_engine' => $defaultStorageEngine,
                'show_charset' => $config->settings['ShowDbStructureCharset'],
                'show_comment' => $config->settings['ShowDbStructureComment'],
                'show_creation' => $config->settings['ShowDbStructureCreation'],
                'show_last_update' => $config->settings['ShowDbStructureLastUpdate'],
                'show_last_check' => $config->settings['ShowDbStructureLastCheck'],
            ],
            'check_all_tables' => [
                'text_dir' => LanguageManager::$textDir,
                'overhead_check' => $overheadCheck,
                'db_is_system_schema' => $this->dbIsSystemSchema,
                'hidden_fields' => $hiddenFields,
                'disable_multi_table' => $config->settings['DisableMultiTableMaintenance'],
                'central_columns_work' => $relationParameters->centralColumnsFeature !== null,
            ],
        ]);
    }

    /**
     * Returns the tracking icon if the table is tracked
     *
     * @return string HTML for tracking icon
     */
    private function getTrackingIcon(string $table, TrackedTable|null $trackedTable): string
    {
        $trackingIcon = '';
        if (Tracker::isActive() && $trackedTable !== null) {
            $trackingIcon = $this->template->render('database/structure/tracking_icon', [
                'db' => Current::$database,
                'table' => $table,
                'is_tracked' => $trackedTable->active,
            ]);
        }

        return $trackingIcon;
    }

    /**
     * Returns whether the row count is approximated
     *
     * @param mixed[] $currentTable array containing details about the table
     * @param bool    $tableIsView  whether the table is a view
     *
     * @return array{bool, string}
     */
    private function isRowCountApproximated(
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
                && in_array($currentTable['ENGINE'], ['InnoDB', 'TokuDB'], true)
                && ! $currentTable['COUNTED'];

            if ($tableIsView && $currentTable['TABLE_ROWS'] >= Config::getInstance()->settings['MaxExactCountViews']) {
                $approxRows = true;
                $showSuperscript = Generator::showHint(
                    Sanitize::convertBBCode(
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
     * @return array{bool, bool}
     */
    private function getReplicationStatus(array $replicaInfo, string $table): array
    {
        $do = $ignored = false;
        if ($replicaInfo['status']) {
            $nbServReplicaDoDb = count($replicaInfo['Do_DB']);
            $nbServReplicaIgnoreDb = count($replicaInfo['Ignore_DB']);
            $searchDoDBInTruename = array_search($table, $replicaInfo['Do_DB']);
            $searchDoDBInDB = array_search(Current::$database, $replicaInfo['Do_DB']);

            $do = (is_string($searchDoDBInTruename) && $searchDoDBInTruename !== '')
                || (is_string($searchDoDBInDB) && $searchDoDBInDB !== '')
                || ($nbServReplicaDoDb == 0 && $nbServReplicaIgnoreDb == 0)
                || $this->hasTable($replicaInfo['Wild_Do_Table'], $table);

            $searchDb = array_search(Current::$database, $replicaInfo['Ignore_DB']);
            $searchTable = array_search($table, $replicaInfo['Ignore_Table']);
            $ignored = (is_string($searchTable) && $searchTable !== '')
                || (is_string($searchDb) && $searchDb !== '')
                || $this->hasTable($replicaInfo['Wild_Ignore_Table'], $table);
        }

        return [$do, $ignored];
    }

    /**
     * Find table with truename
     *
     * @param mixed[] $db       DB to look into
     * @param string  $truename Table name
     */
    private function hasTable(array $db, string $truename): bool
    {
        foreach ($db as $dbTable) {
            if (
                Current::$database == $this->replication->extractDbOrTable($dbTable)
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
    private function getStuffForEngineTypeTable(
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
                ->getTable(Current::$database, $currentTable['TABLE_NAME'])
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
    private function getValuesForAriaTable(
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
                ->getTable(Current::$database, $currentTable['Name'])
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
    private function getValuesForInnodbTable(
        array $currentTable,
        int $sumSize,
    ): array {
        $formattedSize = $unit = '';

        if (
            (in_array($currentTable['ENGINE'], ['InnoDB', 'TokuDB'], true)
            && $currentTable['TABLE_ROWS'] < Config::getInstance()->settings['MaxExactCount'])
            || ! isset($currentTable['TABLE_ROWS'])
        ) {
            $currentTable['COUNTED'] = true;
            $currentTable['TABLE_ROWS'] = $this->dbi
                ->getTable(Current::$database, $currentTable['TABLE_NAME'])
                ->countRecords(true);
        } else {
            $currentTable['COUNTED'] = false;
        }

        if ($this->isShowStats) {
            /** @var int $tblsize */
            $tblsize = $currentTable['Data_length']
                + $currentTable['Index_length'];
            $sumSize += $tblsize;
            [$formattedSize, $unit] = Util::formatByteDown($tblsize, 3, $tblsize > 0 ? 1 : 0);
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
    private function getValuesForMroongaTable(
        array $currentTable,
        int $sumSize,
    ): array {
        $formattedSize = '';
        $unit = '';

        if ($this->isShowStats) {
            /** @var int $tblsize */
            $tblsize = $currentTable['Data_length'] + $currentTable['Index_length'];
            $sumSize += $tblsize;
            [$formattedSize, $unit] = Util::formatByteDown($tblsize, 3, $tblsize > 0 ? 1 : 0);
        }

        return [$currentTable, $formattedSize, $unit, $sumSize];
    }
}
