<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use DateTimeImmutable;
use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Replication\Replication;
use PhpMyAdmin\Replication\ReplicationInfo;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracking\TrackedTable;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Tracking\TrackingChecker;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use Throwable;

use function __;
use function array_search;
use function ceil;
use function count;
use function implode;
use function in_array;
use function is_numeric;
use function is_scalar;
use function is_string;
use function max;
use function mb_substr;
use function md5;
use function preg_match;
use function preg_quote;
use function sprintf;
use function strnatcasecmp;
use function uksort;

/**
 * Handles database structure logic
 */
#[Route('/database/structure', ['GET', 'POST'])]
final class StructureController implements InvocableController
{
    /** @var int Number of tables */
    private int $numTables = 0;

    /** @var int Current position in the list */
    private int $position = 0;

    /** @var bool DB is information_schema */
    private bool $dbIsSystemSchema = false;

    /** @var int Number of tables */
    private int $totalNumTables = 0;

    /** @var (string|int|null)[][] Tables in the database */
    private array $tables = [];

    /** @var bool whether stats show or not */
    private bool $isShowStats = false;

    private ReplicationInfo $replicationInfo;

    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly Relation $relation,
        private readonly Replication $replication,
        private readonly DatabaseInterface $dbi,
        private readonly TrackingChecker $trackingChecker,
        private readonly PageSettings $pageSettings,
        private readonly DbTableExists $dbTableExists,
        private readonly Config $config,
    ) {
        $this->replicationInfo = new ReplicationInfo($this->dbi);
    }

    /**
     * Retrieves database information for further use.
     */
    private function getDatabaseInfo(ServerRequest $request): void
    {
        $this->position = $this->getTableListPosition($request->getParam('pos'), Current::$database);

        // Special speedup for newer MySQL Versions (in 4.0 format changed)
        if ($this->config->settings['SkipLockedTables'] === true) {
            $tables = $this->getTablesWhenOpen(Current::$database);
            $totalNumTables = count($tables);
        } else {
            [$tables, $totalNumTables] = $this->getDbInfo(
                Current::$database,
                $request->getParam('sort'),
                $request->getParam('sort_order'),
                $request->getParam('tbl_group'),
                $request->getParam('tbl_type'),
            );
        }

        $this->tables = $tables;
        $this->numTables = count($tables);
        $this->totalNumTables = $totalNumTables ?? count($tables);

        /**
         * whether to display extended stats
         */
        $this->isShowStats = $this->config->settings['ShowStats'];

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

    public function __invoke(ServerRequest $request): Response
    {
        $parameters = ['sort' => $_REQUEST['sort'] ?? null, 'sort_order' => $_REQUEST['sort_order'] ?? null];

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
        }

        $this->response->addScriptFiles(['database/structure.js', 'table/change.js']);

        // Gets the database structure
        $this->getDatabaseInfo($request);

        // Checks if there are any tables to be shown on current page.
        // If there are no tables, the user is redirected to the last page
        // having any.
        if ($this->totalNumTables > 0 && $this->position > $this->totalNumTables) {
            return $this->response->redirectToRoute('/database/structure', [
                'db' => Current::$database,
                'pos' => max(0, $this->totalNumTables - $this->config->settings['MaxTableList']),
                'reload' => 1,
            ]);
        }

        $this->replicationInfo->load($request->getParsedBodyParamAsStringOrNull('primary_connection'));
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
                $this->config->settings['MaxTableList'],
            );

            $tableList = $this->displayTableList($replicaInfo);
        }

        $createTable = '';
        if (! $this->dbIsSystemSchema) {
            $createTable = $this->template->render('database/create_table', ['db' => Current::$database]);
        }

        $this->response->render('database/structure/index', [
            'database' => Current::$database,
            'has_tables' => $this->numTables > 0,
            'list_navigator_html' => $listNavigator ?? '',
            'table_list_html' => $tableList ?? '',
            'is_system_schema' => $this->dbIsSystemSchema,
            'create_table_html' => $createTable,
        ]);

        return $this->response->response();
    }

    /** @param mixed[] $replicaInfo */
    private function displayTableList(array $replicaInfo): string
    {
        $html = '';

        // filtering
        $html .= $this->template->render('filter', ['filter_value' => '']);

        $i = $sumEntries = 0;
        $overheadCheck = false;
        $createTimeAll = null;
        $updateTimeAll = null;
        $checkTimeAll = null;
        $numColumns = $this->config->config->PropertiesNumColumns > 1
            ? ceil($this->numTables / $this->config->config->PropertiesNumColumns) + 1
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

            $curTable = $this->dbi->getTable(Current::$database, $currentTable['TABLE_NAME']);
            if (! $curTable->isMerge()) {
                $sumEntries += $currentTable['TABLE_ROWS'];
            }

            $collationDefinition = '---';
            if (isset($currentTable['Collation'])) {
                $tableCollation = Charsets::findCollationByName(
                    $this->dbi,
                    $this->config->selectedServer['DisableIS'],
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

            if ($this->config->settings['ShowDbStructureCharset']) {
                $charset = '';
                if (isset($tableCollation)) {
                    $charset = $tableCollation->getCharset();
                }
            }

            $createTime = null;
            if ($this->config->settings['ShowDbStructureCreation'] && isset($currentTable['Create_time'])) {
                $createTime = $this->createDateTime($currentTable['Create_time']);
                if ($createTime !== null && ($createTimeAll === null || $createTime < $createTimeAll)) {
                    $createTimeAll = $createTime;
                }
            }

            $updateTime = null;
            if ($this->config->settings['ShowDbStructureLastUpdate'] && isset($currentTable['Update_time'])) {
                $updateTime = $this->createDateTime($currentTable['Update_time']);
                if ($updateTime !== null && ($updateTimeAll === null || $updateTime < $updateTimeAll)) {
                    $updateTimeAll = $updateTime;
                }
            }

            $checkTime = null;
            if ($this->config->settings['ShowDbStructureLastCheck'] && isset($currentTable['Check_time'])) {
                $checkTime = $this->createDateTime($currentTable['Check_time']);
                if ($checkTime !== null && ($checkTimeAll === null || $checkTime < $checkTimeAll)) {
                    $checkTimeAll = $checkTime;
                }
            }

            $truename = $currentTable['TABLE_NAME'];

            $i++;

            $rowCount++;
            if ($tableIsView) {
                $hiddenFields[] = ['name' => 'views[]', 'value' => $currentTable['TABLE_NAME']];
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
                    $tableIsView ? 'VIEW' : 'TABLE',
                    Util::backquote(
                        $currentTable['TABLE_NAME'],
                    ),
                );
                $dropMessage = sprintf(
                    ($tableIsView ? __('View %s has been dropped.') : __('Table %s has been dropped.')),
                    $currentTable['TABLE_NAME'],
                );
            }

            if ($numColumns > 0 && $this->numTables > $numColumns && ($rowCount % $numColumns) === 0) {
                $rowCount = 1;

                $html .= $this->template->render('database/structure/table_header', [
                    'db' => Current::$database,
                    'db_is_system_schema' => $this->dbIsSystemSchema,
                    'replication' => $replicaInfo['status'],
                    'properties_num_columns' => $this->config->config->PropertiesNumColumns,
                    'is_show_stats' => $this->isShowStats,
                    'show_charset' => $this->config->settings['ShowDbStructureCharset'],
                    'show_comment' => $this->config->settings['ShowDbStructureComment'],
                    'show_creation' => $this->config->settings['ShowDbStructureCreation'],
                    'show_last_update' => $this->config->settings['ShowDbStructureLastUpdate'],
                    'show_last_check' => $this->config->settings['ShowDbStructureLastCheck'],
                    'num_favorite_tables' => $this->config->settings['NumFavoriteTables'],
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
                'browse_table_label_title' => $currentTable['TABLE_COMMENT'],
                'browse_table_label_truename' => $truename,
                'empty_table_sql_query' => 'TRUNCATE ' . Util::backquote($currentTable['TABLE_NAME']),
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
                'create_time' => $createTime !== null ? Util::localisedDate($createTime) : '-',
                'update_time' => $updateTime !== null ? Util::localisedDate($updateTime) : '-',
                'check_time' => $checkTime !== null ? Util::localisedDate($checkTime) : '-',
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
                'num_favorite_tables' => $this->config->settings['NumFavoriteTables'],
                'properties_num_columns' => $this->config->config->PropertiesNumColumns,
                'limit_chars' => $this->config->settings['LimitChars'],
                'show_charset' => $this->config->settings['ShowDbStructureCharset'],
                'show_comment' => $this->config->settings['ShowDbStructureComment'],
                'show_creation' => $this->config->settings['ShowDbStructureCreation'],
                'show_last_update' => $this->config->settings['ShowDbStructureLastUpdate'],
                'show_last_check' => $this->config->settings['ShowDbStructureLastCheck'],
            ];

            $overallApproxRows = $overallApproxRows || $approxRows;
        }

        $databaseCollation = [];
        $databaseCharset = '';
        $collation = Charsets::findCollationByName(
            $this->dbi,
            $this->config->selectedServer['DisableIS'],
            $this->dbi->getDbCollation(Current::$database),
        );
        if ($collation !== null) {
            $databaseCollation = ['name' => $collation->getName(), 'description' => $collation->getDescription()];
            $databaseCharset = $collation->getCharset();
        }

        $relationParameters = $this->relation->getRelationParameters();

        $defaultStorageEngine = '';
        if ($this->config->config->PropertiesNumColumns < 2) {
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
            'properties_num_columns' => $this->config->config->PropertiesNumColumns,
            'is_show_stats' => $this->isShowStats,
            'show_charset' => $this->config->settings['ShowDbStructureCharset'],
            'show_comment' => $this->config->settings['ShowDbStructureComment'],
            'show_creation' => $this->config->settings['ShowDbStructureCreation'],
            'show_last_update' => $this->config->settings['ShowDbStructureLastUpdate'],
            'show_last_check' => $this->config->settings['ShowDbStructureLastCheck'],
            'num_favorite_tables' => $this->config->settings['NumFavoriteTables'],
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
                'create_time_all' => $createTimeAll !== null ? Util::localisedDate($createTimeAll) : '-',
                'update_time_all' => $updateTimeAll !== null ? Util::localisedDate($updateTimeAll) : '-',
                'check_time_all' => $checkTimeAll !== null ? Util::localisedDate($checkTimeAll) : '-',
                'approx_rows' => $overallApproxRows,
                'num_favorite_tables' => $this->config->settings['NumFavoriteTables'],
                'db' => Current::$database,
                'properties_num_columns' => $this->config->config->PropertiesNumColumns,
                'default_storage_engine' => $defaultStorageEngine,
                'show_charset' => $this->config->settings['ShowDbStructureCharset'],
                'show_comment' => $this->config->settings['ShowDbStructureComment'],
                'show_creation' => $this->config->settings['ShowDbStructureCreation'],
                'show_last_update' => $this->config->settings['ShowDbStructureLastUpdate'],
                'show_last_check' => $this->config->settings['ShowDbStructureLastCheck'],
            ],
            'check_all_tables' => [
                'overhead_check' => $overheadCheck,
                'db_is_system_schema' => $this->dbIsSystemSchema,
                'hidden_fields' => $hiddenFields,
                'disable_multi_table' => $this->config->config->DisableMultiTableMaintenance,
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
     * @param (string|int|null)[] $currentTable array containing details about the table
     * @param bool                $tableIsView  whether the table is a view
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

            if ($tableIsView && $currentTable['TABLE_ROWS'] >= $this->config->settings['MaxExactCountViews']) {
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
            $searchDoDBInTruename = array_search($table, $replicaInfo['Do_DB'], true);
            $searchDoDBInDB = array_search(Current::$database, $replicaInfo['Do_DB'], true);

            $searchDb = array_search(Current::$database, $replicaInfo['Ignore_DB'], true);
            $searchTable = array_search($table, $replicaInfo['Ignore_Table'], true);
            $ignored = (is_string($searchTable) && $searchTable !== '')
                || (is_string($searchDb) && $searchDb !== '')
                || $this->hasTable($replicaInfo['Wild_Ignore_Table'], $table);

            // Only set do = true if table is not ignored
            if (! $ignored) {
                $do = (is_string($searchDoDBInTruename) && $searchDoDBInTruename !== '')
                    || (is_string($searchDoDBInDB) && $searchDoDBInDB !== '')
                    || ($nbServReplicaDoDb === 0 && $nbServReplicaIgnoreDb === 0)
                    || $this->hasTable($replicaInfo['Wild_Do_Table'], $table);
            }
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
                Current::$database === $this->replication->extractDbOrTable($dbTable)
                && preg_match(
                    '@^' .
                    preg_quote(mb_substr($this->replication->extractDbOrTable($dbTable, 'table'), 0, -1), '@') . '@',
                    $truename,
                ) === 1
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
     * @param (string|int|null)[] $currentTable current table
     * @param int                 $sumSize      total table size
     * @param int                 $overheadSize overhead size
     *
     * @return list{(string|int|null)[], string, string, string, string, int, bool, int}
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
            case 'ROCKSDB':
                // InnoDB table: Row count is not accurate but data and index sizes are.
                // PBMS table in Drizzle: TABLE_ROWS is taken from table cache,
                // so it may be unavailable
                [$currentTable, $formattedSize, $unit, $sumSize] = $this->getValuesForInnodbTable(
                    $currentTable,
                    $sumSize,
                );
                break;
            case 'CSV':
                [$currentTable, $formattedSize, $unit, $sumSize] = $this->getValuesForCsvTable($currentTable, $sumSize);
                break;
            // Mysql 5.0.x (and lower) uses MRG_MyISAM
            // and MySQL 5.1.x (and higher) uses MRG_MYISAM
            // Both are aliases for MERGE
            case 'MRG_MyISAM':
            case 'MRG_MYISAM':
            case 'MERGE':
                // Merge table: Only row count is accurate.
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
     * @param (string|int|null)[] $currentTable      current table
     * @param int                 $sumSize           sum size
     * @param int                 $overheadSize      overhead size
     * @param string              $formattedSize     formatted size
     * @param string              $unit              unit
     * @param string              $formattedOverhead overhead formatted
     * @param string              $overheadUnit      overhead unit
     *
     * @return array{(string|int|null)[], string, string, string, string, int|float, int}
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
            $tblsize = $currentTable['Data_length'] + $currentTable['Index_length'];
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
     * @param (string|int|null)[] $currentTable current table
     * @param int                 $sumSize      sum size
     *
     * @return array{(string|int|null)[], string, string, int}
     */
    private function getValuesForInnodbTable(
        array $currentTable,
        int $sumSize,
    ): array {
        $formattedSize = $unit = '';

        if (
            (in_array($currentTable['ENGINE'], ['InnoDB', 'TokuDB'], true)
            && $currentTable['TABLE_ROWS'] < $this->config->settings['MaxExactCount'])
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
     * Get values for CSV table
     *
     * https://bugs.mysql.com/bug.php?id=53929
     *
     * @param (string|int|null)[] $currentTable
     *
     * @return array{(string|int|null)[], string, string, int}
     */
    private function getValuesForCsvTable(array $currentTable, int $sumSize): array
    {
        $formattedSize = $unit = '';

        $currentTable['TABLE_ROWS'] = $this->dbi
            ->getTable(Current::$database, $currentTable['TABLE_NAME'])
            ->countRecords(true);

        if ($this->isShowStats) {
            // Only count columns that have double quotes
            $columnCount = (int) $this->dbi->fetchValue(
                'SELECT COUNT(COLUMN_NAME) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '
                . $this->dbi->quoteString(Current::$database) . ' AND TABLE_NAME = '
                . $this->dbi->quoteString($currentTable['TABLE_NAME']) . ' AND NUMERIC_SCALE IS NULL;',
            );

            // Get column names
            $columnNames = $this->dbi->fetchValue(
                'SELECT GROUP_CONCAT(COLUMN_NAME) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '
                . $this->dbi->quoteString(Current::$database) . ' AND TABLE_NAME = '
                . $this->dbi->quoteString($currentTable['TABLE_NAME']) . ';',
            );

            // 10Mb buffer for CONCAT_WS
            // not sure if is needed
            $this->dbi->query('SET SESSION group_concat_max_len = 10 * 1024 * 1024');

            // Calculate data length
            $dataLength = (int) $this->dbi->fetchValue('
                SELECT SUM(CHAR_LENGTH(REPLACE(REPLACE(REPLACE(
                    CONCAT_WS(\',\', ' . $columnNames . '),
                    UNHEX(\'0A\'), \'nn\'), UNHEX(\'22\'), \'nn\'), UNHEX(\'5C\'), \'nn\'
                ))) FROM ' . Util::backquote(Current::$database) . '.' . Util::backquote($currentTable['TABLE_NAME']));

            // Calculate quotes length
            $quotesLength = $currentTable['TABLE_ROWS'] * $columnCount * 2;

            /** @var int $tblsize */
            $tblsize = $dataLength + $quotesLength + $currentTable['TABLE_ROWS'];

            $sumSize += $tblsize;
            [$formattedSize, $unit] = Util::formatByteDown($tblsize, 3, $tblsize > 0 ? 1 : 0);
        }

        return [$currentTable, $formattedSize, $unit, $sumSize];
    }

    /**
     * Get values for Mroonga table
     *
     * @param (string|int|null)[] $currentTable current table
     * @param int                 $sumSize      sum size
     *
     * @return array{(string|int|null)[], string, string, int}
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

    private function createDateTime(mixed $dateTime): DateTimeImmutable|null
    {
        if (! is_string($dateTime) || $dateTime === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($dateTime);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Gets the list of tables in the current db and information about these tables if possible.
     *
     * @return array{(string|int|null)[][], int|null}
     */
    public function getDbInfo(
        string $db,
        mixed $sortParam,
        mixed $sortOrderParam,
        mixed $tableGroupParam,
        mixed $tableTypeParam,
    ): array {
        /**
         * information about tables in db
         */
        $tables = [];
        $totalNumTables = null;

        // Set some sorting defaults
        $sort = 'Name';
        $sortOrder = 'ASC';

        if (is_string($sortParam)) {
            $sortableNameMappings = [
                'table' => 'Name',
                'records' => 'Rows',
                'type' => 'Engine',
                'collation' => 'Collation',
                'size' => 'Data_length',
                'overhead' => 'Data_free',
                'creation' => 'Create_time',
                'last_update' => 'Update_time',
                'last_check' => 'Check_time',
                'comment' => 'Comment',
            ];

            // Make sure the sort type is implemented
            if (isset($sortableNameMappings[$sortParam])) {
                $sort = $sortableNameMappings[$sortParam];
                if ($sortOrderParam === 'DESC') {
                    $sortOrder = 'DESC';
                }
            }
        }

        $groupWithSeparator = false;
        $tableType = null;
        $limitOffset = 0;
        $limitCount = false;
        $groupTable = [];

        if (
            is_string($tableGroupParam) && $tableGroupParam !== ''
            || is_string($tableTypeParam) && $tableTypeParam !== ''
        ) {
            if (is_string($tableTypeParam) && $tableTypeParam !== '') {
                // only tables for selected type
                $tableType = $tableTypeParam;
            }

            if (is_string($tableGroupParam) && $tableGroupParam !== '') {
                // only tables for selected group
                // include the table with the exact name of the group if such exists
                $groupTable = $this->dbi->getTablesFull(
                    $db,
                    $tableGroupParam,
                    false,
                    0,
                    false,
                    $sort,
                    $sortOrder,
                    $tableType,
                );
                $groupWithSeparator = $tableGroupParam . $this->config->config->NavigationTreeTableSeparator;
            }
        } else {
            // all tables in db
            // - get the total number of tables
            //  (needed for proper working of the MaxTableList feature)
            $tables = $this->dbi->getTables($db);
            $totalNumTables = count($tables);
            // fetch the details for a possible limited subset
            $limitOffset = $this->position;
            $limitCount = true;
        }

        // We must use union operator here instead of array_merge to preserve numerical keys
        $tables = $groupTable + $this->dbi->getTablesFull(
            $db,
            $groupWithSeparator !== false ? $groupWithSeparator : $tables,
            $groupWithSeparator !== false,
            $limitOffset,
            $limitCount,
            $sort,
            $sortOrder,
            $tableType,
        );

        return [
            $tables,
            $totalNumTables, // needed for proper working of the MaxTableList feature
        ];
    }

    /**
     * Gets the list of tables in the current db, taking into account
     * that they might be "in use"
     *
     * @return (string|int|null)[][] list of tables
     */
    private function getTablesWhenOpen(string $db): array
    {
        $openTables = $this->dbi->query(
            'SHOW OPEN TABLES FROM ' . Util::backquote($db) . ' WHERE In_use > 0;',
        );

        // Blending out tables in use
        $openTableNames = [];

        /** @var string $tableName */
        foreach ($openTables as ['Table' => $tableName]) {
            $openTableNames[] = $tableName;
        }

        // is there at least one "in use" table?
        if ($openTableNames === []) {
            return [];
        }

        $tables = [];
        $tblGroupSql = '';
        $whereAdded = false;
        if (
            isset($_REQUEST['tbl_group'])
            && is_scalar($_REQUEST['tbl_group'])
            && (string) $_REQUEST['tbl_group'] !== ''
        ) {
            $group = $this->dbi->escapeMysqlWildcards((string) $_REQUEST['tbl_group']);
            $groupWithSeparator = $this->dbi->escapeMysqlWildcards(
                $_REQUEST['tbl_group'] . $this->config->config->NavigationTreeTableSeparator,
            );
            $tblGroupSql .= ' WHERE ('
                . Util::backquote('Tables_in_' . $db)
                . ' LIKE ' . $this->dbi->quoteString($groupWithSeparator . '%')
                . ' OR '
                . Util::backquote('Tables_in_' . $db)
                . ' LIKE ' . $this->dbi->quoteString($group) . ')';
            $whereAdded = true;
        }

        if (isset($_REQUEST['tbl_type']) && in_array($_REQUEST['tbl_type'], ['table', 'view'], true)) {
            $tblGroupSql .= $whereAdded ? ' AND' : ' WHERE';
            if ($_REQUEST['tbl_type'] === 'view') {
                $tblGroupSql .= " `Table_type` NOT IN ('BASE TABLE', 'SYSTEM VERSIONED')";
            } else {
                $tblGroupSql .= " `Table_type` IN ('BASE TABLE', 'SYSTEM VERSIONED')";
            }
        }

        $dbInfoResult = $this->dbi->query('SHOW FULL TABLES FROM ' . Util::backquote($db) . $tblGroupSql);

        if ($dbInfoResult->numRows() > 0) {
            $names = [];
            while ($tableName = $dbInfoResult->fetchValue()) {
                if (! in_array($tableName, $openTableNames, true)) {
                    $names[] = $tableName;
                } else { // table in use
                    $tables[$tableName] = [
                        'TABLE_NAME' => $tableName,
                        'ENGINE' => '',
                        'TABLE_TYPE' => '',
                        'TABLE_ROWS' => 0,
                        'TABLE_COMMENT' => '',
                    ];
                }
            }

            if ($names !== []) {
                $tables += $this->dbi->getTablesFull($db, $names);
            }

            if ($this->config->settings['NaturalOrder']) {
                uksort($tables, strnatcasecmp(...));
            }
        }

        return $tables;
    }

    public function getTableListPosition(string|null $posParam, string $db): int
    {
        if (
            ! isset($_SESSION['tmpval']['table_limit_offset'])
            || $_SESSION['tmpval']['table_limit_offset_db'] !== $db
        ) {
            $_SESSION['tmpval']['table_limit_offset'] = 0;
            $_SESSION['tmpval']['table_limit_offset_db'] = $db;
        }

        if (is_numeric($posParam)) {
            $_SESSION['tmpval']['table_limit_offset'] = (int) $posParam;
        }

        return $_SESSION['tmpval']['table_limit_offset'];
    }
}
