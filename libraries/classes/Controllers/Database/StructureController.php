<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Core;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FlashMessages;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Replication;
use PhpMyAdmin\ReplicationInfo;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\ForeignKey;

use function array_search;
use function ceil;
use function count;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_string;
use function json_decode;
use function json_encode;
use function max;
use function mb_strlen;
use function mb_substr;
use function md5;
use function preg_match;
use function preg_quote;
use function sha1;
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
    protected $numTables;

    /** @var int Current position in the list */
    protected $position;

    /** @var bool DB is information_schema */
    protected $dbIsSystemSchema;

    /** @var int Number of tables */
    protected $totalNumTables;

    /** @var array Tables in the database */
    protected $tables;

    /** @var bool whether stats show or not */
    protected $isShowStats;

    /** @var Relation */
    private $relation;

    /** @var Replication */
    private $replication;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var Operations */
    private $operations;

    /** @var ReplicationInfo */
    private $replicationInfo;

    /** @var DatabaseInterface */
    private $dbi;

    /** @var FlashMessages */
    private $flash;

    /**
     * @param Response          $response
     * @param string            $db          Database name
     * @param Relation          $relation
     * @param Replication       $replication
     * @param DatabaseInterface $dbi
     */
    public function __construct(
        $response,
        Template $template,
        $db,
        $relation,
        $replication,
        RelationCleanup $relationCleanup,
        Operations $operations,
        $dbi,
        FlashMessages $flash
    ) {
        parent::__construct($response, $template, $db);
        $this->relation = $relation;
        $this->replication = $replication;
        $this->relationCleanup = $relationCleanup;
        $this->operations = $operations;
        $this->dbi = $dbi;
        $this->flash = $flash;

        $this->replicationInfo = new ReplicationInfo($this->dbi);
    }

    /**
     * Retrieves database information for further use
     *
     * @param string $subPart Page part name
     */
    private function getDatabaseInfo(string $subPart): void
    {
        [
            $tables,
            $numTables,
            $totalNumTables,,
            $isShowStats,
            $dbIsSystemSchema,,,
            $position,
        ] = Util::getDbInfo($this->db, $subPart);

        $this->tables = $tables;
        $this->numTables = $numTables;
        $this->position = $position;
        $this->dbIsSystemSchema = $dbIsSystemSchema;
        $this->totalNumTables = $totalNumTables;
        $this->isShowStats = $isShowStats;
    }

    public function index(): void
    {
        global $cfg, $db, $errorUrl;

        $parameters = [
            'sort' => $_REQUEST['sort'] ?? null,
            'sort_order' => $_REQUEST['sort_order'] ?? null,
        ];

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $this->addScriptFiles(['database/structure.js', 'table/change.js']);

        // Gets the database structure
        $this->getDatabaseInfo('_structure');

        // Checks if there are any tables to be shown on current page.
        // If there are no tables, the user is redirected to the last page
        // having any.
        if ($this->totalNumTables > 0 && $this->position > $this->totalNumTables) {
            $this->redirect('/database/structure', [
                'db' => $this->db,
                'pos' => max(0, $this->totalNumTables - $cfg['MaxTableList']),
                'reload' => 1,
            ]);
        }

        $this->replicationInfo->load($_POST['master_connection'] ?? null);
        $replicaInfo = $this->replicationInfo->getReplicaInfo();

        $pageSettings = new PageSettings('DbStructure');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        if ($this->numTables > 0) {
            $urlParams = [
                'pos' => $this->position,
                'db' => $this->db,
            ];
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
                $cfg['MaxTableList']
            );

            $tableList = $this->displayTableList($replicaInfo);
        }

        $createTable = '';
        if (empty($this->dbIsSystemSchema)) {
            $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
            $checkUserPrivileges->getPrivileges();

            $createTable = $this->template->render('database/create_table', ['db' => $this->db]);
        }

        $this->render('database/structure/index', [
            'database' => $this->db,
            'has_tables' => $this->numTables > 0,
            'list_navigator_html' => $listNavigator ?? '',
            'table_list_html' => $tableList ?? '',
            'is_system_schema' => ! empty($this->dbIsSystemSchema),
            'create_table_html' => $createTable,
        ]);
    }

    public function addRemoveFavoriteTablesAction(): void
    {
        global $cfg, $db, $errorUrl;

        $parameters = [
            'favorite_table' => $_REQUEST['favorite_table'] ?? null,
            'favoriteTables' => $_REQUEST['favoriteTables'] ?? null,
            'sync_favorite_tables' => $_REQUEST['sync_favorite_tables'] ?? null,
        ];

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase() || ! $this->response->isAjax()) {
            return;
        }

        $favoriteInstance = RecentFavoriteTable::getInstance('favorite');
        if (isset($parameters['favoriteTables'])) {
            $favoriteTables = json_decode($parameters['favoriteTables'], true);
        } else {
            $favoriteTables = [];
        }

        // Required to keep each user's preferences separate.
        $user = sha1($cfg['Server']['user']);

        // Request for Synchronization of favorite tables.
        if (isset($parameters['sync_favorite_tables'])) {
            $cfgRelation = $this->relation->getRelationsParam();
            if ($cfgRelation['favoritework']) {
                $this->response->addJSON($this->synchronizeFavoriteTables(
                    $favoriteInstance,
                    $user,
                    $favoriteTables
                ));
            }

            return;
        }

        $changes = true;
        $favoriteTable = $parameters['favorite_table'] ?? '';
        $alreadyFavorite = $this->checkFavoriteTable($favoriteTable);

        if (isset($_REQUEST['remove_favorite'])) {
            if ($alreadyFavorite) {
                // If already in favorite list, remove it.
                $favoriteInstance->remove($this->db, $favoriteTable);
                $alreadyFavorite = false; // for favorite_anchor template
            }
        } elseif (isset($_REQUEST['add_favorite'])) {
            if (! $alreadyFavorite) {
                $numTables = count($favoriteInstance->getTables());
                if ($numTables == $cfg['NumFavoriteTables']) {
                    $changes = false;
                } else {
                    // Otherwise add to favorite list.
                    $favoriteInstance->add($this->db, $favoriteTable);
                    $alreadyFavorite = true;  // for favorite_anchor template
                }
            }
        }

        $favoriteTables[$user] = $favoriteInstance->getTables();

        $json = [];
        $json['changes'] = $changes;
        if (! $changes) {
            $json['message'] = $this->template->render('components/error_message', [
                'msg' => __('Favorite List is full!'),
            ]);
            $this->response->addJSON($json);

            return;
        }

        // Check if current table is already in favorite list.
        $favoriteParams = [
            'db' => $this->db,
            'ajax_request' => true,
            'favorite_table' => $favoriteTable,
            ($alreadyFavorite ? 'remove' : 'add') . '_favorite' => true,
        ];

        $json['user'] = $user;
        $json['favoriteTables'] = json_encode($favoriteTables);
        $json['list'] = $favoriteInstance->getHtmlList();
        $json['anchor'] = $this->template->render('database/structure/favorite_anchor', [
            'table_name_hash' => md5($favoriteTable),
            'db_table_name_hash' => md5($this->db . '.' . $favoriteTable),
            'fav_params' => $favoriteParams,
            'already_favorite' => $alreadyFavorite,
        ]);

        $this->response->addJSON($json);
    }

    /**
     * Handles request for real row count on database level view page.
     */
    public function handleRealRowCountRequestAction(): void
    {
        global $cfg, $db, $errorUrl;

        $parameters = [
            'real_row_count_all' => $_REQUEST['real_row_count_all'] ?? null,
            'table' => $_REQUEST['table'] ?? null,
        ];

        Util::checkParameters(['db']);

        $errorUrl = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $errorUrl .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase() || ! $this->response->isAjax()) {
            return;
        }

        // If there is a request to update all table's row count.
        if (! isset($parameters['real_row_count_all'])) {
            // Get the real row count for the table.
            $realRowCount = (int) $this->dbi
                ->getTable($this->db, (string) $parameters['table'])
                ->getRealRowCountTable();
            // Format the number.
            $realRowCount = Util::formatNumber($realRowCount, 0);

            $this->response->addJSON(['real_row_count' => $realRowCount]);

            return;
        }

        // Array to store the results.
        $realRowCountAll = [];
        // Iterate over each table and fetch real row count.
        foreach ($this->tables as $table) {
            $rowCount = $this->dbi
                ->getTable($this->db, $table['TABLE_NAME'])
                ->getRealRowCountTable();
            $realRowCountAll[] = [
                'table' => $table['TABLE_NAME'],
                'row_count' => $rowCount,
            ];
        }

        $this->response->addJSON(['real_row_count_all' => json_encode($realRowCountAll)]);
    }

    public function copyTable(): void
    {
        global $db, $message;

        $selected = $_POST['selected'] ?? [];
        $targetDb = $_POST['target_db'] ?? null;
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            Table::moveCopy(
                $db,
                $selected[$i],
                $targetDb,
                $selected[$i],
                $_POST['what'],
                false,
                'one_table'
            );

            if (empty($_POST['adjust_privileges'])) {
                continue;
            }

            $this->operations->adjustPrivilegesCopyTable(
                $db,
                $selected[$i],
                $targetDb,
                $selected[$i]
            );
        }

        $message = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $message;
        }

        $this->index();
    }

    /**
     * @param array $replicaInfo
     */
    protected function displayTableList($replicaInfo): string
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
        $rowCount      = 0;
        $sumSize       = 0;
        $overheadSize  = 0;

        $hiddenFields = [];
        $overallApproxRows = false;
        $structureTableRows = [];
        foreach ($this->tables as $keyname => $currentTable) {
            // Get valid statistics whatever is the table type

            $dropQuery = '';
            $dropMessage = '';
            $overhead = '';
            $inputClass = ['checkall'];

            // Sets parameters for links
            $tableUrlParams = [
                'db' => $this->db,
                'table' => $currentTable['TABLE_NAME'],
            ];
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
            ] = $this->getStuffForEngineTypeTable(
                $currentTable,
                $sumSize,
                $overheadSize
            );

            $curTable = $this->dbi
                ->getTable($this->db, $currentTable['TABLE_NAME']);
            if (! $curTable->isMerge()) {
                $sumEntries += $currentTable['TABLE_ROWS'];
            }

            $collationDefinition = '---';
            if (isset($currentTable['Collation'])) {
                $tableCollation = Charsets::findCollationByName(
                    $this->dbi,
                    $GLOBALS['cfg']['Server']['DisableIS'],
                    $currentTable['Collation']
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
                if (
                    $createTime
                    && (! $createTimeAll
                    || $createTime < $createTimeAll)
                ) {
                    $createTimeAll = $createTime;
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
                $updateTime = $currentTable['Update_time'] ?? '';
                if (
                    $updateTime
                    && (! $updateTimeAll
                    || $updateTime < $updateTimeAll)
                ) {
                    $updateTimeAll = $updateTime;
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
                $checkTime = $currentTable['Check_time'] ?? '';
                if (
                    $checkTime
                    && (! $checkTimeAll
                    || $checkTime < $checkTimeAll)
                ) {
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

            /*
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
                        $currentTable['TABLE_NAME']
                    )
                );
                $dropMessage = sprintf(
                    ($tableIsView || $currentTable['ENGINE'] == null
                        ? __('View %s has been dropped.')
                        : __('Table %s has been dropped.')),
                    str_replace(
                        ' ',
                        '&nbsp;',
                        htmlspecialchars($currentTable['TABLE_NAME'])
                    )
                );
            }

            if (
                $numColumns > 0
                && $this->numTables > $numColumns
                && ($rowCount % $numColumns) == 0
            ) {
                $rowCount = 1;

                $html .= $this->template->render('database/structure/table_header', [
                    'db' => $this->db,
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

            [$approxRows, $showSuperscript] = $this->isRowCountApproximated(
                $currentTable,
                $tableIsView
            );

            [$do, $ignored] = $this->getReplicationStatus($replicaInfo, $truename);

            $structureTableRows[] = [
                'table_name_hash' => md5($currentTable['TABLE_NAME']),
                'db_table_name_hash' => md5($this->db . '.' . $currentTable['TABLE_NAME']),
                'db' => $this->db,
                'curr' => $i,
                'input_class' => implode(' ', $inputClass),
                'table_is_view' => $tableIsView,
                'current_table' => $currentTable,
                'may_have_rows' => $mayHaveRows,
                'browse_table_label_title' => htmlspecialchars($currentTable['TABLE_COMMENT']),
                'browse_table_label_truename' => $truename,
                'empty_table_sql_query' => 'TRUNCATE ' . Util::backquote(
                    $currentTable['TABLE_NAME']
                ),
                'empty_table_message_to_show' => urlencode(
                    sprintf(
                        __('Table %s has been emptied.'),
                        htmlspecialchars(
                            $currentTable['TABLE_NAME']
                        )
                    )
                ),
                'tracking_icon' => $this->getTrackingIcon($truename),
                'server_slave_status' => $replicaInfo['status'],
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
                'already_favorite' => $this->checkFavoriteTable(
                    $currentTable['TABLE_NAME']
                ),
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
            $this->dbi->getDbCollation($this->db)
        );
        if ($collation !== null) {
            $databaseCollation = [
                'name' => $collation->getName(),
                'description' => $collation->getDescription(),
            ];
            $databaseCharset = $collation->getCharset();
        }

        return $html . $this->template->render('database/structure/table_header', [
            'db' => $this->db,
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
                'server_slave_status' => $replicaInfo['status'],
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
                'central_columns_work' => $GLOBALS['cfgRelation']['centralcolumnswork'] ?? null,
            ],
        ]);
    }

    /**
     * Returns the tracking icon if the table is tracked
     *
     * @param string $table table name
     *
     * @return string HTML for tracking icon
     */
    protected function getTrackingIcon(string $table): string
    {
        $trackingIcon = '';
        if (Tracker::isActive()) {
            $isTracked = Tracker::isTracked($this->db, $table);
            if (
                $isTracked
                || Tracker::getVersion($this->db, $table) > 0
            ) {
                $trackingIcon = $this->template->render('database/structure/tracking_icon', [
                    'db' => $this->db,
                    'table' => $table,
                    'is_tracked' => $isTracked,
                ]);
            }
        }

        return $trackingIcon;
    }

    /**
     * Returns whether the row count is approximated
     *
     * @param array $currentTable array containing details about the table
     * @param bool  $tableIsView  whether the table is a view
     *
     * @return array
     */
    protected function isRowCountApproximated(
        array $currentTable,
        bool $tableIsView
    ): array {
        $approxRows = false;
        $showSuperscript = '';

        // there is a null value in the ENGINE
        // - when the table needs to be repaired, or
        // - when it's a view
        //  so ensure that we'll display "in use" below for a table
        //  that needs to be repaired
        if (
            isset($currentTable['TABLE_ROWS'])
            && ($currentTable['ENGINE'] != null || $tableIsView)
        ) {
            // InnoDB/TokuDB table: we did not get an accurate row count
            $approxRows = ! $tableIsView
                && in_array($currentTable['ENGINE'], ['InnoDB', 'TokuDB'])
                && ! $currentTable['COUNTED'];

            if (
                $tableIsView
                && $currentTable['TABLE_ROWS'] >= $GLOBALS['cfg']['MaxExactCountViews']
            ) {
                $approxRows = true;
                $showSuperscript = Generator::showHint(
                    Sanitize::sanitizeMessage(
                        sprintf(
                            __(
                                'This view has at least this number of '
                                . 'rows. Please refer to %sdocumentation%s.'
                            ),
                            '[doc@cfg_MaxExactCountViews]',
                            '[/doc]'
                        )
                    )
                );
            }
        }

        return [
            $approxRows,
            $showSuperscript,
        ];
    }

    /**
     * Returns the replication status of the table.
     *
     * @param array  $replicaInfo
     * @param string $table       table name
     *
     * @return array
     */
    protected function getReplicationStatus($replicaInfo, string $table): array
    {
        $do = $ignored = false;
        if ($replicaInfo['status']) {
            $nbServSlaveDoDb = count(
                $replicaInfo['Do_DB']
            );
            $nbServSlaveIgnoreDb = count(
                $replicaInfo['Ignore_DB']
            );
            $searchDoDBInTruename = array_search(
                $table,
                $replicaInfo['Do_DB']
            );
            $searchDoDBInDB = array_search(
                $this->db,
                $replicaInfo['Do_DB']
            );

            $do = (is_string($searchDoDBInTruename) && strlen($searchDoDBInTruename) > 0)
                || (is_string($searchDoDBInDB) && strlen($searchDoDBInDB) > 0)
                || ($nbServSlaveDoDb == 0 && $nbServSlaveIgnoreDb == 0)
                || $this->hasTable(
                    $replicaInfo['Wild_Do_Table'],
                    $table
                );

            $searchDb = array_search(
                $this->db,
                $replicaInfo['Ignore_DB']
            );
            $searchTable = array_search(
                $table,
                $replicaInfo['Ignore_Table']
            );
            $ignored = (is_string($searchTable) && strlen($searchTable) > 0)
                || (is_string($searchDb) && strlen($searchDb) > 0)
                || $this->hasTable(
                    $replicaInfo['Wild_Ignore_Table'],
                    $table
                );
        }

        return [
            $do,
            $ignored,
        ];
    }

    /**
     * Synchronize favorite tables
     *
     * @param RecentFavoriteTable $favoriteInstance Instance of this class
     * @param string              $user             The user hash
     * @param array               $favoriteTables   Existing favorites
     *
     * @return array
     */
    protected function synchronizeFavoriteTables(
        RecentFavoriteTable $favoriteInstance,
        string $user,
        array $favoriteTables
    ): array {
        $favoriteInstanceTables = $favoriteInstance->getTables();

        if (
            empty($favoriteInstanceTables)
            && isset($favoriteTables[$user])
        ) {
            foreach ($favoriteTables[$user] as $key => $value) {
                $favoriteInstance->add($value['db'], $value['table']);
            }
        }

        $favoriteTables[$user] = $favoriteInstance->getTables();

        $json = [
            'favoriteTables' => json_encode($favoriteTables),
            'list' => $favoriteInstance->getHtmlList(),
        ];
        $serverId = $GLOBALS['server'];
        // Set flag when localStorage and pmadb(if present) are in sync.
        $_SESSION['tmpval']['favorites_synced'][$serverId] = true;

        return $json;
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
            if ($value['db'] == $this->db && $value['table'] == $currentTable) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find table with truename
     *
     * @param array  $db       DB to look into
     * @param string $truename Table name
     *
     * @return bool
     */
    protected function hasTable(array $db, $truename)
    {
        foreach ($db as $dbTable) {
            if (
                $this->db == $this->replication->extractDbOrTable($dbTable)
                && preg_match(
                    '@^' .
                    preg_quote(mb_substr($this->replication->extractDbOrTable($dbTable, 'table'), 0, -1), '@') . '@',
                    $truename
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
     * @param array $currentTable current table
     * @param int   $sumSize      total table size
     * @param int   $overheadSize overhead size
     *
     * @return array
     */
    protected function getStuffForEngineTypeTable(
        array $currentTable,
        $sumSize,
        $overheadSize
    ) {
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
                    $overheadUnit
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
                    $sumSize
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
                    $formattedSize =  ' - ';
                    $unit          =  '';
                }

                break;
        // for a view, the ENGINE is sometimes reported as null,
        // or on some servers it's reported as "SYSTEM VIEW"
            case null:
            case 'SYSTEM VIEW':
                // possibly a view, do nothing
                break;
            default:
                // Unknown table type.
                if ($this->isShowStats) {
                    $formattedSize =  __('unknown');
                    $unit          =  '';
                }
        }

        if (
            $currentTable['TABLE_TYPE'] === 'VIEW'
            || $currentTable['TABLE_TYPE'] === 'SYSTEM VIEW'
        ) {
            // countRecords() takes care of $cfg['MaxExactCountViews']
            $currentTable['TABLE_ROWS'] = $this->dbi
                ->getTable($this->db, $currentTable['TABLE_NAME'])
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
     * @param array  $currentTable      current table
     * @param int    $sumSize           sum size
     * @param int    $overheadSize      overhead size
     * @param int    $formattedSize     formatted size
     * @param string $unit              unit
     * @param int    $formattedOverhead overhead formatted
     * @param string $overheadUnit      overhead unit
     *
     * @return array
     */
    protected function getValuesForAriaTable(
        array $currentTable,
        $sumSize,
        $overheadSize,
        $formattedSize,
        $unit,
        $formattedOverhead,
        $overheadUnit
    ) {
        if ($this->dbIsSystemSchema) {
            $currentTable['Rows'] = $this->dbi
                ->getTable($this->db, $currentTable['Name'])
                ->countRecords();
        }

        if ($this->isShowStats) {
            /** @var int $tblsize */
            $tblsize = $currentTable['Data_length']
                + $currentTable['Index_length'];
            $sumSize += $tblsize;
            [$formattedSize, $unit] = Util::formatByteDown(
                $tblsize,
                3,
                $tblsize > 0 ? 1 : 0
            );
            if (
                isset($currentTable['Data_free'])
                && $currentTable['Data_free'] > 0
            ) {
                [$formattedOverhead, $overheadUnit] = Util::formatByteDown(
                    $currentTable['Data_free'],
                    3,
                    ($currentTable['Data_free'] > 0 ? 1 : 0)
                );
                $overheadSize += $currentTable['Data_free'];
            }
        }

        return [
            $currentTable,
            $formattedSize,
            $unit,
            $formattedOverhead,
            $overheadUnit,
            $overheadSize,
            $sumSize,
        ];
    }

    /**
     * Get values for InnoDB table
     *
     * @param array $currentTable current table
     * @param int   $sumSize      sum size
     *
     * @return array
     */
    protected function getValuesForInnodbTable(
        array $currentTable,
        $sumSize
    ) {
        $formattedSize = $unit = '';

        if (
            (in_array($currentTable['ENGINE'], ['InnoDB', 'TokuDB'])
            && $currentTable['TABLE_ROWS'] < $GLOBALS['cfg']['MaxExactCount'])
            || ! isset($currentTable['TABLE_ROWS'])
        ) {
            $currentTable['COUNTED'] = true;
            $currentTable['TABLE_ROWS'] = $this->dbi
                ->getTable($this->db, $currentTable['TABLE_NAME'])
                ->countRecords(true);
        } else {
            $currentTable['COUNTED'] = false;
        }

        if ($this->isShowStats) {
            /** @var int $tblsize */
            $tblsize = $currentTable['Data_length']
                + $currentTable['Index_length'];
            $sumSize += $tblsize;
            [$formattedSize, $unit] = Util::formatByteDown(
                $tblsize,
                3,
                ($tblsize > 0 ? 1 : 0)
            );
        }

        return [
            $currentTable,
            $formattedSize,
            $unit,
            $sumSize,
        ];
    }

    public function showCreate(): void
    {
        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $tables = $this->getShowCreateTables($selected);

        $showCreate = $this->template->render('database/structure/show_create', ['tables' => $tables]);

        $this->response->addJSON('message', $showCreate);
    }

    /**
     * @param string[] $selected Selected tables.
     *
     * @return array<string, array<int, array<string, string>>>
     */
    private function getShowCreateTables(array $selected): array
    {
        $tables = ['tables' => [], 'views' => []];

        foreach ($selected as $table) {
            $object = $this->dbi->getTable($this->db, $table);

            $tables[$object->isView() ? 'views' : 'tables'][] = [
                'name' => Core::mimeDefaultFunction($table),
                'show_create' => Core::mimeDefaultFunction($object->showCreate()),
            ];
        }

        return $tables;
    }

    public function copyForm(): void
    {
        global $db, $dblist;

        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $urlParams = ['db' => $db];
        foreach ($selected as $selectedValue) {
            $urlParams['selected'][] = $selectedValue;
        }

        $databasesList = $dblist->databases;
        foreach ($databasesList as $key => $databaseName) {
            if ($databaseName == $db) {
                $databasesList->offsetUnset($key);
                break;
            }
        }

        $this->response->disable();
        $this->render('database/structure/copy_form', [
            'url_params' => $urlParams,
            'options' => $databasesList->getList(),
        ]);
    }

    public function centralColumnsAdd(): void
    {
        global $message;

        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $centralColumns = new CentralColumns($this->dbi);
        $error = $centralColumns->syncUniqueColumns($selected);

        $message = $error instanceof Message ? $error : Message::success(__('Success!'));

        unset($_POST['submit_mult']);

        $this->index();
    }

    public function centralColumnsMakeConsistent(): void
    {
        global $db, $message;

        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $centralColumns = new CentralColumns($this->dbi);
        $error = $centralColumns->makeConsistentWithList($db, $selected);

        $message = $error instanceof Message ? $error : Message::success(__('Success!'));

        unset($_POST['submit_mult']);

        $this->index();
    }

    public function centralColumnsRemove(): void
    {
        global $message;

        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $centralColumns = new CentralColumns($this->dbi);
        $error = $centralColumns->deleteColumnsFromList($_POST['db'], $selected);

        $message = $error instanceof Message ? $error : Message::success(__('Success!'));

        unset($_POST['submit_mult']);

        $this->index();
    }

    public function addPrefix(): void
    {
        global $db;

        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $params = ['db' => $db];
        foreach ($selected as $selectedValue) {
            $params['selected'][] = $selectedValue;
        }

        $this->response->disable();
        $this->render('database/structure/add_prefix', ['url_params' => $params]);
    }

    public function changePrefixForm(): void
    {
        global $db;

        $selected = $_POST['selected_tbl'] ?? [];
        $submitMult = $_POST['submit_mult'] ?? '';

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $route = '/database/structure/replace-prefix';
        if ($submitMult === 'copy_tbl_change_prefix') {
            $route = '/database/structure/copy-table-with-prefix';
        }

        $urlParams = ['db' => $db];
        foreach ($selected as $selectedValue) {
            $urlParams['selected'][] = $selectedValue;
        }

        $this->response->disable();
        $this->render('database/structure/change_prefix_form', [
            'route' => $route,
            'url_params' => $urlParams,
        ]);
    }

    public function dropForm(): void
    {
        global $db;

        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $views = $this->dbi->getVirtualTables($db);

        $fullQueryViews = '';
        $fullQuery = '';

        foreach ($selected as $selectedValue) {
            $current = $selectedValue;
            if (! empty($views) && in_array($current, $views)) {
                $fullQueryViews .= (empty($fullQueryViews) ? 'DROP VIEW ' : ', ')
                    . Util::backquote(htmlspecialchars($current));
            } else {
                $fullQuery .= (empty($fullQuery) ? 'DROP TABLE ' : ', ')
                    . Util::backquote(htmlspecialchars($current));
            }
        }

        if (! empty($fullQuery)) {
            $fullQuery .= ';<br>' . "\n";
        }

        if (! empty($fullQueryViews)) {
            $fullQuery .= $fullQueryViews . ';<br>' . "\n";
        }

        $urlParams = ['db' => $db];
        foreach ($selected as $selectedValue) {
            $urlParams['selected'][] = $selectedValue;
        }

        foreach ($views as $current) {
            $urlParams['views'][] = $current;
        }

        $this->render('database/structure/drop_form', [
            'url_params' => $urlParams,
            'full_query' => $fullQuery,
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
        ]);
    }

    public function emptyForm(): void
    {
        global $db;

        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $fullQuery = '';
        $urlParams = ['db' => $db];

        foreach ($selected as $selectedValue) {
            $fullQuery .= 'TRUNCATE ';
            $fullQuery .= Util::backquote(htmlspecialchars($selectedValue)) . ';<br>';
            $urlParams['selected'][] = $selectedValue;
        }

        $this->render('database/structure/empty_form', [
            'url_params' => $urlParams,
            'full_query' => $fullQuery,
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
        ]);
    }

    public function dropTable(): void
    {
        global $db, $message, $reload, $sql_query;

        $reload = $_POST['reload'] ?? $reload ?? null;
        $multBtn = $_POST['mult_btn'] ?? '';
        $selected = $_POST['selected'] ?? [];

        $views = $this->dbi->getVirtualTables($db);

        if ($multBtn !== __('Yes')) {
            $message = Message::success(__('No change'));

            if (empty($_POST['message'])) {
                $_POST['message'] = Message::success();
            }

            unset($_POST['mult_btn']);

            $this->index();

            return;
        }

        $defaultFkCheckValue = ForeignKey::handleDisableCheckInit();
        $sql_query = '';
        $sqlQueryViews = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $this->relationCleanup->table($db, $selected[$i]);
            $current = $selected[$i];

            if (! empty($views) && in_array($current, $views)) {
                $sqlQueryViews .= (empty($sqlQueryViews) ? 'DROP VIEW ' : ', ') . Util::backquote($current);
            } else {
                $sql_query .= (empty($sql_query) ? 'DROP TABLE ' : ', ') . Util::backquote($current);
            }

            $reload = 1;
        }

        if (! empty($sql_query)) {
            $sql_query .= ';';
        } elseif (! empty($sqlQueryViews)) {
            $sql_query = $sqlQueryViews . ';';
            unset($sqlQueryViews);
        }

        // Unset cache values for tables count, issue #14205
        if (isset($_SESSION['tmpval'])) {
            if (isset($_SESSION['tmpval']['table_limit_offset'])) {
                unset($_SESSION['tmpval']['table_limit_offset']);
            }

            if (isset($_SESSION['tmpval']['table_limit_offset_db'])) {
                unset($_SESSION['tmpval']['table_limit_offset_db']);
            }
        }

        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($sql_query);

        if ($result && ! empty($sqlQueryViews)) {
            $sql_query .= ' ' . $sqlQueryViews . ';';
            $result = $this->dbi->tryQuery($sqlQueryViews);
            unset($sqlQueryViews);
        }

        if (! $result) {
            $message = Message::error((string) $this->dbi->getError());
        }

        ForeignKey::handleDisableCheckCleanup($defaultFkCheckValue);

        $message = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $message;
        }

        unset($_POST['mult_btn']);

        $this->index();
    }

    public function emptyTable(): void
    {
        global $db, $table, $message, $sql_query;

        $multBtn = $_POST['mult_btn'] ?? '';
        $selected = $_POST['selected'] ?? [];

        if ($multBtn !== __('Yes')) {
            $this->flash->addMessage('success', __('No change'));
            $this->redirect('/database/structure', ['db' => $db]);

            return;
        }

        $defaultFkCheckValue = ForeignKey::handleDisableCheckInit();

        $sql_query = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $aQuery = 'TRUNCATE ';
            $aQuery .= Util::backquote($selected[$i]);

            $sql_query .= $aQuery . ';' . "\n";
            $this->dbi->selectDb($db);
            $this->dbi->query($aQuery);
        }

        if (! empty($_REQUEST['pos'])) {
            $sql = new Sql(
                $this->dbi,
                $this->relation,
                $this->relationCleanup,
                $this->operations,
                new Transformations(),
                $this->template
            );

            $_REQUEST['pos'] = $sql->calculatePosForLastPage($db, $table, $_REQUEST['pos']);
        }

        ForeignKey::handleDisableCheckCleanup($defaultFkCheckValue);

        $message = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $message;
        }

        unset($_POST['mult_btn']);

        $this->index();
    }

    public function addPrefixTable(): void
    {
        global $db, $message, $sql_query;

        $selected = $_POST['selected'] ?? [];

        $sql_query = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $newTableName = $_POST['add_prefix'] . $selected[$i];
            $aQuery = 'ALTER TABLE ' . Util::backquote($selected[$i])
                . ' RENAME ' . Util::backquote($newTableName);

            $sql_query .= $aQuery . ';' . "\n";
            $this->dbi->selectDb($db);
            $this->dbi->query($aQuery);
        }

        $message = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $message;
        }

        $this->index();
    }

    public function replacePrefix(): void
    {
        global $db, $message, $sql_query;

        $selected = $_POST['selected'] ?? [];
        $fromPrefix = $_POST['from_prefix'] ?? '';
        $toPrefix = $_POST['to_prefix'] ?? '';

        $sql_query = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $current = $selected[$i];
            $subFromPrefix = mb_substr($current, 0, mb_strlen((string) $fromPrefix));

            if ($subFromPrefix === $fromPrefix) {
                $newTableName = $toPrefix . mb_substr(
                    $current,
                    mb_strlen((string) $fromPrefix)
                );
            } else {
                $newTableName = $current;
            }

            $aQuery = 'ALTER TABLE ' . Util::backquote($selected[$i])
                . ' RENAME ' . Util::backquote($newTableName);

            $sql_query .= $aQuery . ';' . "\n";
            $this->dbi->selectDb($db);
            $this->dbi->query($aQuery);
        }

        $message = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $message;
        }

        $this->index();
    }

    public function copyTableWithPrefix(): void
    {
        global $db, $message;

        $selected = $_POST['selected'] ?? [];
        $fromPrefix = $_POST['from_prefix'] ?? null;
        $toPrefix = $_POST['to_prefix'] ?? null;

        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $current = $selected[$i];
            $newTableName = $toPrefix . mb_substr($current, mb_strlen((string) $fromPrefix));

            Table::moveCopy(
                $db,
                $current,
                $db,
                $newTableName,
                'data',
                false,
                'one_table'
            );
        }

        $message = Message::success();

        if (empty($_POST['message'])) {
            $_POST['message'] = $message;
        }

        $this->index();
    }
}
