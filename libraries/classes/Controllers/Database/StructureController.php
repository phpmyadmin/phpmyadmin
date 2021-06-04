<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Core;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\DatabaseInterface;
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
        $dbi
    ) {
        parent::__construct($response, $template, $db);
        $this->relation = $relation;
        $this->replication = $replication;
        $this->relationCleanup = $relationCleanup;
        $this->operations = $operations;
        $this->dbi = $dbi;

        $this->replicationInfo = new ReplicationInfo($this->dbi);
    }

    /**
     * Retrieves database information for further use
     *
     * @param string $subPart Page part name
     */
    private function getDatabaseInfo(string $subPart): void
    {
        [$tables, $numTables, $totalNumTables, , $isShowStats, $dbIsSystemSchema, , , $position]
            = Util::getDbInfo($this->db, $subPart);

        $this->tables = $tables;
        $this->numTables = $numTables;
        $this->position = $position;
        $this->dbIsSystemSchema = $dbIsSystemSchema;
        $this->totalNumTables = $totalNumTables;
        $this->isShowStats = $isShowStats;
    }

    public function index(): void
    {
        global $cfg, $db, $err_url;

        $parameters = [
            'sort' => $_REQUEST['sort'] ?? null,
            'sort_order' => $_REQUEST['sort_order'] ?? null,
        ];

        Util::checkParameters(['db']);

        $err_url = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $err_url .= Url::getCommon(['db' => $db], '&');

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
            $uri = './index.php?route=/database/structure' . Url::getCommonRaw([
                'db' => $this->db,
                'pos' => max(0, $this->totalNumTables - $cfg['MaxTableList']),
                'reload' => 1,
            ], '&');
            Core::sendHeaderLocation($uri);
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
        global $cfg, $db, $err_url;

        $parameters = [
            'favorite_table' => $_REQUEST['favorite_table'] ?? null,
            'favoriteTables' => $_REQUEST['favoriteTables'] ?? null,
            'sync_favorite_tables' => $_REQUEST['sync_favorite_tables'] ?? null,
        ];

        Util::checkParameters(['db']);

        $err_url = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $err_url .= Url::getCommon(['db' => $db], '&');

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
        global $cfg, $db, $err_url;

        $parameters = [
            'real_row_count_all' => $_REQUEST['real_row_count_all'] ?? null,
            'table' => $_REQUEST['table'] ?? null,
        ];

        Util::checkParameters(['db']);

        $err_url = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $err_url .= Url::getCommon(['db' => $db], '&');

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
        global $PMA_Theme;

        $html = '';

        // filtering
        $html .= $this->template->render('filter', ['filter_value' => '']);

        $i = $sum_entries = 0;
        $overhead_check = false;
        $create_time_all = '';
        $update_time_all = '';
        $check_time_all = '';
        $num_columns = $GLOBALS['cfg']['PropertiesNumColumns'] > 1
            ? ceil($this->numTables / $GLOBALS['cfg']['PropertiesNumColumns']) + 1
            : 0;
        $row_count      = 0;
        $sum_size       = 0;
        $overhead_size  = 0;

        $hidden_fields = [];
        $overall_approx_rows = false;
        $structure_table_rows = [];
        foreach ($this->tables as $keyname => $current_table) {
            // Get valid statistics whatever is the table type

            $drop_query = '';
            $drop_message = '';
            $overhead = '';
            $input_class = ['checkall'];

            // Sets parameters for links
            $tableUrlParams = [
                'db' => $this->db,
                'table' => $current_table['TABLE_NAME'],
            ];
            // do not list the previous table's size info for a view

            [
                $current_table,
                $formatted_size,
                $unit,
                $formatted_overhead,
                $overhead_unit,
                $overhead_size,
                $table_is_view,
                $sum_size,
            ] = $this->getStuffForEngineTypeTable(
                $current_table,
                $sum_size,
                $overhead_size
            );

            $curTable = $this->dbi
                ->getTable($this->db, $current_table['TABLE_NAME']);
            if (! $curTable->isMerge()) {
                $sum_entries += $current_table['TABLE_ROWS'];
            }

            $collationDefinition = '---';
            if (isset($current_table['Collation'])) {
                $tableCollation = Charsets::findCollationByName(
                    $this->dbi,
                    $GLOBALS['cfg']['Server']['DisableIS'],
                    $current_table['Collation']
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
                if ($formatted_overhead != '') {
                    $overhead = $this->template->render('database/structure/overhead', [
                        'table_url_params' => $tableUrlParams,
                        'formatted_overhead' => $formatted_overhead,
                        'overhead_unit' => $overhead_unit,
                    ]);
                    $overhead_check = true;
                    $input_class[] = 'tbl-overhead';
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureCharset']) {
                $charset = '';
                if (isset($tableCollation)) {
                    $charset = $tableCollation->getCharset();
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
                $create_time = $current_table['Create_time'] ?? '';
                if ($create_time
                    && (! $create_time_all
                    || $create_time < $create_time_all)
                ) {
                    $create_time_all = $create_time;
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
                $update_time = $current_table['Update_time'] ?? '';
                if ($update_time
                    && (! $update_time_all
                    || $update_time < $update_time_all)
                ) {
                    $update_time_all = $update_time;
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
                $check_time = $current_table['Check_time'] ?? '';
                if ($check_time
                    && (! $check_time_all
                    || $check_time < $check_time_all)
                ) {
                    $check_time_all = $check_time;
                }
            }

            $truename = $current_table['TABLE_NAME'];

            $i++;

            $row_count++;
            if ($table_is_view) {
                $hidden_fields[] = '<input type="hidden" name="views[]" value="'
                    . htmlspecialchars($current_table['TABLE_NAME']) . '">';
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
            $may_have_rows = $current_table['TABLE_ROWS'] > 0 || $table_is_view;

            if (! $this->dbIsSystemSchema) {
                $drop_query = sprintf(
                    'DROP %s %s',
                    $table_is_view || $current_table['ENGINE'] == null ? 'VIEW'
                    : 'TABLE',
                    Util::backquote(
                        $current_table['TABLE_NAME']
                    )
                );
                $drop_message = sprintf(
                    ($table_is_view || $current_table['ENGINE'] == null
                        ? __('View %s has been dropped.')
                        : __('Table %s has been dropped.')),
                    str_replace(
                        ' ',
                        '&nbsp;',
                        htmlspecialchars($current_table['TABLE_NAME'])
                    )
                );
            }

            if ($num_columns > 0
                && $this->numTables > $num_columns
                && ($row_count % $num_columns) == 0
            ) {
                $row_count = 1;

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
                    'structure_table_rows' => $structure_table_rows,
                ]);
                $structure_table_rows = [];
            }

            [$approx_rows, $show_superscript] = $this->isRowCountApproximated(
                $current_table,
                $table_is_view
            );

            [$do, $ignored] = $this->getReplicationStatus($replicaInfo, $truename);

            $structure_table_rows[] = [
                'table_name_hash' => md5($current_table['TABLE_NAME']),
                'db_table_name_hash' => md5($this->db . '.' . $current_table['TABLE_NAME']),
                'db' => $this->db,
                'curr' => $i,
                'input_class' => implode(' ', $input_class),
                'table_is_view' => $table_is_view,
                'current_table' => $current_table,
                'may_have_rows' => $may_have_rows,
                'browse_table_label_title' => htmlspecialchars($current_table['TABLE_COMMENT']),
                'browse_table_label_truename' => $truename,
                'empty_table_sql_query' => 'TRUNCATE ' . Util::backquote(
                    $current_table['TABLE_NAME']
                ),
                'empty_table_message_to_show' => urlencode(
                    sprintf(
                        __('Table %s has been emptied.'),
                        htmlspecialchars(
                            $current_table['TABLE_NAME']
                        )
                    )
                ),
                'tracking_icon' => $this->getTrackingIcon($truename),
                'server_slave_status' => $replicaInfo['status'],
                'table_url_params' => $tableUrlParams,
                'db_is_system_schema' => $this->dbIsSystemSchema,
                'drop_query' => $drop_query,
                'drop_message' => $drop_message,
                'collation' => $collationDefinition,
                'formatted_size' => $formatted_size,
                'unit' => $unit,
                'overhead' => $overhead,
                'create_time' => isset($create_time) && $create_time
                        ? Util::localisedDate(strtotime($create_time)) : '-',
                'update_time' => isset($update_time) && $update_time
                        ? Util::localisedDate(strtotime($update_time)) : '-',
                'check_time' => isset($check_time) && $check_time
                        ? Util::localisedDate(strtotime($check_time)) : '-',
                'charset' => $charset ?? '',
                'is_show_stats' => $this->isShowStats,
                'ignored' => $ignored,
                'do' => $do,
                'approx_rows' => $approx_rows,
                'show_superscript' => $show_superscript,
                'already_favorite' => $this->checkFavoriteTable(
                    $current_table['TABLE_NAME']
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

            $overall_approx_rows = $overall_approx_rows || $approx_rows;
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
            'structure_table_rows' => $structure_table_rows,
            'body_for_table_summary' => [
                'num_tables' => $this->numTables,
                'server_slave_status' => $replicaInfo['status'],
                'db_is_system_schema' => $this->dbIsSystemSchema,
                'sum_entries' => $sum_entries,
                'database_collation' => $databaseCollation,
                'is_show_stats' => $this->isShowStats,
                'database_charset' => $databaseCharset,
                'sum_size' => $sum_size,
                'overhead_size' => $overhead_size,
                'create_time_all' => $create_time_all ? Util::localisedDate(strtotime($create_time_all)) : '-',
                'update_time_all' => $update_time_all ? Util::localisedDate(strtotime($update_time_all)) : '-',
                'check_time_all' => $check_time_all ? Util::localisedDate(strtotime($check_time_all)) : '-',
                'approx_rows' => $overall_approx_rows,
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
                'theme_image_path' => $PMA_Theme->getImgPath(),
                'text_dir' => $GLOBALS['text_dir'],
                'overhead_check' => $overhead_check,
                'db_is_system_schema' => $this->dbIsSystemSchema,
                'hidden_fields' => $hidden_fields,
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
        $tracking_icon = '';
        if (Tracker::isActive()) {
            $is_tracked = Tracker::isTracked($this->db, $table);
            if ($is_tracked
                || Tracker::getVersion($this->db, $table) > 0
            ) {
                $tracking_icon = $this->template->render('database/structure/tracking_icon', [
                    'db' => $this->db,
                    'table' => $table,
                    'is_tracked' => $is_tracked,
                ]);
            }
        }

        return $tracking_icon;
    }

    /**
     * Returns whether the row count is approximated
     *
     * @param array $current_table array containing details about the table
     * @param bool  $table_is_view whether the table is a view
     *
     * @return array
     */
    protected function isRowCountApproximated(
        array $current_table,
        bool $table_is_view
    ): array {
        $approx_rows = false;
        $show_superscript = '';

        // there is a null value in the ENGINE
        // - when the table needs to be repaired, or
        // - when it's a view
        //  so ensure that we'll display "in use" below for a table
        //  that needs to be repaired
        if (isset($current_table['TABLE_ROWS'])
            && ($current_table['ENGINE'] != null || $table_is_view)
        ) {
            // InnoDB/TokuDB table: we did not get an accurate row count
            $approx_rows = ! $table_is_view
                && in_array($current_table['ENGINE'], ['InnoDB', 'TokuDB'])
                && ! $current_table['COUNTED'];

            if ($table_is_view
                && $current_table['TABLE_ROWS'] >= $GLOBALS['cfg']['MaxExactCountViews']
            ) {
                $approx_rows = true;
                $show_superscript = Generator::showHint(
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
            $approx_rows,
            $show_superscript,
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

        if (empty($favoriteInstanceTables)
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
        foreach ($db as $db_table) {
            if ($this->db == $this->replication->extractDbOrTable($db_table)
                && preg_match(
                    '@^' .
                    preg_quote(mb_substr($this->replication->extractDbOrTable($db_table, 'table'), 0, -1), '@') . '@',
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
     * @param array $current_table current table
     * @param int   $sum_size      total table size
     * @param int   $overhead_size overhead size
     *
     * @return array
     */
    protected function getStuffForEngineTypeTable(
        array $current_table,
        $sum_size,
        $overhead_size
    ) {
        $formatted_size = '-';
        $unit = '';
        $formatted_overhead = '';
        $overhead_unit = '';
        $table_is_view = false;

        switch ($current_table['ENGINE']) {
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
                    $current_table,
                    $formatted_size,
                    $unit,
                    $formatted_overhead,
                    $overhead_unit,
                    $overhead_size,
                    $sum_size,
                ] = $this->getValuesForAriaTable(
                    $current_table,
                    $sum_size,
                    $overhead_size,
                    $formatted_size,
                    $unit,
                    $formatted_overhead,
                    $overhead_unit
                );
                break;
            case 'InnoDB':
            case 'PBMS':
            case 'TokuDB':
                // InnoDB table: Row count is not accurate but data and index sizes are.
                // PBMS table in Drizzle: TABLE_ROWS is taken from table cache,
                // so it may be unavailable
                [$current_table, $formatted_size, $unit, $sum_size]
                = $this->getValuesForInnodbTable(
                    $current_table,
                    $sum_size
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
                    $formatted_size =  ' - ';
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
                    $formatted_size =  __('unknown');
                    $unit          =  '';
                }
        }

        if ($current_table['TABLE_TYPE'] === 'VIEW'
            || $current_table['TABLE_TYPE'] === 'SYSTEM VIEW'
        ) {
            // countRecords() takes care of $cfg['MaxExactCountViews']
            $current_table['TABLE_ROWS'] = $this->dbi
                ->getTable($this->db, $current_table['TABLE_NAME'])
                ->countRecords(true);
            $table_is_view = true;
        }

        return [
            $current_table,
            $formatted_size,
            $unit,
            $formatted_overhead,
            $overhead_unit,
            $overhead_size,
            $table_is_view,
            $sum_size,
        ];
    }

    /**
     * Get values for ARIA/MARIA tables
     *
     * @param array  $current_table      current table
     * @param int    $sum_size           sum size
     * @param int    $overhead_size      overhead size
     * @param int    $formatted_size     formatted size
     * @param string $unit               unit
     * @param int    $formatted_overhead overhead formatted
     * @param string $overhead_unit      overhead unit
     *
     * @return array
     */
    protected function getValuesForAriaTable(
        array $current_table,
        $sum_size,
        $overhead_size,
        $formatted_size,
        $unit,
        $formatted_overhead,
        $overhead_unit
    ) {
        if ($this->dbIsSystemSchema) {
            $current_table['Rows'] = $this->dbi
                ->getTable($this->db, $current_table['Name'])
                ->countRecords();
        }

        if ($this->isShowStats) {
            /** @var int $tblsize */
            $tblsize = $current_table['Data_length']
                + $current_table['Index_length'];
            $sum_size += $tblsize;
            [$formatted_size, $unit] = Util::formatByteDown(
                $tblsize,
                3,
                $tblsize > 0 ? 1 : 0
            );
            if (isset($current_table['Data_free'])
                && $current_table['Data_free'] > 0
            ) {
                [$formatted_overhead, $overhead_unit]
                    = Util::formatByteDown(
                        $current_table['Data_free'],
                        3,
                        ($current_table['Data_free'] > 0 ? 1 : 0)
                    );
                $overhead_size += $current_table['Data_free'];
            }
        }

        return [
            $current_table,
            $formatted_size,
            $unit,
            $formatted_overhead,
            $overhead_unit,
            $overhead_size,
            $sum_size,
        ];
    }

    /**
     * Get values for InnoDB table
     *
     * @param array $current_table current table
     * @param int   $sum_size      sum size
     *
     * @return array
     */
    protected function getValuesForInnodbTable(
        array $current_table,
        $sum_size
    ) {
        $formatted_size = $unit = '';

        if ((in_array($current_table['ENGINE'], ['InnoDB', 'TokuDB'])
            && $current_table['TABLE_ROWS'] < $GLOBALS['cfg']['MaxExactCount'])
            || ! isset($current_table['TABLE_ROWS'])
        ) {
            $current_table['COUNTED'] = true;
            $current_table['TABLE_ROWS'] = $this->dbi
                ->getTable($this->db, $current_table['TABLE_NAME'])
                ->countRecords(true);
        } else {
            $current_table['COUNTED'] = false;
        }

        if ($this->isShowStats) {
            /** @var int $tblsize */
            $tblsize = $current_table['Data_length']
                + $current_table['Index_length'];
            $sum_size += $tblsize;
            [$formatted_size, $unit] = Util::formatByteDown(
                $tblsize,
                3,
                ($tblsize > 0 ? 1 : 0)
            );
        }

        return [
            $current_table,
            $formatted_size,
            $unit,
            $sum_size,
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
        $submit_mult = $_POST['submit_mult'] ?? '';

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $route = '/database/structure/replace-prefix';
        if ($submit_mult === 'copy_tbl_change_prefix') {
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

        $full_query_views = '';
        $full_query = '';

        foreach ($selected as $selectedValue) {
            $current = $selectedValue;
            if (! empty($views) && in_array($current, $views)) {
                $full_query_views .= (empty($full_query_views) ? 'DROP VIEW ' : ', ')
                    . Util::backquote(htmlspecialchars($current));
            } else {
                $full_query .= (empty($full_query) ? 'DROP TABLE ' : ', ')
                    . Util::backquote(htmlspecialchars($current));
            }
        }

        if (! empty($full_query)) {
            $full_query .= ';<br>' . "\n";
        }
        if (! empty($full_query_views)) {
            $full_query .= $full_query_views . ';<br>' . "\n";
        }

        $_url_params = ['db' => $db];
        foreach ($selected as $selectedValue) {
            $_url_params['selected'][] = $selectedValue;
        }
        foreach ($views as $current) {
            $_url_params['views'][] = $current;
        }

        $this->render('database/structure/drop_form', [
            'url_params' => $_url_params,
            'full_query' => $full_query,
            'is_foreign_key_check' => Util::isForeignKeyCheck(),
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
            'is_foreign_key_check' => Util::isForeignKeyCheck(),
        ]);
    }

    public function dropTable(): void
    {
        global $db, $message, $reload, $sql_query;

        $reload = $_POST['reload'] ?? $reload ?? null;
        $mult_btn = $_POST['mult_btn'] ?? '';
        $selected = $_POST['selected'] ?? [];

        $views = $this->dbi->getVirtualTables($db);

        if ($mult_btn !== __('Yes')) {
            $message = Message::success(__('No change'));

            if (empty($_POST['message'])) {
                $_POST['message'] = Message::success();
            }

            unset($_POST['mult_btn']);

            $this->index();

            return;
        }

        $default_fk_check_value = Util::handleDisableFKCheckInit();
        $sql_query = '';
        $sql_query_views = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $this->relationCleanup->table($db, $selected[$i]);
            $current = $selected[$i];

            if (! empty($views) && in_array($current, $views)) {
                $sql_query_views .= (empty($sql_query_views) ? 'DROP VIEW ' : ', ') . Util::backquote($current);
            } else {
                $sql_query .= (empty($sql_query) ? 'DROP TABLE ' : ', ') . Util::backquote($current);
            }

            $reload = 1;
        }

        if (! empty($sql_query)) {
            $sql_query .= ';';
        } elseif (! empty($sql_query_views)) {
            $sql_query = $sql_query_views . ';';
            unset($sql_query_views);
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

        if ($result && ! empty($sql_query_views)) {
            $sql_query .= ' ' . $sql_query_views . ';';
            $result = $this->dbi->tryQuery($sql_query_views);
            unset($sql_query_views);
        }

        if (! $result) {
            $message = Message::error((string) $this->dbi->getError());
        }

        Util::handleDisableFKCheckCleanup($default_fk_check_value);

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

        $mult_btn = $_POST['mult_btn'] ?? '';
        $selected = $_POST['selected'] ?? [];

        if ($mult_btn !== __('Yes')) {
            $message = Message::success(__('No change'));

            if (empty($_POST['message'])) {
                $_POST['message'] = Message::success();
            }

            unset($_POST['mult_btn']);

            $this->index();

            return;
        }

        $default_fk_check_value = Util::handleDisableFKCheckInit();

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

        Util::handleDisableFKCheckCleanup($default_fk_check_value);

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
        $from_prefix = $_POST['from_prefix'] ?? '';
        $to_prefix = $_POST['to_prefix'] ?? '';

        $sql_query = '';
        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $current = $selected[$i];
            $subFromPrefix = mb_substr($current, 0, mb_strlen((string) $from_prefix));

            if ($subFromPrefix === $from_prefix) {
                $newTableName = $to_prefix . mb_substr(
                    $current,
                    mb_strlen((string) $from_prefix)
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
        $from_prefix = $_POST['from_prefix'] ?? null;
        $to_prefix = $_POST['to_prefix'] ?? null;

        $selectedCount = count($selected);

        for ($i = 0; $i < $selectedCount; $i++) {
            $current = $selected[$i];
            $newTableName = $to_prefix . mb_substr($current, mb_strlen((string) $from_prefix));

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
