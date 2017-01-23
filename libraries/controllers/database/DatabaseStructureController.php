<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\libraries\controllers\database\DatabaseStructureController
 *
 * @package PMA
 */

namespace PMA\libraries\controllers\database;

use PMA\libraries\config\PageSettings;
use PMA\libraries\controllers\DatabaseController;
use PMA\libraries\Message;
use PMA\libraries\RecentFavoriteTable;
use PMA\libraries\Template;
use PMA\libraries\Tracker;
use PMA\libraries\Util;

require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/display_create_table.lib.php';
require_once 'libraries/config/messages.inc.php';
require_once 'libraries/config/user_preferences.forms.php';
require_once 'libraries/config/page_settings.forms.php';

/**
 * Handles database structure logic
 *
 * @package PhpMyAdmin
 */
class DatabaseStructureController extends DatabaseController
{
    /**
     * @var string  The URL query string
     */
    protected $_url_query;
    /**
     * @var int Number of tables
     */
    protected $_num_tables;
    /**
     * @var int Current position in the list
     */
    protected $_pos;
    /**
     * @var bool DB is information_schema
     */
    protected $_db_is_system_schema;
    /**
     * @var int Number of tables
     */
    protected $_total_num_tables;
    /**
     * @var array Tables in the database
     */
    protected $_tables;
    /**
     * @var bool whether stats show or not
     */
    protected $_is_show_stats;

    /**
     * DatabaseStructureController constructor
     *
     * @param string $url_query URL query
     */
    public function __construct($url_query) {
        parent::__construct();

        $this->_url_query = $url_query;
    }

    /**
     * Retrieves databse information for further use
     *
     * @param string $sub_part Page part name
     *
     * @return void
     */
    private function _getDbInfo($sub_part)
    {
        list(
            $tables,
            $num_tables,
            $total_num_tables,
            ,
            $is_show_stats,
            $db_is_system_schema,
            ,
            ,
            $pos
        ) = Util::getDbInfo($this->db, $sub_part);

        $this->_tables = $tables;
        $this->_num_tables = $num_tables;
        $this->_pos = $pos;
        $this->_db_is_system_schema = $db_is_system_schema;
        $this->_total_num_tables = $total_num_tables;
        $this->_is_show_stats = $is_show_stats;
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        // Add/Remove favorite tables using Ajax request.
        if ($GLOBALS['is_ajax_request'] && !empty($_REQUEST['favorite_table'])) {
            $this->addRemoveFavoriteTablesAction();
            return;
        }

        // If there is an Ajax request for real row count of a table.
        if ($GLOBALS['is_ajax_request']
            && isset($_REQUEST['real_row_count'])
            && $_REQUEST['real_row_count'] == true
        ) {
            $this->handleRealRowCountRequestAction();
            return;
        }

        // Drops/deletes/etc. multiple tables if required
        if ((! empty($_POST['submit_mult']) && isset($_POST['selected_tbl']))
            || isset($_POST['mult_btn'])
        ) {
            $this->multiSubmitAction();
        }

        $this->response->getHeader()->getScripts()->addFiles(
            array(
                'db_structure.js',
                'tbl_change.js',
                'jquery/jquery-ui-timepicker-addon.js'
            )
        );

        $this->_url_query .= '&amp;goto=db_structure.php';

        // Gets the database structure
        $this->_getDbInfo('_structure');

        include_once 'libraries/replication.inc.php';

        PageSettings::showGroup('DbStructure');

        // 1. No tables
        if ($this->_num_tables == 0) {
            $this->response->addHTML(
                Message::notice(__('No tables found in database.'))
            );
            if (empty($this->_db_is_system_schema)) {
                $this->response->addHTML(PMA_getHtmlForCreateTable($this->db));
            }
            return;
        }

        // else
        // 2. Shows table information
        /**
         * Displays the tables list
         */
        $this->response->addHTML('<div id="tableslistcontainer">');
        $_url_params = array(
            'pos' => $this->_pos,
            'db'  => $this->db);

        // Add the sort options if they exists
        if (isset($_REQUEST['sort'])) {
            $_url_params['sort'] = $_REQUEST['sort'];
        }

        if (isset($_REQUEST['sort_order'])) {
            $_url_params['sort_order'] = $_REQUEST['sort_order'];
        }

        $this->response->addHTML(
            Util::getListNavigator(
                $this->_total_num_tables, $this->_pos, $_url_params,
                'db_structure.php', 'frame_content', $GLOBALS['cfg']['MaxTableList']
            )
        );

        $this->displayTableList();

        // display again the table list navigator
        $this->response->addHTML(
            Util::getListNavigator(
                $this->_total_num_tables, $this->_pos, $_url_params,
                'db_structure.php', 'frame_content',
                $GLOBALS['cfg']['MaxTableList']
            )
        );

        $this->response->addHTML('</div><hr />');

        /**
         * Work on the database
         */
        /* DATABASE WORK */
        /* Printable view of a table */
        $this->response->addHTML(
            Template::get('database/structure/print_view_data_dictionary_link')
                ->render(array('url_query' => $this->_url_query))
        );

        if (empty($this->_db_is_system_schema)) {
            $this->response->addHTML(PMA_getHtmlForCreateTable($this->db));
        }
    }

    /**
     * Add or remove favorite tables
     *
     * @return void
     */
    public function addRemoveFavoriteTablesAction()
    {
        $fav_instance = RecentFavoriteTable::getInstance('favorite');
        if (isset($_REQUEST['favorite_tables'])) {
            $favorite_tables = json_decode($_REQUEST['favorite_tables'], true);
        } else {
            $favorite_tables = array();
        }
        // Required to keep each user's preferences separate.
        $user = sha1($GLOBALS['cfg']['Server']['user']);

        // Request for Synchronization of favorite tables.
        if (isset($_REQUEST['sync_favorite_tables'])) {
            $this->synchronizeFavoriteTables($fav_instance, $user, $favorite_tables);
            return;
        }
        $changes = true;
        $titles = Util::buildActionTitles();
        $favorite_table = $_REQUEST['favorite_table'];
        $already_favorite = $this->checkFavoriteTable($favorite_table);

        if (isset($_REQUEST['remove_favorite'])) {
            if ($already_favorite) {
                // If already in favorite list, remove it.
                $fav_instance->remove($this->db, $favorite_table);
                $already_favorite = false; // for favorite_anchor template
            }
        } elseif (isset($_REQUEST['add_favorite'])) {
            if (!$already_favorite) {
                $nbTables = count($fav_instance->getTables());
                if ($nbTables == $GLOBALS['cfg']['NumFavoriteTables']) {
                    $changes = false;
                } else {
                    // Otherwise add to favorite list.
                    $fav_instance->add($this->db, $favorite_table);
                    $already_favorite = true;  // for favorite_anchor template
                }
            }
        }

        $favorite_tables[$user] = $fav_instance->getTables();
        $this->response->addJSON('changes', $changes);
        if (!$changes) {
            $this->response->addJSON(
                'message',
                Template::get('components/error_message')
                    ->render(
                        array(
                            'msg' => __("Favorite List is full!")
                        )
                    )
            );
            return;
        }
        $this->response->addJSON(
            array(
                'user' => $user,
                'favorite_tables' => json_encode($favorite_tables),
                'list' => $fav_instance->getHtmlList(),
                'anchor' => Template::get('database/structure/favorite_anchor')
                    ->render(
                        array(
                            'db' => $this->db,
                            'current_table' => array(
                                'TABLE_NAME' => $favorite_table
                            ),
                            'titles' => $titles,
                            'already_favorite' => $already_favorite
                        )
                    )
            )
        );
    }

    /**
     * Handles request for real row count on database level view page.
     *
     * @return boolean true
     */
    public function handleRealRowCountRequestAction()
    {
        $ajax_response = $this->response;
        // If there is a request to update all table's row count.
        if (!isset($_REQUEST['real_row_count_all'])) {
            // Get the real row count for the table.
            $real_row_count = $this->dbi
                ->getTable($this->db, $_REQUEST['table'])
                ->getRealRowCountTable();
            // Format the number.
            $real_row_count = Util::formatNumber($real_row_count, 0);
            $ajax_response->addJSON('real_row_count', $real_row_count);
            return;
        }

        // Array to store the results.
        $real_row_count_all = array();
        // Iterate over each table and fetch real row count.
        foreach ($this->_tables as $table) {
            $row_count = $this->dbi
                ->getTable($this->db, $table['TABLE_NAME'])
                ->getRealRowCountTable();
            $real_row_count_all[] = array(
                'table' => $table['TABLE_NAME'],
                'row_count' => $row_count
            );
        }

        $ajax_response->addJSON(
            'real_row_count_all',
            json_encode($real_row_count_all)
        );
    }

    /**
     * Handles actions related to multiple tables
     *
     * @return void
     */
    public function multiSubmitAction()
    {
        $action = 'db_structure.php';
        $err_url = 'db_structure.php' . PMA_URL_getCommon(
            array('db' => $this->db)
        );

        // see bug #2794840; in this case, code path is:
        // db_structure.php -> libraries/mult_submits.inc.php -> sql.php
        // -> db_structure.php and if we got an error on the multi submit,
        // we must display it here and not call again mult_submits.inc.php
        if (! isset($_POST['error']) || false === $_POST['error']) {
            include 'libraries/mult_submits.inc.php';
        }
        if (empty($_POST['message'])) {
            $_POST['message'] = Message::success();
        }
    }

    /**
     * Displays the list of tables
     *
     * @return void
     */
    protected function displayTableList()
    {
        // table form
        $this->response->addHTML(
            Template::get('database/structure/table_header')
                ->render(
                    array(
                        'db'                  => $this->db,
                        'db_is_system_schema' => $this->_db_is_system_schema,
                        'replication'         => $GLOBALS['replication_info']['slave']['status'],
                    )
                )
        );

        $i = $sum_entries = 0;
        $overhead_check = '';
        $create_time_all = '';
        $update_time_all = '';
        $check_time_all = '';
        $num_columns = $GLOBALS['cfg']['PropertiesNumColumns'] > 1
            ? ceil($this->_num_tables / $GLOBALS['cfg']['PropertiesNumColumns']) + 1
            : 0;
        $row_count      = 0;
        $sum_size       = 0;
        $overhead_size  = 0;

        $hidden_fields = array();
        $odd_row       = true;
        $overall_approx_rows = false;
        foreach ($this->_tables as $keyname => $current_table) {
            // Get valid statistics whatever is the table type

            $drop_query = '';
            $drop_message = '';
            $overhead = '';

            $table_is_view = false;
            $table_encoded = urlencode($current_table['TABLE_NAME']);
            // Sets parameters for links
            $tbl_url_query = $this->_url_query . '&amp;table=' . $table_encoded;
            // do not list the previous table's size info for a view

            list($current_table, $formatted_size, $unit, $formatted_overhead,
                $overhead_unit, $overhead_size, $table_is_view, $sum_size)
                    = $this->getStuffForEngineTypeTable(
                        $current_table, $sum_size, $overhead_size
                    );

            $curTable = $this->dbi
                ->getTable($this->db, $current_table['TABLE_NAME']);
            if (!$curTable->isMerge()) {
                $sum_entries += $current_table['TABLE_ROWS'];
            }

            if (isset($current_table['Collation'])) {
                $collation = '<dfn title="'
                    . PMA_getCollationDescr($current_table['Collation']) . '">'
                    . $current_table['Collation'] . '</dfn>';
            } else {
                $collation = '---';
            }

            if ($this->_is_show_stats) {
                if ($formatted_overhead != '') {
                    $overhead = '<a href="tbl_structure.php'
                        . $tbl_url_query . '#showusage">'
                        . '<span>' . $formatted_overhead . '</span>&nbsp;'
                        . '<span class="unit">' . $overhead_unit . '</span>'
                        . '</a>' . "\n";
                    $overhead_check .=
                        "markAllRows('row_tbl_" . ($i + 1) . "');";
                } else {
                    $overhead = '-';
                }
            } // end if

            if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
                $create_time = isset($current_table['Create_time'])
                    ? $current_table['Create_time'] : '';
                if ($create_time
                    && (!$create_time_all
                    || $create_time < $create_time_all)
                ) {
                    $create_time_all = $create_time;
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
                $update_time = isset($current_table['Update_time'])
                    ? $current_table['Update_time'] : '';
                if ($update_time
                    && (!$update_time_all
                    || $update_time < $update_time_all)
                ) {
                    $update_time_all = $update_time;
                }
            }

            if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
                $check_time = isset($current_table['Check_time'])
                    ? $current_table['Check_time'] : '';
                if ($check_time
                    && (!$check_time_all
                    || $check_time < $check_time_all)
                ) {
                    $check_time_all = $check_time;
                }
            }

            $truename = htmlspecialchars(
                (!empty($tooltip_truename)
                    && isset($tooltip_truename[$current_table['TABLE_NAME']]))
                ? $tooltip_truename[$current_table['TABLE_NAME']]
                : $current_table['TABLE_NAME']
            );
            $truename = str_replace(' ', '&nbsp;', $truename);

            $i++;

            $row_count++;
            if ($table_is_view) {
                $hidden_fields[] = '<input type="hidden" name="views[]" value="'
                    .  htmlspecialchars($current_table['TABLE_NAME']) . '" />';
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

            $browse_table = Template::get('database/structure/browse_table')
                ->render(
                    array(
                        'tbl_url_query' => $tbl_url_query,
                        'title'         => $may_have_rows ? $titles['Browse']
                            : $titles['NoBrowse'],
                    )
                );

            $search_table = Template::get('database/structure/search_table')
                ->render(
                    array(
                        'tbl_url_query' => $tbl_url_query,
                        'title'         => $may_have_rows ? $titles['Search']
                            : $titles['NoSearch'],
                    )
                );

            $browse_table_label = Template::get(
                'database/structure/browse_table_label'
            )
                ->render(
                    array(
                        'tbl_url_query' => $tbl_url_query,
                        'title'         => htmlspecialchars(
                            $current_table['TABLE_COMMENT']
                        ),
                        'truename'      => $truename,
                    )
                );

            $empty_table = '';
            if (!$this->_db_is_system_schema) {
                $empty_table = '&nbsp;';
                if (!$table_is_view) {
                    $empty_table = Template::get('database/structure/empty_table')
                        ->render(
                            array(
                                'tbl_url_query' => $tbl_url_query,
                                'sql_query' => urlencode(
                                    'TRUNCATE ' . Util::backquote(
                                        $current_table['TABLE_NAME']
                                    )
                                ),
                                'message_to_show' => urlencode(
                                    sprintf(
                                        __('Table %s has been emptied.'),
                                        htmlspecialchars(
                                            $current_table['TABLE_NAME']
                                        )
                                    )
                                ),
                                'title' => $may_have_rows ? $titles['Empty']
                                    : $titles['NoEmpty'],
                            )
                        );
                }
                $drop_query = sprintf(
                    'DROP %s %s',
                    ($table_is_view || $current_table['ENGINE'] == null) ? 'VIEW'
                    : 'TABLE',
                    Util::backquote(
                        $current_table['TABLE_NAME']
                    )
                );
                $drop_message = sprintf(
                    (($table_is_view || $current_table['ENGINE'] == null)
                        ? __('View %s has been dropped.')
                        : __('Table %s has been dropped.')),
                    str_replace(
                        ' ', '&nbsp;',
                        htmlspecialchars($current_table['TABLE_NAME'])
                    )
                );
            }

            if ($num_columns > 0
                && $this->_num_tables > $num_columns
                && ($row_count % $num_columns) == 0
            ) {
                $row_count = 1;
                $odd_row = true;

                $this->response->addHTML(
                    '</tr></tbody></table></form>'
                );

                $this->response->addHTML(
                    Template::get('database/structure/table_header')->render(
                        array(
                            'db' => $this->db,
                            'db_is_system_schema' => $this->_db_is_system_schema,
                            'replication' => $GLOBALS['replication_info']['slave']['status']
                        )
                    )
                );
            }

            list($approx_rows, $show_superscript) = $this->isRowCountApproximated(
                $current_table, $table_is_view
            );

            list($do, $ignored) = $this->getReplicationStatus($truename);

            $this->response->addHTML(
                Template::get('database/structure/structure_table_row')
                    ->render(
                        array(
                            'db'                    => $this->db,
                            'curr'                  => $i,
                            'odd_row'               => $odd_row,
                            'table_is_view'         => $table_is_view,
                            'current_table'         => $current_table,
                            'browse_table_label'    => $browse_table_label,
                            'tracking_icon'         => $this->getTrackingIcon($truename),
                            'server_slave_status'   => $GLOBALS['replication_info']['slave']['status'],
                            'browse_table'          => $browse_table,
                            'tbl_url_query'         => $tbl_url_query,
                            'search_table'          => $search_table,
                            'db_is_system_schema'   => $this->_db_is_system_schema,
                            'titles'                => $titles,
                            'empty_table'           => $empty_table,
                            'drop_query'            => $drop_query,
                            'drop_message'          => $drop_message,
                            'collation'             => $collation,
                            'formatted_size'        => $formatted_size,
                            'unit'                  => $unit,
                            'overhead'              => $overhead,
                            'create_time'           => isset($create_time)
                                ? $create_time : '',
                            'update_time'           => isset($update_time)
                                ? $update_time : '',
                            'check_time'            => isset($check_time)
                                ? $check_time : '',
                            'is_show_stats'         => $this->_is_show_stats,
                            'ignored'               => $ignored,
                            'do'                    => $do,
                            'colspan_for_structure' => $GLOBALS['colspan_for_structure'],
                            'approx_rows'           => $approx_rows,
                            'show_superscript'      => $show_superscript,
                            'already_favorite'      => $this->checkFavoriteTable(
                                $current_table['TABLE_NAME']
                            ),
                        )
                    )
            );

            $odd_row = ! $odd_row;
            $overall_approx_rows = $overall_approx_rows || $approx_rows;
        } // end foreach

        $this->response->addHTML('</tbody>');

        $db_collation = PMA_getDbCollation($this->db);

        // Show Summary
        $this->response->addHTML(
            Template::get('database/structure/body_for_table_summary')->render(
                array(
                    'num_tables' => $this->_num_tables,
                    'server_slave_status' => $GLOBALS['replication_info']['slave']['status'],
                    'db_is_system_schema' => $this->_db_is_system_schema,
                    'sum_entries' => $sum_entries,
                    'db_collation' => $db_collation,
                    'is_show_stats' => $this->_is_show_stats,
                    'sum_size' => $sum_size,
                    'overhead_size' => $overhead_size,
                    'create_time_all' => $create_time_all,
                    'update_time_all' => $update_time_all,
                    'check_time_all' => $check_time_all,
                    'approx_rows' => $overall_approx_rows
                )
            )
        );
        $this->response->addHTML('</table>');

        //check all
        $this->response->addHTML(
            Template::get('database/structure/check_all_tables')->render(
                array(
                    'pmaThemeImage' => $GLOBALS['pmaThemeImage'],
                    'text_dir' => $GLOBALS['text_dir'],
                    'overhead_check' => $overhead_check,
                    'db_is_system_schema' => $this->_db_is_system_schema,
                    'hidden_fields' => $hidden_fields
                )
            )
        );
        $this->response->addHTML('</form>'); //end of form
    }

    /**
     * Returns the tracking icon if the table is tracked
     *
     * @param string $table table name
     *
     * @return string HTML for tracking icon
     */
    protected function getTrackingIcon($table)
    {
        $tracking_icon = '';
        if (Tracker::isActive()) {
            $is_tracked = Tracker::isTracked($this->db, $table);
            if ($is_tracked
                || Tracker::getVersion($this->db, $table) > 0
            ) {
                $tracking_icon = Template::get(
                    'database/structure/tracking_icon'
                )
                ->render(
                    array(
                        'url_query' => $this->_url_query,
                        'truename' => $table,
                        'is_tracked' => $is_tracked,
                    )
                );
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
    protected function isRowCountApproximated($current_table, $table_is_view)
    {
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
            // InnoDB table: we did not get an accurate row count
            $approx_rows = !$table_is_view
                && $current_table['ENGINE'] == 'InnoDB'
                && !$current_table['COUNTED'];

            if ($table_is_view
                && $current_table['TABLE_ROWS'] >= $GLOBALS['cfg']['MaxExactCountViews']
            ) {
                $approx_rows = true;
                $show_superscript = Util::showHint(
                    PMA_sanitize(
                        sprintf(
                            __(
                                'This view has at least this number of '
                                . 'rows. Please refer to %sdocumentation%s.'
                            ),
                            '[doc@cfg_MaxExactCountViews]', '[/doc]'
                        )
                    )
                );
            }
        }

        return array($approx_rows, $show_superscript);
    }

    /**
     * Returns the replication status of the table.
     *
     * @param string $table table name
     *
     * @return array
     */
    protected function getReplicationStatus($table)
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
                $table, $GLOBALS['replication_info']['slave']['Do_DB']
            );
            $searchDoDBInDB = array_search(
                $this->db, $GLOBALS['replication_info']['slave']['Do_DB']
            );

            $do = strlen($searchDoDBInTruename) > 0
                || strlen($searchDoDBInDB) > 0
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
            $ignored = strlen($searchTable) > 0
                || strlen($searchDb) > 0
                || $this->hasTable(
                    $GLOBALS['replication_info']['slave']['Wild_Ignore_Table'],
                    $table
                );
        }

        return array($do, $ignored);
    }

    /**
     * Synchronize favorite tables
     *
     *
     * @param RecentFavoriteTable $fav_instance    Instance of this class
     * @param string              $user            The user hash
     * @param array               $favorite_tables Existing favorites
     *
     * @return void
     */
    protected function synchronizeFavoriteTables(
        $fav_instance,
        $user,
        $favorite_tables
    ) {
        $fav_instance_tables = $fav_instance->getTables();

        if (empty($fav_instance_tables)
            && isset($favorite_tables[$user])
        ) {
            foreach ($favorite_tables[$user] as $key => $value) {
                $fav_instance->add($value['db'], $value['table']);
            }
        }
        $favorite_tables[$user] = $fav_instance->getTables();

        $this->response->addJSON(
            array(
                'favorite_tables' => json_encode($favorite_tables),
                'list' => $fav_instance->getHtmlList()
            )
        );
        $server_id = $GLOBALS['server'];
        // Set flag when localStorage and pmadb(if present) are in sync.
        $_SESSION['tmpval']['favorites_synced'][$server_id] = true;
    }

    /**
     * Function to check if a table is already in favorite list.
     *
     * @param string $current_table current table
     *
     * @return true|false
     */
    protected function checkFavoriteTable($current_table)
    {
        // ensure $_SESSION['tmpval']['favorite_tables'] is initialized
        RecentFavoriteTable::getInstance('favorite');
        foreach (
            $_SESSION['tmpval']['favorite_tables'][$GLOBALS['server']] as $value
        ) {
            if ($value['db'] == $this->db && $value['table'] == $current_table) {
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
    protected function hasTable($db, $truename)
    {
        foreach ($db as $db_table) {
            if ($this->db == PMA_extractDbOrTable($db_table)
                && preg_match(
                    "@^" .
                    preg_quote(mb_substr(PMA_extractDbOrTable($db_table, 'table'), 0, -1)) . "@",
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
        $current_table, $sum_size, $overhead_size
    ) {
        $formatted_size = '-';
        $unit = '';
        $formatted_overhead = '';
        $overhead_unit = '';
        $table_is_view = false;

        switch ( $current_table['ENGINE']) {
        // MyISAM, ISAM or Heap table: Row count, data size and index size
        // are accurate; data size is accurate for ARCHIVE
        case 'MyISAM' :
        case 'ISAM' :
        case 'HEAP' :
        case 'MEMORY' :
        case 'ARCHIVE' :
        case 'Aria' :
        case 'Maria' :
        case 'TokuDB' :
            list($current_table, $formatted_size, $unit, $formatted_overhead,
                $overhead_unit, $overhead_size, $sum_size)
                    = $this->getValuesForAriaTable(
                        $current_table, $sum_size, $overhead_size,
                        $formatted_size, $unit, $formatted_overhead, $overhead_unit
                    );
            break;
        case 'InnoDB' :
        case 'PBMS' :
            // InnoDB table: Row count is not accurate but data and index sizes are.
            // PBMS table in Drizzle: TABLE_ROWS is taken from table cache,
            // so it may be unavailable
            list($current_table, $formatted_size, $unit, $sum_size)
                = $this->getValuesForInnodbTable(
                    $current_table, $sum_size
                );
            break;
        // Mysql 5.0.x (and lower) uses MRG_MyISAM
        // and MySQL 5.1.x (and higher) uses MRG_MYISAM
        // Both are aliases for MERGE
        case 'MRG_MyISAM' :
        case 'MRG_MYISAM' :
        case 'MERGE' :
        case 'BerkeleyDB' :
            // Merge or BerkleyDB table: Only row count is accurate.
            if ($this->_is_show_stats) {
                $formatted_size =  ' - ';
                $unit          =  '';
            }
            break;
        // for a view, the ENGINE is sometimes reported as null,
        // or on some servers it's reported as "SYSTEM VIEW"
        case null :
        case 'SYSTEM VIEW' :
            // possibly a view, do nothing
            break;
        default :
            // Unknown table type.
            if ($this->_is_show_stats) {
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

        return array($current_table, $formatted_size, $unit, $formatted_overhead,
            $overhead_unit, $overhead_size, $table_is_view, $sum_size
        );
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
        $current_table, $sum_size, $overhead_size, $formatted_size, $unit,
        $formatted_overhead, $overhead_unit
    ) {
        if ($this->_db_is_system_schema) {
            $current_table['Rows'] = $this->dbi
                ->getTable($this->db, $current_table['Name'])
                ->countRecords();
        }

        if ($this->_is_show_stats) {
            $tblsize = $current_table['Data_length']
                + $current_table['Index_length'];
            $sum_size += $tblsize;
            list($formatted_size, $unit) = Util::formatByteDown(
                $tblsize, 3, ($tblsize > 0) ? 1 : 0
            );
            if (isset($current_table['Data_free'])
                && $current_table['Data_free'] > 0
            ) {
                // here, the value 4 as the second parameter
                // would transform 6.1MiB into 6,224.6KiB
                list($formatted_overhead, $overhead_unit)
                    = Util::formatByteDown(
                        $current_table['Data_free'], 4,
                        (($current_table['Data_free'] > 0) ? 1 : 0)
                    );
                $overhead_size += $current_table['Data_free'];
            }
        }
        return array($current_table, $formatted_size, $unit, $formatted_overhead,
            $overhead_unit, $overhead_size, $sum_size
        );
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
        $current_table, $sum_size
    ) {
        $formatted_size = $unit = '';

        if (($current_table['ENGINE'] == 'InnoDB'
            && $current_table['TABLE_ROWS'] < $GLOBALS['cfg']['MaxExactCount'])
            || !isset($current_table['TABLE_ROWS'])
        ) {
            $current_table['COUNTED'] = true;
            $current_table['TABLE_ROWS'] = $this->dbi
                ->getTable($this->db, $current_table['TABLE_NAME'])
                ->countRecords(true);
        } else {
            $current_table['COUNTED'] = false;
        }

        if ($this->_is_show_stats) {
            $tblsize = $current_table['Data_length']
                + $current_table['Index_length'];
            $sum_size += $tblsize;
            list($formatted_size, $unit) = Util::formatByteDown(
                $tblsize, 3, (($tblsize > 0) ? 1 : 0)
            );
        }

        return array($current_table, $formatted_size, $unit, $sum_size);
    }
}
