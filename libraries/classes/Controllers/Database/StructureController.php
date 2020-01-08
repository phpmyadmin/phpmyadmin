<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Database\StructureController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\CreateTable;
use PhpMyAdmin\Message;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Replication;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Handles database structure logic
 *
 * @package PhpMyAdmin\Controllers
 */
class StructureController extends AbstractController
{
    /**
     * @var int Number of tables
     */
    protected $numTables;
    /**
     * @var int Current position in the list
     */
    protected $position;
    /**
     * @var bool DB is information_schema
     */
    protected $dbIsSystemSchema;
    /**
     * @var int Number of tables
     */
    protected $totalNumTables;
    /**
     * @var array Tables in the database
     */
    protected $tables;
    /**
     * @var bool whether stats show or not
     */
    protected $isShowStats;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * @var Replication
     */
    private $replication;

    /**
     * Constructor
     *
     * @param Response          $response    Response instance
     * @param DatabaseInterface $dbi         DatabaseInterface instance
     * @param Template          $template    Template object
     * @param string            $db          Database name
     * @param Relation          $relation    Relation instance
     * @param Replication       $replication Replication instance
     */
    public function __construct($response, $dbi, Template $template, $db, $relation, $replication)
    {
        parent::__construct($response, $dbi, $template, $db);
        $this->relation = $relation;
        $this->replication = $replication;
    }

    /**
     * Retrieves database information for further use
     *
     * @param string $subPart Page part name
     *
     * @return void
     */
    private function getDatabaseInfo(string $subPart): void
    {
        list(
            $tables,
            $numTables,
            $totalNumTables,
            ,
            $isShowStats,
            $dbIsSystemSchema,
            ,
            ,
            $position
        ) = Util::getDbInfo($this->db, $subPart);

        $this->tables = $tables;
        $this->numTables = $numTables;
        $this->position = $position;
        $this->dbIsSystemSchema = $dbIsSystemSchema;
        $this->totalNumTables = $totalNumTables;
        $this->isShowStats = $isShowStats;
    }

    /**
     * Index action
     *
     * @param array $params Request parameters
     * @return string HTML
     */
    public function index(array $params): string
    {
        global $cfg;

        // Drops/deletes/etc. multiple tables if required
        if ((! empty($params['submit_mult']) && isset($params['selected_tbl']))
            || isset($params['mult_btn'])
        ) {
            $this->multiSubmitAction();
        }

        // Gets the database structure
        $this->getDatabaseInfo('_structure');

        // Checks if there are any tables to be shown on current page.
        // If there are no tables, the user is redirected to the last page
        // having any.
        if ($this->totalNumTables > 0 && $this->position > $this->totalNumTables) {
            $uri = './db_structure.php' . Url::getCommonRaw([
                'db' => $this->db,
                'pos' => max(0, $this->totalNumTables - $cfg['MaxTableList']),
                'reload' => 1,
            ]);
            Core::sendHeaderLocation($uri);
        }

        include_once ROOT_PATH . 'libraries/replication.inc.php';

        PageSettings::showGroup('DbStructure');

        if ($this->numTables > 0) {
            $urlParams = [
                'pos' => $this->position,
                'db' => $this->db,
            ];
            if (isset($params['sort'])) {
                $urlParams['sort'] = $params['sort'];
            }
            if (isset($params['sort_order'])) {
                $urlParams['sort_order'] = $params['sort_order'];
            }
            $listNavigator = Util::getListNavigator(
                $this->totalNumTables,
                $this->position,
                $urlParams,
                'db_structure.php',
                'frame_content',
                $cfg['MaxTableList']
            );

            $tableList = $this->displayTableList();
        }

        $createTable = '';
        if (empty($this->dbIsSystemSchema)) {
            $createTable = CreateTable::getHtml($this->db);
        }

        return $this->template->render('database/structure/index', [
            'database' => $this->db,
            'has_tables' => $this->numTables > 0,
            'list_navigator_html' => $listNavigator ?? '',
            'table_list_html' => $tableList ?? '',
            'is_system_schema' => ! empty($this->dbIsSystemSchema),
            'create_table_html' => $createTable,
        ]);
    }

    /**
     * Add or remove favorite tables
     *
     * @param array $params Request parameters
     * @return array|null JSON
     */
    public function addRemoveFavoriteTablesAction(array $params): ?array
    {
        global $cfg;

        $favoriteInstance = RecentFavoriteTable::getInstance('favorite');
        if (isset($params['favoriteTables'])) {
            $favoriteTables = json_decode($params['favoriteTables'], true);
        } else {
            $favoriteTables = [];
        }
        // Required to keep each user's preferences separate.
        $user = sha1($cfg['Server']['user']);

        // Request for Synchronization of favorite tables.
        if (isset($params['sync_favorite_tables'])) {
            $cfgRelation = $this->relation->getRelationsParam();
            if ($cfgRelation['favoritework']) {
                return $this->synchronizeFavoriteTables($favoriteInstance, $user, $favoriteTables);
            }
            return null;
        }
        $changes = true;
        $titles = Util::buildActionTitles();
        $favoriteTable = $params['favorite_table'];
        $alreadyFavorite = $this->checkFavoriteTable($favoriteTable);

        if (isset($params['remove_favorite'])) {
            if ($alreadyFavorite) {
                // If already in favorite list, remove it.
                $favoriteInstance->remove($this->db, $favoriteTable);
                $alreadyFavorite = false; // for favorite_anchor template
            }
        } elseif (isset($params['add_favorite'])) {
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
                'msg' => __("Favorite List is full!"),
            ]);
            return $json;
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
            'db_table_name_hash' => md5($this->db . "." . $favoriteTable),
            'fav_params' => $favoriteParams,
            'already_favorite' => $alreadyFavorite,
            'titles' => $titles,
        ]);

        return $json;
    }

    /**
     * Handles request for real row count on database level view page.
     *
     * @param array $params Request parameters
     * @return array JSON
     */
    public function handleRealRowCountRequestAction(array $params): array
    {
        // If there is a request to update all table's row count.
        if (! isset($params['real_row_count_all'])) {
            // Get the real row count for the table.
            $realRowCount = $this->dbi
                ->getTable($this->db, (string) $params['table'])
                ->getRealRowCountTable();
            // Format the number.
            $realRowCount = Util::formatNumber($realRowCount, 0);

            return ['real_row_count' => $realRowCount];
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

        return ['real_row_count_all' => json_encode($realRowCountAll)];
    }

    /**
     * Handles actions related to multiple tables
     *
     * @return void
     */
    public function multiSubmitAction(): void
    {
        $action = 'db_structure.php';
        $err_url = 'db_structure.php' . Url::getCommon(
            ['db' => $this->db]
        );

        // see bug #2794840; in this case, code path is:
        // db_structure.php -> libraries/mult_submits.inc.php -> sql.php
        // -> db_structure.php and if we got an error on the multi submit,
        // we must display it here and not call again mult_submits.inc.php
        if (! isset($_POST['error']) || false === $_POST['error']) {
            include ROOT_PATH . 'libraries/mult_submits.inc.php';
        }
        if (empty($_POST['message'])) {
            $_POST['message'] = Message::success();
        }
    }

    /**
     * Displays the list of tables
     *
     * @return string HTML
     */
    protected function displayTableList(): string
    {
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

            $table_is_view = false;
            // Sets parameters for links
            $tbl_url_query = Url::getCommon(
                [
                    'db' => $this->db,
                    'table' => $current_table['TABLE_NAME'],
                ]
            );
            // do not list the previous table's size info for a view

            list($current_table, $formatted_size, $unit, $formatted_overhead,
                $overhead_unit, $overhead_size, $table_is_view, $sum_size)
                    = $this->getStuffForEngineTypeTable(
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
                    $collationDefinition = '<dfn title="'
                        . $tableCollation->getDescription() . '">'
                        . $tableCollation->getName() . '</dfn>';
                }
            }

            if ($this->isShowStats) {
                $overhead = '-';
                if ($formatted_overhead != '') {
                    $overhead = '<a href="tbl_structure.php'
                        . $tbl_url_query . '#showusage">'
                        . '<span>' . $formatted_overhead . '</span>&nbsp;'
                        . '<span class="unit">' . $overhead_unit . '</span>'
                        . '</a>' . "\n";
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
                $create_time = isset($current_table['Create_time'])
                    ? $current_table['Create_time'] : '';
                if ($create_time
                    && (! $create_time_all
                    || $create_time < $create_time_all)
                ) {
                    $create_time_all = $create_time;
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
                $update_time = isset($current_table['Update_time'])
                    ? $current_table['Update_time'] : '';
                if ($update_time
                    && (! $update_time_all
                    || $update_time < $update_time_all)
                ) {
                    $update_time_all = $update_time;
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
                $check_time = isset($current_table['Check_time'])
                    ? $current_table['Check_time'] : '';
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
            $titles = Util::buildActionTitles();

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
                    'replication' => $GLOBALS['replication_info']['slave']['status'],
                    'properties_num_columns' => $GLOBALS['cfg']['PropertiesNumColumns'],
                    'is_show_stats' => $GLOBALS['is_show_stats'],
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

            list($approx_rows, $show_superscript) = $this->isRowCountApproximated(
                $current_table,
                $table_is_view
            );

            list($do, $ignored) = $this->getReplicationStatus($truename);

            $structure_table_rows[] = [
                'table_name_hash' => md5($current_table['TABLE_NAME']),
                'db_table_name_hash' => md5($this->db . '.' . $current_table['TABLE_NAME']),
                'db' => $this->db,
                'curr' => $i,
                'input_class' => implode(' ', $input_class),
                'table_is_view' => $table_is_view,
                'current_table' => $current_table,
                'browse_table_title' => $may_have_rows ? $titles['Browse'] : $titles['NoBrowse'],
                'search_table_title' => $may_have_rows ? $titles['Search'] : $titles['NoSearch'],
                'browse_table_label_title' => htmlspecialchars($current_table['TABLE_COMMENT']),
                'browse_table_label_truename' => $truename,
                'empty_table_sql_query' => urlencode(
                    'TRUNCATE ' . Util::backquote(
                        $current_table['TABLE_NAME']
                    )
                ),
                'empty_table_message_to_show' => urlencode(
                    sprintf(
                        __('Table %s has been emptied.'),
                        htmlspecialchars(
                            $current_table['TABLE_NAME']
                        )
                    )
                ),
                'empty_table_title' => $may_have_rows ? $titles['Empty'] : $titles['NoEmpty'],
                'tracking_icon' => $this->getTrackingIcon($truename),
                'server_slave_status' => $GLOBALS['replication_info']['slave']['status'],
                'tbl_url_query' => $tbl_url_query,
                'db_is_system_schema' => $this->dbIsSystemSchema,
                'titles' => $titles,
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
                'charset' => isset($charset)
                        ? $charset : '',
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

        // table form
        $html .= $this->template->render('database/structure/table_header', [
            'db' => $this->db,
            'db_is_system_schema' => $this->dbIsSystemSchema,
            'replication' => $GLOBALS['replication_info']['slave']['status'],
            'properties_num_columns' => $GLOBALS['cfg']['PropertiesNumColumns'],
            'is_show_stats' => $GLOBALS['is_show_stats'],
            'show_charset' => $GLOBALS['cfg']['ShowDbStructureCharset'],
            'show_comment' => $GLOBALS['cfg']['ShowDbStructureComment'],
            'show_creation' => $GLOBALS['cfg']['ShowDbStructureCreation'],
            'show_last_update' => $GLOBALS['cfg']['ShowDbStructureLastUpdate'],
            'show_last_check' => $GLOBALS['cfg']['ShowDbStructureLastCheck'],
            'num_favorite_tables' => $GLOBALS['cfg']['NumFavoriteTables'],
            'structure_table_rows' => $structure_table_rows,
            'body_for_table_summary' => [
                'num_tables' => $this->numTables,
                'server_slave_status' => $GLOBALS['replication_info']['slave']['status'],
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
                'pma_theme_image' => $GLOBALS['pmaThemeImage'],
                'text_dir' => $GLOBALS['text_dir'],
                'overhead_check' => $overhead_check,
                'db_is_system_schema' => $this->dbIsSystemSchema,
                'hidden_fields' => $hidden_fields,
                'disable_multi_table' => $GLOBALS['cfg']['DisableMultiTableMaintenance'],
                'central_columns_work' => $GLOBALS['cfgRelation']['centralcolumnswork'],
            ],
        ]);

        return $html;
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
     * @param array   $current_table array containing details about the table
     * @param boolean $table_is_view whether the table is a view
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
                $show_superscript = Util::showHint(
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
     * @param string $table table name
     *
     * @return array
     */
    protected function getReplicationStatus(string $table): array
    {
        $do = $ignored = false;
        if ($GLOBALS['replication_info']['slave']['status']) {
            $nbServSlaveDoDb = count(
                $GLOBALS['replication_info']['slave']['Do_DB']
            );
            $nbServSlaveIgnoreDb = count(
                $GLOBALS['replication_info']['slave']['Ignore_DB']
            );
            $searchDoDBInTruename = array_search(
                $table,
                $GLOBALS['replication_info']['slave']['Do_DB']
            );
            $searchDoDBInDB = array_search(
                $this->db,
                $GLOBALS['replication_info']['slave']['Do_DB']
            );

            $do = (is_string($searchDoDBInTruename) && strlen($searchDoDBInTruename) > 0)
                || (is_string($searchDoDBInDB) && strlen($searchDoDBInDB) > 0)
                || ($nbServSlaveDoDb == 0 && $nbServSlaveIgnoreDb == 0)
                || $this->hasTable(
                    $GLOBALS['replication_info']['slave']['Wild_Do_Table'],
                    $table
                );

            $searchDb = array_search(
                $this->db,
                $GLOBALS['replication_info']['slave']['Ignore_DB']
            );
            $searchTable = array_search(
                $table,
                $GLOBALS['replication_info']['slave']['Ignore_Table']
            );
            $ignored = (is_string($searchTable) && strlen($searchTable) > 0)
                || (is_string($searchDb) && strlen($searchDb) > 0)
                || $this->hasTable(
                    $GLOBALS['replication_info']['slave']['Wild_Ignore_Table'],
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
     *
     * @return bool
     */
    protected function checkFavoriteTable(string $currentTable): bool
    {
        // ensure $_SESSION['tmpval']['favoriteTables'] is initialized
        RecentFavoriteTable::getInstance('favorite');
        foreach ($_SESSION['tmpval']['favoriteTables'][$GLOBALS['server']] as $value) {
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
     * @param array   $current_table current table
     * @param integer $sum_size      total table size
     * @param integer $overhead_size overhead size
     *
     * @return array
     * @internal param bool $table_is_view whether table is view or not
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
                list($current_table, $formatted_size, $unit, $formatted_overhead,
                $overhead_unit, $overhead_size, $sum_size)
                    = $this->getValuesForAriaTable(
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
                list($current_table, $formatted_size, $unit, $sum_size)
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
        } // end switch

        if ($current_table['TABLE_TYPE'] == 'VIEW'
            || $current_table['TABLE_TYPE'] == 'SYSTEM VIEW'
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
     * @param array   $current_table      current table
     * @param integer $sum_size           sum size
     * @param integer $overhead_size      overhead size
     * @param integer $formatted_size     formatted size
     * @param string  $unit               unit
     * @param integer $formatted_overhead overhead formatted
     * @param string  $overhead_unit      overhead unit
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
            $tblsize = $current_table['Data_length']
                + $current_table['Index_length'];
            $sum_size += $tblsize;
            list($formatted_size, $unit) = Util::formatByteDown(
                $tblsize,
                3,
                $tblsize > 0 ? 1 : 0
            );
            if (isset($current_table['Data_free'])
                && $current_table['Data_free'] > 0
            ) {
                list($formatted_overhead, $overhead_unit)
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
     * @param array   $current_table current table
     * @param integer $sum_size      sum size
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
            $tblsize = $current_table['Data_length']
                + $current_table['Index_length'];
            $sum_size += $tblsize;
            list($formatted_size, $unit) = Util::formatByteDown(
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
}
