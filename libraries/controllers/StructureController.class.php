<?php

namespace PMA\Controllers;

use PMA\Template;
use PMA_Index;
use PMA_RecentFavoriteTable;
use PMA_Table;
use PMA_Tracker;
use PMA_Message;
use PMA_PageSettings;
use PMA_Util;
use PMA\Util;
use SqlParser;

require_once 'libraries/common.inc.php';
require_once 'libraries/tbl_info.inc.php';
require_once 'libraries/Index.class.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/config/page_settings.class.php';
require_once 'libraries/util.lib.php';
require_once 'libraries/display_create_table.lib.php';
require_once 'libraries/transformations.lib.php';
require_once 'libraries/Template.class.php';
require_once 'libraries/util.lib.php';
require_once 'libraries/controllers/Controller.class.php';

class StructureController extends Controller
{
    /**
     * @var string  Indicate the db_structure or tbl_structure
     */
    protected $_type;

    /**
     * @var string  The database name
     */
    protected $_db;

    /**
     * @var string  The table name
     */
    protected $_table;

    /**
     * @var PMA_Table  The table object
     */
    protected $_table_obj;

    /**
     * @var string  The URL query string
     */
    protected $_url_query;
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
     * @var int Number of tables
     */
    protected $_num_tables;

    /**
     * @var array   Tables in the database
     */
    protected $_tables;
    /**
     * @var bool Table is a view
     */
    protected $_tbl_is_view;
    /**
     * @var bool whether stats show or not
     */
    protected $_is_show_stats;
    /**
     * @var string Table storage engine
     */
    protected $_tbl_storage_engine;
    /**
     * @var int Number of rows
     */
    protected $_table_info_num_rows;
    /**
     * @var string Table collation
     */
    protected $_tbl_collation;

    /**
     * @var array Show table info
     */
    protected $_showtable;

    /**
     * StructureController constructor
     *
     * @param string $type                Indicate the db_structure or tbl_structure
     * @param string $db                  DB name
     * @param string $table               Table name
     * @param string $url_query           URL query
     * @param int    $num_tables          Number of tables
     * @param int    $pos                 Current position in the list
     * @param bool   $db_is_system_schema DB is information_schema
     * @param int    $total_num_tables    Number of tables
     * @param array  $tables              Tables in the DB
     * @param bool   $is_show_stats       Whether stats show or not
     * @param bool   $tbl_is_view         Table is a view
     * @param string $tbl_storage_engine  Table storage engine
     * @param int    $table_info_num_rows Number of rows
     * @param string $tbl_collation       Table collation
     * @param array  $showtable           Show table info
     */
    public function __construct(
        $type, $db, $table, $url_query, $num_tables, $pos, $db_is_system_schema,
        $total_num_tables, $tables, $is_show_stats, $tbl_is_view,
        $tbl_storage_engine, $table_info_num_rows, $tbl_collation, $showtable
    ) {
        parent::__construct();

        $this->_type = $type;
        $this->_db = $db;
        $this->_table = $table;
        $this->_num_tables = $num_tables;
        $this->_pos = $pos;
        $this->_db_is_system_schema = $db_is_system_schema;
        $this->_total_num_tables = $total_num_tables;
        $this->_tables = $tables;
        $this->_is_show_stats = $is_show_stats;
        $this->_url_query = $url_query;
        $this->_tbl_is_view = $tbl_is_view;
        $this->_tbl_storage_engine = $tbl_storage_engine;
        $this->_table_info_num_rows = $table_info_num_rows;
        $this->_tbl_collation = $tbl_collation;
        $this->_showtable = $showtable;
        $this->_table_obj = new PMA_Table($this->_table, $this->_db);
    }

    public function indexAction()
    {
        // Database structure
        if ($this->_type == 'db') {
            // Add/Remove favorite tables using Ajax request.
            if ($GLOBALS['is_ajax_request'] && ! empty($_REQUEST['favorite_table'])) {
                $this->addRemoveFavoriteTables();
                return;
            }

            $this->response->getHeader()->getScripts()->addFiles(
                array(
                    'db_structure.js',
                    'tbl_change.js',
                    'jquery/jquery-ui-timepicker-addon.js'
                )
            );

            // Drops/deletes/etc. multiple tables if required
            if ((!empty($_POST['submit_mult']) && isset($_POST['selected_tbl']))
                || isset($_POST['mult_btn'])
            ) {
                $action = 'db_structure.php';
                $err_url = 'db_structure.php' . PMA_URL_getCommon(array('db' => $this->_db));

                // see bug #2794840; in this case, code path is:
                // db_structure.php -> libraries/mult_submits.inc.php -> sql.php
                // -> db_structure.php and if we got an error on the multi submit,
                // we must display it here and not call again mult_submits.inc.php
                if (! isset($_POST['error']) || false === $_POST['error']) {
                    include 'libraries/mult_submits.inc.php';
                }
                if (empty($_POST['message'])) {
                    $_POST['message'] = PMA_Message::success();
                }
            }

            $this->_url_query .= '&amp;goto=db_structure.php';

            // Gets the database structure
            $sub_part = '_structure';

            // If there is an Ajax request for real row count of a table.
            if ($GLOBALS['is_ajax_request']
                && isset($_REQUEST['real_row_count'])
                && $_REQUEST['real_row_count'] == true
            ) {
                $this->handleRealRowCountRequestAction();
                return;
            }

            if (!PMA_DRIZZLE) {
                include_once 'libraries/replication.inc.php';
            } else {
                $GLOBALS['replication_info']['slave']['status'] = false;
            }

            PMA_PageSettings::showGroup('DbStructure');

            $db_collation = PMA_getDbCollation($this->_db);

            $titles = PMA_Util::buildActionTitles();

            // 1. No tables

            if ($this->_num_tables == 0) {
                $this->response->addHTML(
                    PMA_message::notice(__('No tables found in database.'))
                );
                if (empty($db_is_system_schema)) {
                    $this->response->addHTML(PMA_getHtmlForCreateTable($this->_db));
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
                'db'  => $this->_db);

            // Add the sort options if they exists
            if (isset($_REQUEST['sort'])) {
                $_url_params['sort'] = $_REQUEST['sort'];
            }

            if (isset($_REQUEST['sort_order'])) {
                $_url_params['sort_order'] = $_REQUEST['sort_order'];
            }

            $this->response->addHTML(
                PMA_Util::getListNavigator(
                    $this->_total_num_tables, $this->_pos, $_url_params, 'db_structure.php',
                    'frame_content', $GLOBALS['cfg']['MaxTableList']
                )
            );

            // table form
            $this->response->addHTML(
                Template::get('structure/table_header')->render(
                    array(
                        'db' => $this->_db,
                        'db_is_system_schema' => $this->_db_is_system_schema,
                        'replication' => $GLOBALS['replication_info']['slave']['status']
                    )
                )
            );

            $i = $sum_entries = 0;
            $overhead_check = '';
            $create_time_all = '';
            $update_time_all = '';
            $check_time_all = '';
            $num_columns    = $GLOBALS['cfg']['PropertiesNumColumns'] > 1
                ? ceil($this->_num_tables / $GLOBALS['cfg']['PropertiesNumColumns']) + 1
                : 0;
            $row_count      = 0;
            $sum_size       = (double) 0;
            $overhead_size  = (double) 0;

            $hidden_fields = array();
            $odd_row       = true;
            $overall_approx_rows = false;
            // Instance of PMA_RecentFavoriteTable class.
            $fav_instance = PMA_RecentFavoriteTable::getInstance('favorite');
            foreach ($this->_tables as $keyname => $current_table) {
                // Get valid statistics whatever is the table type

                $drop_query = '';
                $drop_message = '';
                $already_favorite = false;
                $overhead = '';

                $table_is_view = false;
                $table_encoded = urlencode($current_table['TABLE_NAME']);
                // Sets parameters for links
                $tbl_url_query = $this->_url_query . '&amp;table=' . $table_encoded;
                // do not list the previous table's size info for a view

                list($current_table, $formatted_size, $unit, $formatted_overhead,
                    $overhead_unit, $overhead_size, $table_is_view, $sum_size)
                    = $this->getStuffForEngineTypeTable(
                        $current_table, $this->_db_is_system_schema,
                        $this->_is_show_stats, $sum_size, $overhead_size
                    );

                if (! $this->dbi->getTable($this->_db, $current_table['TABLE_NAME'])->isMerge()) {
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

                $showtable = $this->dbi->getTable(
                    $this->_db, $current_table['TABLE_NAME']
                )->sGetStatusInfo(null, true);

                if ($GLOBALS['cfg']['ShowDbStructureCreation']) {
                    $create_time = isset($showtable['Create_time']) ? $showtable['Create_time'] : '';
                    if ($create_time && (!$create_time_all || $create_time < $create_time_all)) {
                        $create_time_all = $create_time;
                    }
                }

                if ($GLOBALS['cfg']['ShowDbStructureLastUpdate']) {
                    // $showtable might already be set from ShowDbStructureCreation, see above
                    $update_time = isset($showtable['Update_time']) ? $showtable['Update_time'] : '';
                    if ($update_time && (!$update_time_all || $update_time < $update_time_all)) {
                        $update_time_all = $update_time;
                    }
                }

                if ($GLOBALS['cfg']['ShowDbStructureLastCheck']) {
                    // $showtable might already be set from ShowDbStructureCreation, see above
                    $check_time = isset($showtable['Check_time']) ? $showtable['Check_time'] : '';
                    if ($check_time && (!$check_time_all || $check_time < $check_time_all)) {
                        $check_time_all = $check_time;
                    }
                }

                $alias = htmlspecialchars(
                    (!empty($tooltip_aliasname) && isset($tooltip_aliasname[$current_table['TABLE_NAME']]))
                    ? $tooltip_aliasname[$current_table['TABLE_NAME']]
                    : $current_table['TABLE_NAME']
                );
                $alias = str_replace(' ', '&nbsp;', $alias);

                $truename = htmlspecialchars(
                    (!empty($tooltip_truename) && isset($tooltip_truename[$current_table['TABLE_NAME']]))
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

                $browse_table = Template::get('structure/browse_table')
                    ->render(
                        array(
                            'tbl_url_query' => $tbl_url_query,
                            'title' => $may_have_rows ? $titles['Browse'] : $titles['NoBrowse']
                        )
                    );

                $search_table = Template::get('structure/search_table')
                    ->render(
                        array(
                            'tbl_url_query' => $tbl_url_query,
                            'title' => $may_have_rows ? $titles['Search'] : $titles['NoSearch']
                        )
                    );

                $browse_table_label = Template::get('structure/browse_table_label')
                    ->render(
                        array(
                            'tbl_url_query' => $tbl_url_query,
                            'title' => htmlspecialchars($current_table['TABLE_COMMENT']),
                            'truename' => $truename
                        )
                    );

                $empty_table = '';
                if (!$this->_db_is_system_schema) {
                    $empty_table = '&nbsp;';
                    if (!$table_is_view) {
                        $empty_table = Template::get('structure/empty_table')
                            ->render(
                                array(
                                    'tbl_url_query' => $tbl_url_query,
                                    'sql_query' => urlencode(
                                        'TRUNCATE ' . PMA_Util::backquote($current_table['TABLE_NAME'])
                                    ),
                                    'message_to_show' => urlencode(
                                        sprintf(
                                            __('Table %s has been emptied.'),
                                            htmlspecialchars($current_table['TABLE_NAME'])
                                        )
                                    ),
                                    'title' => $may_have_rows ? $titles['Empty'] : $titles['NoEmpty']
                                )
                            );
                    }
                    $drop_query = sprintf(
                        'DROP %s %s',
                        ($table_is_view || $current_table['ENGINE'] == null) ? 'VIEW' : 'TABLE',
                        PMA_Util::backquote(
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

                $tracking_icon = '';
                if (PMA_Tracker::isActive()) {
                    $is_tracked = PMA_Tracker::isTracked($GLOBALS["db"], $truename);
                    if ($is_tracked || PMA_Tracker::getVersion($GLOBALS["db"], $truename) > 0) {
                        $tracking_icon = Template::get('structure/tracking_icon')
                            ->render(
                                array(
                                    'url_query' => $this->_url_query,
                                    'truename' => $truename,
                                    'is_tracked' => $is_tracked
                                )
                            );
                    }
                }

                if ($num_columns > 0
                    && $this->_num_tables > $num_columns
                    && ($row_count % $num_columns) == 0
                ) {
                    $row_count = 1;
                    $odd_row = true;

                    $this->response->addHTML(
                        '</tr></tbody></table>'
                    );

                    $this->response->addHTML(
                        Template::get('structure/table_header')->render(
                            array(
                                'db_is_system_schema' => false,
                                'replication' => $GLOBALS['replication_info']['slave']['status']
                            )
                        )
                    );
                }

                $do = $ignored = false;
                $server_slave_status = $GLOBALS['replication_info']['slave']['status'];
                include_once 'libraries/replication.inc.php';

                if ($server_slave_status) {

                    $nbServSlaveDoDb = count($GLOBALS['replication_info']['slave']['Do_DB']);
                    $nbServSlaveIgnoreDb = count($GLOBALS['replication_info']['slave']['Ignore_DB']);
                    $searchDoDBInTruename = array_search($truename, $GLOBALS['replication_info']['slave']['Do_DB']);
                    $searchDoDBInDB = array_search($this->_db, $GLOBALS['replication_info']['slave']['Do_DB']);

                    $do = strlen($searchDoDBInTruename) > 0 || strlen($searchDoDBInDB) > 0 ||
                        ($nbServSlaveDoDb == 1 && $nbServSlaveIgnoreDb == 1) ||
                        $this->hasTable($GLOBALS['replication_info']['slave']['Wild_Do_Table'], $truename);

                    $searchDb = array_search($this->_db, $GLOBALS['replication_info']['slave']['Ignore_DB']);
                    $searchTable = array_search($truename, $GLOBALS['replication_info']['slave']['Ignore_Table']);
                    $ignored = (strlen($searchTable) > 0) || strlen($searchDb) > 0 ||
                        $this->hasTable($GLOBALS['replication_info']['slave']['Wild_Ignore_Table'], $truename);
                }

                // Handle favorite table list. ----START----
                $already_favorite = $this->checkFavoriteTable($current_table['TABLE_NAME']);

                if (isset($_REQUEST['remove_favorite'])) {
                    if ($already_favorite) {
                        // If already in favorite list, remove it.
                        $favorite_table = $_REQUEST['favorite_table'];
                        $fav_instance->remove($this->_db, $favorite_table);
                    }
                }

                if (isset($_REQUEST['add_favorite'])) {
                    if (!$already_favorite) {
                        // Otherwise add to favorite list.
                        $favorite_table = $_REQUEST['favorite_table'];
                        $fav_instance->add($this->_db, $favorite_table);
                    }
                } // Handle favorite table list. ----ENDS----

                $show_superscript = '';

                // there is a null value in the ENGINE
                // - when the table needs to be repaired, or
                // - when it's a view
                //  so ensure that we'll display "in use" below for a table
                //  that needs to be repaired
                $approx_rows = false;
                if (isset($current_table['TABLE_ROWS']) && ($current_table['ENGINE'] != null || $table_is_view)) {
                    // InnoDB table: we did not get an accurate row count
                    $approx_rows = !$table_is_view && $current_table['ENGINE'] == 'InnoDB' && !$current_table['COUNTED'];

                    // Drizzle views use FunctionEngine, and the only place where they are
                    // available are I_S and D_D schemas, where we do exact counting
                    if ($table_is_view && $current_table['TABLE_ROWS'] >= $GLOBALS['cfg']['MaxExactCountViews'] && $current_table['ENGINE'] != 'FunctionEngine') {
                        $approx_rows = true;
                        $show_superscript = PMA_Util::showHint(
                            PMA_sanitize(
                                sprintf(
                                    __('This view has at least this number of rows. Please refer to %sdocumentation%s.'),
                                    '[doc@cfg_MaxExactCountViews]', '[/doc]'
                                )
                            )
                        );
                    }
                }

                $this->response->addHTML(
                    Template::get('structure/structure_table_row')
                        ->render(
                            array(
                                'db' => $this->_db,
                                'curr' => $i,
                                'odd_row' => $odd_row,
                                'table_is_view' => $table_is_view,
                                'current_table' => $current_table,
                                'browse_table_label' => $browse_table_label,
                                'tracking_icon' => $tracking_icon,
                                'server_slave_status' => $GLOBALS['replication_info']['slave']['status'],
                                'browse_table' => $browse_table,
                                'tbl_url_query' => $tbl_url_query,
                                'search_table' => $search_table,
                                'db_is_system_schema' => $this->_db_is_system_schema,
                                'titles' => $titles,
                                'empty_table' => $empty_table,
                                'drop_query' => $drop_query,
                                'drop_message' => $drop_message,
                                'collation' => $collation,
                                'formatted_size' => $formatted_size,
                                'unit' => $unit,
                                'overhead' => $overhead,
                                'create_time' => isset ($create_time) ? $create_time : '',
                                'update_time' => isset ($update_time) ? $update_time : '',
                                'check_time' => isset ($check_time) ? $check_time : '',
                                'is_show_stats' => $this->_is_show_stats,
                                'ignored' => $ignored,
                                'do' => $do,
                                'colspan_for_structure' => $GLOBALS['colspan_for_structure'],
                                'approx_rows' => $approx_rows,
                                'show_superscript' => $show_superscript,
                                'already_favorite' => $this->checkFavoriteTable($current_table['TABLE_NAME'])
                            )
                        )
                );

                $odd_row = ! $odd_row;
                $overall_approx_rows = $overall_approx_rows || $approx_rows;
            } // end foreach

            // Show Summary
            $this->response->addHTML('</tbody>');
            $this->response->addHTML(
                Template::get('structure/body_for_table_summary')->render(
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
                Template::get('structure/check_all_tables')->render(
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

            // display again the table list navigator
            $this->response->addHTML(
                PMA_Util::getListNavigator(
                    $this->_total_num_tables, $this->_pos, $_url_params, 'db_structure.php',
                    'frame_content', $GLOBALS['cfg']['MaxTableList']
                )
            );

            $this->response->addHTML('</div><hr />');

            /**
             * Work on the database
             */
            /* DATABASE WORK */
            /* Printable view of a table */
            $this->response->addHTML(
                Template::get('structure/print_view_data_dictionary_link')->render(
                    array('url_query' => $this->_url_query)
                )
            );

            if (empty($db_is_system_schema)) {
                $this->response->addHTML(PMA_getHtmlForCreateTable($this->_db));
            }

        } elseif ($this->_type == 'table') { // Table structure
            PMA_PageSettings::showGroup('TableStructure');

            /**
             * Function implementations for this script
             */
            require_once 'libraries/check_user_privileges.lib.php';
            require_once 'libraries/index.lib.php';
            require_once 'libraries/sql.lib.php';
            require_once 'libraries/bookmark.lib.php';

            $this->response->getHeader()->getScripts()->addFiles(
                array(
                    'tbl_structure.js',
                    'indexes.js'
                )
            );

            /**
             * Handle column moving
             */
            if (isset($_REQUEST['move_columns'])
                && is_array($_REQUEST['move_columns'])
                && $this->response->isAjax()
            ) {
                $this->moveColumns();
                return;
            }

            /**
             * handle MySQL reserved words columns check
             */
            if (isset($_REQUEST['reserved_word_check'])) {
                if ($GLOBALS['cfg']['ReservedWordDisableWarning'] === false) {
                    $columns_names = $_REQUEST['field_name'];
                    $reserved_keywords_names = array();
                    foreach ($columns_names as $column) {
                        if (SqlParser\Context::isKeyword(trim($column), true)) {
                            $reserved_keywords_names[] = trim($column);
                        }
                    }
                    if (SqlParser\Context::isKeyword(trim($this->_table), true)) {
                        $reserved_keywords_names[] = trim($this->_table);
                    }
                    if (count($reserved_keywords_names) == 0) {
                        $this->response->isSuccess(false);
                    }
                    $this->response->addJSON(
                        'message', sprintf(
                            _ngettext(
                                'The name \'%s\' is a MySQL reserved keyword.',
                                'The names \'%s\' are MySQL reserved keywords.',
                                count($reserved_keywords_names)
                            ),
                            implode(',', $reserved_keywords_names)
                        )
                    );
                } else {
                    $this->response->isSuccess(false);
                }
                return;
            }
            /**
             * A click on Change has been made for one column
             */
            if (isset($_REQUEST['change_column'])) {
                $this->displayHtmlForColumnChange(null, 'tbl_structure.php');
                return;
            }

            /**
             * handle multiple field commands if required
             *
             * submit_mult_*_x comes from IE if <input type="img" ...> is used
             */
            $submit_mult = $this->getMultipleFieldCommandType();

            if (! empty($submit_mult)) {
                if (isset($_REQUEST['selected_fld'])) {
                    if ($submit_mult == 'browse') {
                        // browsing the table displaying only selected columns
                        $this->displayTableBrowseForSelectedColumns(
                            $GLOBALS['goto'], $GLOBALS['pmaThemeImage']
                        );
                    } else {
                        // handle multiple field commands
                        // handle confirmation of deleting multiple columns
                        $action = 'tbl_structure.php';
                        $GLOBALS['selected'] = $_REQUEST['selected_fld'];
                        list(
                            $what_ret, $query_type_ret, $is_unset_submit_mult, $mult_btn_ret,
                            $centralColsError
                        ) = $this->getDataForSubmitMult(
                            $submit_mult, $_REQUEST['selected_fld'], $action
                        );
                        //update the existing variables
                        // todo: refactor mult_submits.inc.php such as
                        // below globals are not needed anymore
                        if (isset($what_ret)) {
                            $GLOBALS['what'] = $what_ret;
                            global $what;
                        }
                        if (isset($query_type_ret)) {
                            $GLOBALS['query_type'] = $query_type_ret;
                            global $query_type;
                        }
                        if ($is_unset_submit_mult) {
                            unset($submit_mult);
                        }
                        if (isset($mult_btn_ret)) {
                            $GLOBALS['mult_btn'] = $mult_btn_ret;
                            global $mult_btn;
                        }
                        include 'libraries/mult_submits.inc.php';
                        /**
                         * if $submit_mult == 'change', execution will have stopped
                         * at this point
                         */
                        if (empty($message)) {
                            $message = PMA_Message::success();
                        }
                        $this->response->addHTML(PMA_Util::getMessage($message, $sql_query));
                    }
                } else {
                    $this->response->isSuccess(false);
                    $this->response->addJSON('message', __('No column selected.'));
                }
            }

            // display secondary level tabs if necessary
            $engine = $this->_table_obj->sGetStatusInfo('ENGINE');
            $this->response->addHTML(
                Template::get('structure/secondary_tabs')->render(
                    array(
                        'url_params' => array(
                            'db' => $this->_db,
                            'table' => $this->_table
                        ),
                        'engine' => $engine
                    )
                )
            );
            $this->response->addHTML('<div id="structure_content">');

            /**
             * Modifications have been submitted -> updates the table
             */
            if (isset($_REQUEST['do_save_data'])) {
                $regenerate = $this->updateColumns();
                if ($regenerate) {
                    // This happens when updating failed
                    // @todo: do something appropriate
                } else {
                    // continue to show the table's structure
                    unset($_REQUEST['selected']);
                }
            }

            /**
             * Adding indexes
             */
            if (isset($_REQUEST['add_key'])) {
                //todo: set some variables for sql.php include, to be eliminated
                //after refactoring sql.php
                $db = $this->_db;
                $table = $this->_table;
                $cfg = $GLOBALS['cfg'];
                $is_superuser = $GLOBALS['dbi']->isSuperuser();
                $pmaThemeImage = $GLOBALS['pmaThemeImage'];
                include 'sql.php';
                $GLOBALS['reload'] = true;
            }

            /**
             * Gets the relation settings
             */
            $cfgRelation = PMA_getRelationsParam();

            /**
             * Runs common work
             */
            // set db, table references, for require_once that follows
            // got to be eliminated in long run
            $db = &$this->_db;
            $table = &$this->_table;
            require_once 'libraries/tbl_common.inc.php';
            $this->_url_query = $url_query . '&amp;goto=tbl_structure.php&amp;back=tbl_structure.php';
            $url_params['goto'] = 'tbl_structure.php';
            $url_params['back'] = 'tbl_structure.php';

            /**
             * Gets tables information
             */
            require_once 'libraries/tbl_info.inc.php';

            require_once 'libraries/Index.class.php';

            // 2. Gets table keys and retains them
            // @todo should be: $server->db($db)->table($table)->primary()
            $primary = PMA_Index::getPrimary($this->_table, $this->_db);
            $columns_with_index = $this->dbi
                ->getTable($this->_db, $this->_table)
                ->getColumnsWithIndex(
                    PMA_Index::UNIQUE | PMA_Index::INDEX | PMA_Index::SPATIAL | PMA_Index::FULLTEXT
                );
            $columns_with_unique_index = $this->dbi
                ->getTable($this->_db, $this->_table)
                ->getColumnsWithIndex(PMA_Index::UNIQUE);

            // 3. Get fields
            $fields = (array) $this->dbi->getColumns($this->_db, $this->_table, null, true);

            // Get more complete field information
            // For now, this is done just for MySQL 4.1.2+ new TIMESTAMP options
            // but later, if the analyser returns more information, it
            // could be executed for any MySQL version and replace
            // the info given by SHOW FULL COLUMNS FROM.
            //
            // We also need this to correctly learn if a TIMESTAMP is NOT NULL, since
            // SHOW FULL COLUMNS or INFORMATION_SCHEMA incorrectly says NULL
            // and SHOW CREATE TABLE says NOT NULL (tested
            // in MySQL 4.0.25 and 5.0.21, http://bugs.mysql.com/20910).

            $show_create_table = $this->_table_obj->showCreate();
            $parser = new SqlParser\Parser($show_create_table);

            /**
             * @var CreateStatement $stmt
             */
            $stmt = $parser->statements[0];

            $create_table_fields = SqlParser\Utils\Table::getFields($stmt);

            //display table structure
            $this->response->addHTML(
                $this->displayStructure(
                    $cfgRelation, $columns_with_unique_index, $url_params, $primary,
                    $fields, $columns_with_index, $create_table_fields
                )
            );

            $this->response->addHTML('</div>');
        }
    }

    /**
     * Add or remove favorite tables
     *
     * @return void
     */
    protected function addRemoveFavoriteTables()
    {
        $fav_instance = PMA_RecentFavoriteTable::getInstance('favorite');
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
        $titles = PMA_Util::buildActionTitles();
        $favorite_table = $_REQUEST['favorite_table'];
        $already_favorite = $this->checkFavoriteTable($favorite_table);

        if (isset($_REQUEST['remove_favorite'])) {
            if ($already_favorite) {
                // If already in favorite list, remove it.
                $fav_instance->remove($this->_db, $favorite_table);
            }
        } elseif (isset($_REQUEST['add_favorite'])) {
            if (!$already_favorite) {
                if (count($fav_instance->getTables()) == $GLOBALS['cfg']['NumFavoriteTables']) {
                    $changes = false;
                } else {
                    // Otherwise add to favorite list.
                    $fav_instance->add($this->_db, $favorite_table);
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
                'anchor' => Template::get('structure/favorite_anchor')
                    ->render(
                        array(
                            'db' => $this->_db,
                            'current_table' => array(
                                'TABLE_NAME' => $favorite_table
                            ),
                            'titles' => $titles
                        )
                    )
            )
        );
    }

    /**
     * Synchronize favorite tables
     *
     *
     * @param PMA_RecentFavoriteTable $fav_instance    Instance of this class
     * @param string                  $user            The user hash
     * @param array                   $favorite_tables Existing favorites
     *
     * @return void
     */
    protected function synchronizeFavoriteTables($fav_instance, $user, $favorite_tables)
    {
        $fav_instance_tables = $fav_instance->getTables();

        if (empty($fav_instance_tables)
            && isset($favorite_tables[$user])
        ) {
            foreach ($favorite_tables[$user] as $key => $value) {
                $fav_instance->add($value['db'], $value['table']);
            }
        }
        $favorite_tables[$user] = $fav_instance->getTables();

        $$this->response->addJSON(
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
        foreach ($_SESSION['tmpval']['favorite_tables'][$GLOBALS['server']] as $value) {
            if ($value['db'] == $this->_db && $value['table'] == $current_table) {
                return true;
            }
        }
        return false;
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
                ->getTable($this->_db, $_REQUEST['table'])
                ->getRealRowCountTable();
            // Format the number.
            $real_row_count = PMA_Util::formatNumber($real_row_count, 0);
            $ajax_response->addJSON('real_row_count', $real_row_count);
            return;
        }

        // Array to store the results.
        $real_row_count_all = array();
        // Iterate over each table and fetch real row count.
        foreach ($GLOBALS['tables'] as $table) {
            $row_count = $this->dbi
                ->getTable($this->_db, $table['TABLE_NAME'])
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
     * Moves columns in the table's structure based on $_REQUEST
     *
     * @return void
     */
    protected function moveColumns()
    {
        $this->dbi->selectDb($this->_db);

        /*
         * load the definitions for all columns
         */
        $columns = $this->dbi->getColumnsFull($this->_db, $this->_table);
        $column_names = array_keys($columns);
        $changes = array();

        // move columns from first to last
        for ($i = 0, $l = count($_REQUEST['move_columns']); $i < $l; $i++) {
            $column = $_REQUEST['move_columns'][$i];
            // is this column already correctly placed?
            if ($column_names[$i] == $column) {
                continue;
            }

            // it is not, let's move it to index $i
            $data = $columns[$column];
            $extracted_columnspec = PMA_Util::extractColumnSpec($data['Type']);
            if (isset($data['Extra']) && $data['Extra'] == 'on update CURRENT_TIMESTAMP') {
                $extracted_columnspec['attribute'] = $data['Extra'];
                unset($data['Extra']);
            }
            $current_timestamp = ($data['Type'] == 'timestamp' || $data['Type'] == 'datetime') &&
                $data['Default'] == 'CURRENT_TIMESTAMP';

            if ($data['Null'] === 'YES' && $data['Default'] === null) {
                $default_type = 'NULL';
            } elseif ($current_timestamp) {
                $default_type = 'CURRENT_TIMESTAMP';
            } elseif ($data['Default'] === null) {
                $default_type = 'NONE';
            } else {
                $default_type = 'USER_DEFINED';
            }

            $virtual = array(
                'VIRTUAL', 'PERSISTENT', 'VIRTUAL GENERATED', 'STORED GENERATED'
            );
            $data['Virtuality'] = '';
            $data['Expression'] = '';
            if (isset($data['Extra']) && in_array($data['Extra'], $virtual)) {
                $data['Virtuality'] = str_replace(' GENERATED', '', $data['Extra']);
                $expressions = $this->_table->getColumnGenerationExpression($column);
                $data['Expression'] = $expressions[$column];
            }

            $changes[] = 'CHANGE ' . PMA_Table::generateAlter(
                $column,
                $column,
                /*overload*/mb_strtoupper($extracted_columnspec['type']),
                $extracted_columnspec['spec_in_brackets'],
                $extracted_columnspec['attribute'],
                isset($data['Collation']) ? $data['Collation'] : '',
                $data['Null'] === 'YES' ? 'NULL' : 'NOT NULL',
                $default_type,
                $current_timestamp ? '' : $data['Default'],
                isset($data['Extra']) && $data['Extra'] !== '' ? $data['Extra'] : false,
                isset($data['COLUMN_COMMENT']) && $data['COLUMN_COMMENT'] !== ''
                ? $data['COLUMN_COMMENT'] : false,
                $data['Virtuality'],
                $data['Expression'],
                $i === 0 ? '-first' : $column_names[$i - 1]
            );
            // update current column_names array, first delete old position
            for ($j = 0, $ll = count($column_names); $j < $ll; $j++) {
                if ($column_names[$j] == $column) {
                    unset($column_names[$j]);
                }
            }
            // insert moved column
            array_splice($column_names, $i, 0, $column);
        }
        if (empty($changes)) { // should never happen
            $this->response->isSuccess(false);
            return;
        }
        // move columns
        $this->dbi->tryQuery(
            sprintf(
                'ALTER TABLE %s %s',
                PMA_Util::backquote($this->_table),
                implode(', ', $changes)
            )
        );
        $tmp_error = $this->dbi->getError();
        if ($tmp_error) {
            $this->response->isSuccess(false);
            $this->response->addJSON('message', PMA_Message::error($tmp_error));
        } else {
            $message = PMA_Message::success(
                __('The columns have been moved successfully.')
            );
            $this->response->addJSON('message', $message);
            $this->response->addJSON('columns', $column_names);
        }
    }

    /**
     * Displays HTML for changing one or more columns
     *
     * @param array  $selected the selected columns
     * @param string $action   target script to call
     *
     * @return boolean $regenerate true if error occurred
     *
     */
    protected function displayHtmlForColumnChange($selected, $action)
    {
        // $selected comes from mult_submits.inc.php
        if (empty($selected)) {
            $selected[] = $_REQUEST['field'];
            $selected_cnt = 1;
        } else { // from a multiple submit
            $selected_cnt = count($selected);
        }

        /**
         * @todo optimize in case of multiple fields to modify
         */
        $fields_meta = array();
        for ($i = 0; $i < $selected_cnt; $i++) {
            $fields_meta[] = $this->dbi->getColumns(
                $this->_db, $this->_table, $selected[$i], true
            );
        }
        $num_fields = count($fields_meta);
        // set these globals because tbl_columns_definition_form.inc.php
        // verifies them
        // @todo: refactor tbl_columns_definition_form.inc.php so that it uses
        // protected function params
        $GLOBALS['action'] = $action;
        $GLOBALS['num_fields'] = $num_fields;

        /**
         * Form for changing properties.
         */
        include_once 'libraries/check_user_privileges.lib.php';
        include 'libraries/tbl_columns_definition_form.inc.php';
    }

    /**
     * Function to get the type of command for multiple field handling
     *
     * @return string
     */
    protected function getMultipleFieldCommandType()
    {
        $types = array(
            'change', 'drop', 'primary',
            'index', 'unique', 'spatial',
            'fulltext', 'browse'
        );

        foreach ($types as $type) {
            if (isset($_REQUEST['submit_mult_' . $type . '_x'])) {
                return $type;
            }
        }

        if (isset($_REQUEST['submit_mult'])) {
            return $_REQUEST['submit_mult'];
        } elseif (isset($_REQUEST['mult_btn']) && $_REQUEST['mult_btn'] == __('Yes')) {
            if (isset($_REQUEST['selected'])) {
                $_REQUEST['selected_fld'] = $_REQUEST['selected'];
            }
            return 'row_delete';
        }

        return null;
    }

    /**
     * Function to display table browse for selected columns
     *
     * @param string $goto          goto page url
     * @param string $pmaThemeImage URI of the pma theme image
     *
     * @return void
     */
    protected function displayTableBrowseForSelectedColumns($goto, $pmaThemeImage)
    {
        $GLOBALS['active_page'] = 'sql.php';
        $fields = array();
        foreach ($_REQUEST['selected_fld'] as $sval) {
            $fields[] = PMA_Util::backquote($sval);
        }
        $sql_query = sprintf(
            'SELECT %s FROM %s.%s',
            implode(', ', $fields),
            PMA_Util::backquote($this->_db),
            PMA_Util::backquote($this->_table)
        );

        // Parse and analyze the query
        // @todo Refactor parse_analyze.inc to protected function
        $db = &$this->_db;
        include_once 'libraries/parse_analyze.inc.php';

        include_once 'libraries/sql.lib.php';

        $this->response->addHTML(
            PMA_executeQueryAndGetQueryResponse(
                isset($analyzed_sql_results) ? $analyzed_sql_results : '',
                false, // is_gotofile
                $this->_db, // db
                $this->_table, // table
                null, // find_real_end
                null, // sql_query_for_bookmark
                null, // extra_data
                null, // message_to_show
                null, // message
                null, // sql_data
                $goto, // goto
                $pmaThemeImage, // pmaThemeImage
                null, // disp_query
                null, // disp_message
                null, // query_type
                $sql_query, // sql_query
                null, // selectedTables
                null // complete_query
            )
        );
    }

    /**
     * Update the table's structure based on $_REQUEST
     *
     * @return boolean $regenerate              true if error occurred
     *
     */
    protected function updateColumns()
    {
        $err_url = 'tbl_structure.php' . PMA_URL_getCommon(
            array(
                'db' => $this->_db, 'table' => $this->_table
            )
        );
        $regenerate = false;
        $field_cnt = count($_REQUEST['field_name']);
        $changes = array();
        $adjust_privileges = array();

        for ($i = 0; $i < $field_cnt; $i++) {
            if ($this->columnNeedsAlterTable($i)) {
                $changes[] = 'CHANGE ' . PMA_Table::generateAlter(
                    Util\get($_REQUEST, "field_orig.${i}", ''),
                    $_REQUEST['field_name'][$i],
                    $_REQUEST['field_type'][$i],
                    $_REQUEST['field_length'][$i],
                    $_REQUEST['field_attribute'][$i],
                    Util\get($_REQUEST, "field_collation.${i}", ''),
                    Util\get($_REQUEST, "field_null.${i}", 'NOT NULL'),
                    $_REQUEST['field_default_type'][$i],
                    $_REQUEST['field_default_value'][$i],
                    Util\get($_REQUEST, "field_extra.${i}", false),
                    Util\get($_REQUEST, "field_comments.${i}", ''),
                    Util\get($_REQUEST, "field_virtuality.${i}", ''),
                    Util\get($_REQUEST, "field_expression.${i}", ''),
                    Util\get($_REQUEST, "field_move_to.${i}", '')
                );

                // find the remembered sort expression
                $sorted_col = $this->_table_obj->getUiProp(PMA_Table::PROP_SORTED_COLUMN);
                // if the old column name is part of the remembered sort expression
                if (/*overload*/mb_strpos(
                    $sorted_col,
                    PMA_Util::backquote($_REQUEST['field_orig'][$i])
                ) !== false) {
                    // delete the whole remembered sort expression
                    $this->_table_obj->removeUiProp(PMA_Table::PROP_SORTED_COLUMN);
                }

                if (isset($_REQUEST['field_adjust_privileges'][$i])
                    && ! empty($_REQUEST['field_adjust_privileges'][$i])
                    && $_REQUEST['field_orig'][$i] != $_REQUEST['field_name'][$i]
                ) {
                    $adjust_privileges[$_REQUEST['field_orig'][$i]]
                        = $_REQUEST['field_name'][$i];
                }
            }
        } // end for

        if (count($changes) > 0 || isset($_REQUEST['preview_sql'])) {
            // Builds the primary keys statements and updates the table
            $key_query = '';
            /**
             * this is a little bit more complex
             *
             * @todo if someone selects A_I when altering a column we need to check:
             *  - no other column with A_I
             *  - the column has an index, if not create one
             *
             */

            // To allow replication, we first select the db to use
            // and then run queries on this db.
            if (!$this->dbi->selectDb($this->_db)) {
                PMA_Util::mysqlDie(
                    $this->dbi->getError(),
                    'USE ' . PMA_Util::backquote($this->_db) . ';',
                    false,
                    $err_url
                );
            }
            $sql_query = 'ALTER TABLE ' . PMA_Util::backquote($this->_table) . ' ';
            $sql_query .= implode(', ', $changes) . $key_query;
            $sql_query .= ';';

            // If there is a request for SQL previewing.
            if (isset($_REQUEST['preview_sql'])) {
                PMA_previewSQL(count($changes) > 0 ? $sql_query : '');
            }

            $changedToBlob = array();
            // While changing the Column Collation
            // First change to BLOB
            for ($i = 0; $i < $field_cnt; $i++ ) {
                if (isset($_REQUEST['field_collation'][$i])
                    && isset($_REQUEST['field_collation_orig'][$i])
                    && $_REQUEST['field_collation'][$i] !== $_REQUEST['field_collation_orig'][$i]
                ) {
                    $secondary_query = 'ALTER TABLE ' . PMA_Util::backquote($this->_table)
                        . ' CHANGE ' . PMA_Util::backquote($_REQUEST['field_orig'][$i])
                        . ' ' . PMA_Util::backquote($_REQUEST['field_orig'][$i])
                        . ' BLOB;';
                    $this->dbi->query($secondary_query);
                    $changedToBlob[$i] = true;
                } else {
                    $changedToBlob[$i] = false;
                }
            }

            // Then make the requested changes
            $result = $this->dbi->tryQuery($sql_query);

            if ($result !== false) {
                $changed_privileges = $this->adjustColumnPrivileges(
                    $this->_db, $this->_table, $adjust_privileges
                );

                if ($changed_privileges) {
                    $message = PMA_Message::success(
                        __(
                            'Table %1$s has been altered successfully. Privileges ' .
                            'have been adjusted.'
                        )
                    );
                } else {
                    $message = PMA_Message::success(
                        __('Table %1$s has been altered successfully.')
                    );
                }
                $message->addParam($this->_table);

                $this->response->addHTML(
                    PMA_Util::getMessage($message, $sql_query, 'success')
                );
            } else {
                // An error happened while inserting/updating a table definition

                // Save the Original Error
                $orig_error = $this->dbi->getError();
                $changes_revert = array();

                // Change back to Orignal Collation and data type
                for ($i = 0; $i < $field_cnt; $i++) {
                    if ($changedToBlob[$i]) {
                        $changes_revert[] = 'CHANGE ' . PMA_Table::generateAlter(
                            Util\get($_REQUEST, "field_orig.${i}", ''),
                            $_REQUEST['field_name'][$i],
                            $_REQUEST['field_type_orig'][$i],
                            $_REQUEST['field_length_orig'][$i],
                            $_REQUEST['field_attribute_orig'][$i],
                            Util\get($_REQUEST, "field_collation_orig.${i}", ''),
                            Util\get($_REQUEST, "field_null_orig.${i}", 'NOT NULL'),
                            $_REQUEST['field_default_type_orig'][$i],
                            $_REQUEST['field_default_value_orig'][$i],
                            Util\get($_REQUEST, "field_extra_orig.${i}", false),
                            Util\get($_REQUEST, "field_comments_orig.${i}", ''),
                            Util\get($_REQUEST, "field_move_to_orig.${i}", '')
                        );
                    }
                }

                $revert_query = 'ALTER TABLE ' . PMA_Util::backquote($this->_table) . ' ';
                $revert_query .= implode(', ', $changes_revert) . '';
                $revert_query .= ';';

                // Column reverted back to original
                $this->dbi->query($revert_query);

                $this->response->isSuccess(false);
                $this->response->addJSON(
                    'message',
                    PMA_Message::rawError(
                        __('Query error') . ':<br />' . $orig_error
                    )
                );
                $regenerate = true;
            }
        }

        // update field names in relation
        if (isset($_REQUEST['field_orig']) && is_array($_REQUEST['field_orig'])) {
            foreach ($_REQUEST['field_orig'] as $fieldindex => $fieldcontent) {
                if ($_REQUEST['field_name'][$fieldindex] != $fieldcontent) {
                    PMA_REL_renameField(
                        $this->_db, $this->_table, $fieldcontent,
                        $_REQUEST['field_name'][$fieldindex]
                    );
                }
            }
        }

        // update mime types
        if (isset($_REQUEST['field_mimetype']) && is_array($_REQUEST['field_mimetype']) && $GLOBALS['cfg']['BrowseMIME']) {
            foreach ($_REQUEST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_REQUEST['field_name'][$fieldindex])
                    && /*overload*/mb_strlen(
                        $_REQUEST['field_name'][$fieldindex]
                    )
                ) {
                    PMA_setMIME(
                        $this->_db, $this->_table, $_REQUEST['field_name'][$fieldindex],
                        $mimetype,
                        $_REQUEST['field_transformation'][$fieldindex],
                        $_REQUEST['field_transformation_options'][$fieldindex],
                        $_REQUEST['field_input_transformation'][$fieldindex],
                        $_REQUEST['field_input_transformation_options'][$fieldindex]
                    );
                }
            }
        }
        return $regenerate;
    }

    /**
     * Adjusts the Privileges for all the columns whose names have changed
     *
     * @param array  $adjust_privileges assoc array of old col names mapped to new cols
     *
     * @return boolean $changed  boolean whether atleast one column privileges adjusted
     */
    protected function adjustColumnPrivileges($adjust_privileges)
    {
        $changed = false;

        if ((!defined('PMA_DRIZZLE') || !PMA_DRIZZLE)
            && Util\get($GLOBALS, 'col_priv', false)
            && Util\get($GLOBALS, 'flush_priv', false)
        ) {
            $this->dbi->selectDb('mysql');

            // For Column specific privileges
            foreach ($adjust_privileges as $oldCol => $newCol) {

                $this->dbi->query(
                    sprintf(
                        'UPDATE %s SET Column_name = "%s"
                        WHERE Db = "%s"
                        AND Table_name = "%s"
                        AND Column_name = "%s";',
                        PMA_Util::backquote('columns_priv'),
                        $newCol, $this->_db, $this->_table, $oldCol
                    )
                );

                // i.e. if atleast one column privileges adjusted
                $changed = true;
            }

            if ($changed) {
                // Finally FLUSH the new privileges
                $this->dbi->query("FLUSH PRIVILEGES;");
            }
        }

        return $changed;
    }

    /**
     * Verifies if some elements of a column have changed
     *
     * @param integer $i column index in the request
     *
     * @return boolean $alterTableNeeded true if we need to generate ALTER TABLE
     *
     */
    protected function columnNeedsAlterTable($i)
    {
        // these two fields are checkboxes so might not be part of the
        // request; therefore we define them to avoid notices below
        if (! isset($_REQUEST['field_null'][$i])) {
            $_REQUEST['field_null'][$i] = 'NO';
        }
        if (! isset($_REQUEST['field_extra'][$i])) {
            $_REQUEST['field_extra'][$i] = '';
        }

        // field_name does not follow the convention (corresponds to field_orig)
        $fields = array(
            'field_attribute', 'field_collation', 'field_comments',
            'field_default_value', 'field_default_type', 'field_extra',
            'field_length', 'field_name', 'field_null', 'field_type'
        );
        foreach ($fields as $field) {
            if ($_REQUEST[$field][$i] != $_REQUEST[$field . '_orig'][$i]) {
                return true;
            }
        }
        return !empty($_REQUEST['field_move_to'][$i]);
    }

    /**
     * Find table with truename
     *
     * @param array $db
     * @param bool $truename
     * @return bool
     */
    protected function hasTable($db, $truename)
    {
        foreach ($db as $db_table) {
            if ($this->_db == PMA_extractDbOrTable($db_table)
                && preg_match("@^" . /*overload*/ mb_substr(PMA_extractDbOrTable($db_table, 'table'), 0, -1) . "@", $truename)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the value set for ENGINE table,
     *
     * @param array $current_table current table
     * @param boolean $db_is_system_schema whether db is information schema or not
     * @param boolean $is_show_stats whether stats show or not
     * @param double $sum_size total table size
     * @param double $overhead_size overhead size
     * @return array
     * @internal param bool $table_is_view whether table is view or not
     */
    protected function getStuffForEngineTypeTable(
        $current_table, $db_is_system_schema, $is_show_stats, $sum_size,
        $overhead_size
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
            list($current_table, $formatted_size, $unit, $formatted_overhead,
                $overhead_unit, $overhead_size, $sum_size)
                = $this->getValuesForAriaTable(
                    $db_is_system_schema, $current_table, $is_show_stats,
                    $sum_size, $overhead_size, $formatted_size, $unit,
                    $formatted_overhead, $overhead_unit
                );
            break;
        case 'InnoDB' :
        case 'PBMS' :
            // InnoDB table: Row count is not accurate but data and index sizes are.
            // PBMS table in Drizzle: TABLE_ROWS is taken from table cache,
            // so it may be unavailable
            list($current_table, $formatted_size, $unit, $sum_size)
                = $this->getValuesForInnodbTable($current_table, $is_show_stats, $sum_size);
            //$display_rows                   =  ' - ';
            break;
        // Mysql 5.0.x (and lower) uses MRG_MyISAM
        // and MySQL 5.1.x (and higher) uses MRG_MYISAM
        // Both are aliases for MERGE
        case 'MRG_MyISAM' :
        case 'MRG_MYISAM' :
        case 'MERGE' :
        case 'BerkeleyDB' :
            // Merge or BerkleyDB table: Only row count is accurate.
            if ($is_show_stats) {
                $formatted_size =  ' - ';
                $unit          =  '';
            }
            break;
        // for a view, the ENGINE is sometimes reported as null,
        // or on some servers it's reported as "SYSTEM VIEW"
        case null :
        case 'SYSTEM VIEW' :
        case 'FunctionEngine' :
            // possibly a view, do nothing
            break;
        default :
            // Unknown table type.
            if ($is_show_stats) {
                $formatted_size =  __('unknown');
                $unit          =  '';
            }
        } // end switch

        if ($current_table['TABLE_TYPE'] == 'VIEW' || $current_table['TABLE_TYPE'] == 'SYSTEM VIEW') {
            // countRecords() takes care of $cfg['MaxExactCountViews']
            $current_table['TABLE_ROWS'] = $this->dbi
                ->getTable($this->_db, $current_table['TABLE_NAME'])
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
     * @param boolean $db_is_system_schema whether db is information schema or not
     * @param array   $current_table       current table
     * @param boolean $is_show_stats       whether stats show or not
     * @param double  $sum_size            sum size
     * @param double  $overhead_size       overhead size
     * @param number  $formatted_size      formatted size
     * @param string  $unit                unit
     * @param number  $formatted_overhead  overhead formatted
     * @param string  $overhead_unit       overhead unit
     *
     * @return array
     */
    protected function getValuesForAriaTable(
        $db_is_system_schema, $current_table, $is_show_stats,
        $sum_size, $overhead_size, $formatted_size, $unit,
        $formatted_overhead, $overhead_unit
    ) {
        if ($db_is_system_schema) {
            $current_table['Rows'] = $this->dbi
                ->getTable($this->_db, $current_table['Name'])
                ->countRecords();
        }

        if ($is_show_stats) {
            $tblsize = doubleval($current_table['Data_length'])
                + doubleval($current_table['Index_length']);
            $sum_size += $tblsize;
            list($formatted_size, $unit) = PMA_Util::formatByteDown(
                $tblsize, 3, ($tblsize > 0) ? 1 : 0
            );
            if (isset($current_table['Data_free'])
                && $current_table['Data_free'] > 0
            ) {
                // here, the value 4 as the second parameter
                // would transform 6.1MiB into 6,224.6KiB
                list($formatted_overhead, $overhead_unit)
                    = PMA_Util::formatByteDown(
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
     * @param boolean $is_show_stats whether stats show or not
     * @param double  $sum_size      sum size
     *
     * @return array
     */
    protected function getValuesForInnodbTable($current_table, $is_show_stats, $sum_size)
    {
        $formatted_size = $unit = '';

        if (($current_table['ENGINE'] == 'InnoDB'
                && $current_table['TABLE_ROWS'] < $GLOBALS['cfg']['MaxExactCount'])
            || !isset($current_table['TABLE_ROWS'])
        ) {
            $current_table['COUNTED'] = true;
            $current_table['TABLE_ROWS'] = $this->dbi
                ->getTable($this->_db, $current_table['TABLE_NAME'])
                ->countRecords(true);
        } else {
            $current_table['COUNTED'] = false;
        }

        // Drizzle doesn't provide data and index length, check for null
        if ($is_show_stats && $current_table['Data_length'] !== null) {
            $tblsize =  $current_table['Data_length'] + $current_table['Index_length'];
            $sum_size += $tblsize;
            list($formatted_size, $unit) = PMA_Util::formatByteDown(
                $tblsize, 3, (($tblsize > 0) ? 1 : 0)
            );
        }

        return array($current_table, $formatted_size, $unit, $sum_size);
    }

    /**
     * Displays the table structure ('show table' works correct since 3.23.03)
     *
     * @param $cfgRelation
     * @param $columns_with_unique_index
     * @param $url_params
     * @param $primary_index
     * @param $fields
     * @param $columns_with_index
     * @param $create_table_fields
     *
     * @return string
     */
    protected function displayStructure(
        $cfgRelation, $columns_with_unique_index, $url_params, $primary_index,
        $fields, $columns_with_index, $create_table_fields
    ) {
        /* TABLE INFORMATION */
        $HideStructureActions = '';
        if ($GLOBALS['cfg']['HideStructureActions'] === true) {
            $HideStructureActions .= ' HideStructureActions';
        }

        // prepare comments
        $comments_map = array();
        $mime_map = array();

        if ($GLOBALS['cfg']['ShowPropertyComments']) {
            include_once 'libraries/transformations.lib.php';
            $comments_map = PMA_getComments($this->_db, $this->_table);
            if ($cfgRelation['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {
                $mime_map = PMA_getMIME($this->_db, $this->_table, true);
            }
        }
        require_once 'libraries/central_columns.lib.php';
        $central_list = PMA_getCentralColumnsFromTable($this->_db, $this->_table);
        $columns_list = array();

        $titles = array(
            'Change' => PMA_Util::getIcon('b_edit.png', __('Change')),
            'Drop' => PMA_Util::getIcon('b_drop.png', __('Drop')),
            'NoDrop' => PMA_Util::getIcon('b_drop.png', __('Drop')),
            'Primary' => PMA_Util::getIcon('b_primary.png', __('Primary')),
            'Index' => PMA_Util::getIcon('b_index.png', __('Index')),
            'Unique' => PMA_Util::getIcon('b_unique.png', __('Unique')),
            'Spatial' => PMA_Util::getIcon('b_spatial.png', __('Spatial')),
            'IdxFulltext' => PMA_Util::getIcon('b_ftext.png', __('Fulltext')),
            'NoPrimary' => PMA_Util::getIcon('bd_primary.png', __('Primary')),
            'NoIndex' => PMA_Util::getIcon('bd_index.png', __('Index')),
            'NoUnique' => PMA_Util::getIcon('bd_unique.png', __('Unique')),
            'NoSpatial' => PMA_Util::getIcon('bd_spatial.png', __('Spatial')),
            'NoIdxFulltext' => PMA_Util::getIcon('bd_ftext.png', __('Fulltext')),
            'DistinctValues' => PMA_Util::getIcon('b_browse.png', __('Distinct values'))
        );

        /**
         * Work on the table
         */
        if ($this->_tbl_is_view) {
            $item = $this->dbi->fetchSingleRow(
                sprintf(
                    "SELECT `VIEW_DEFINITION`, `CHECK_OPTION`, `DEFINER`, `SECURITY_TYPE`
                    FROM `INFORMATION_SCHEMA`.`VIEWS`
                    WHERE TABLE_SCHEMA='%s'
                    AND TABLE_NAME='%s';",
                    PMA_Util::sqlAddSlashes($this->_db),
                    PMA_Util::sqlAddSlashes($this->_table)
                )
            );

            $createView = $this->dbi->getTable($this->_db, $this->_table)->showCreate();
            // get algorithm from $createView of the form CREATE ALGORITHM=<ALGORITHM> DE...
            $parts = explode(" ", substr($createView, 17));
            $item['ALGORITHM'] = $parts[0];

            $view = array(
                'operation' => 'alter',
                'definer' => $item['DEFINER'],
                'sql_security' => $item['SECURITY_TYPE'],
                'name' => $this->_table,
                'as' => $item['VIEW_DEFINITION'],
                'with' => $item['CHECK_OPTION'],
                'algorithm' => $item['ALGORITHM'],
            );

            $edit_view_url = 'view_create.php'
                . PMA_URL_getCommon($url_params) . '&amp;'
                . implode(
                    '&amp;',
                    array_map(
                        function ($key, $val) {
                            return 'view[' . urlencode($key) . ']=' . urlencode($val);
                        },
                        array_keys($view), $view
                    )
                );
        }

        /**
         * Displays indexes
         */
        if (! $this->_tbl_is_view
            && ! $this->_db_is_system_schema
            && 'ARCHIVE' !=  $this->_tbl_storage_engine
        ) {
            //return the list of index
            $this->response->addJSON(
                'indexes_list',
                PMA_Index::getHtmlForIndexes($this->_table, $this->_db)
            );
        }

        /**
         * Displays Space usage and row statistics
         */
        // BEGIN - Calc Table Space
        // Get valid statistics whatever is the table type
        if ($GLOBALS['cfg']['ShowStats']) {
            //get table stats in HTML format
            $tablestats = $this->getTableStats(
                $this->_showtable, $this->_table_info_num_rows, $this->_tbl_is_view,
                $this->_db_is_system_schema, $this->_tbl_storage_engine,
                $this->_url_query, $this->_tbl_collation
            );
            //returning the response in JSON format to be used by Ajax
            $this->response->addJSON('tableStat', $tablestats);
        }
        // END - Calc Table Space

        return Template::get('structure/display_structure')->render(
            array(
                'HideStructureActions' => $HideStructureActions,
                'db' => $this->_db,
                'table' => $this->_table,
                'db_is_system_schema' => $this->_db_is_system_schema,
                'tbl_is_view' => $this->_tbl_is_view,
                'mime_map' => $mime_map,
                'url_query' => $this->_url_query,
                'titles' => $titles,
                'tbl_storage_engine' => $this->_tbl_storage_engine,
                'primary' => $primary_index,
                'columns_with_unique_index' => $columns_with_unique_index,
                'edit_view_url' => isset($edit_view_url) ? $edit_view_url : null,
                'columns_list' => $columns_list,
                'tablestats' => isset($tablestats) ? $tablestats : null,
                'fields' => $fields,
                'columns_with_index' => $columns_with_index,
                'central_list' => $central_list,
                'create_table_fields' => $create_table_fields
            )
        );
    }

    /**
     * Get HTML snippet for display table statistics
     *
     * @param array   $showtable           full table status info
     * @param integer $table_info_num_rows table info number of rows
     * @param boolean $tbl_is_view         whether table is view or not
     * @param boolean $db_is_system_schema whether db is information schema or not
     * @param string  $tbl_storage_engine  table storage engine
     * @param string  $url_query           url query
     * @param string  $tbl_collation       table collation
     *
     * @return string $html_output
     */
    protected function getTableStats(
        $showtable, $table_info_num_rows, $tbl_is_view,
        $db_is_system_schema, $tbl_storage_engine,
        $url_query, $tbl_collation
    ) {
        if (empty($showtable)) {
            $showtable = $this->dbi->getTable(
                $this->_db, $this->_table
            )->sGetStatusInfo(null, true);
        }

        if (empty($showtable['Data_length'])) {
            $showtable['Data_length'] = 0;
        }
        if (empty($showtable['Index_length'])) {
            $showtable['Index_length'] = 0;
        }

        $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');

        $mergetable = $this->_table_obj->isMerge();

        // this is to display for example 261.2 MiB instead of 268k KiB
        $max_digits = 3;
        $decimals = 1;
        list($data_size, $data_unit) = PMA_Util::formatByteDown(
            $showtable['Data_length'], $max_digits, $decimals
        );
        if ($mergetable == false) {
            list($index_size, $index_unit) = PMA_Util::formatByteDown(
                $showtable['Index_length'], $max_digits, $decimals
            );
        }
        // InnoDB returns a huge value in Data_free, do not use it
        if (! $is_innodb && isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
            list($free_size, $free_unit) = PMA_Util::formatByteDown(
                $showtable['Data_free'], $max_digits, $decimals
            );
            list($effect_size, $effect_unit) = PMA_Util::formatByteDown(
                $showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free'],
                $max_digits, $decimals
            );
        } else {
            list($effect_size, $effect_unit) = PMA_Util::formatByteDown(
                $showtable['Data_length'] + $showtable['Index_length'],
                $max_digits, $decimals
            );
        }
        list($tot_size, $tot_unit) = PMA_Util::formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length'],
            $max_digits, $decimals
        );
        if ($table_info_num_rows > 0) {
            list($avg_size, $avg_unit) = PMA_Util::formatByteDown(
                ($showtable['Data_length'] + $showtable['Index_length'])
                / $showtable['Rows'],
                6, 1
            );
        } else {
            $avg_size = $avg_unit = '';
        }

        return Template::get('structure/display_table_stats')->render(
            array(
                'showtable' => $showtable,
                'table_info_num_rows' => $table_info_num_rows,
                'tbl_is_view' => $tbl_is_view,
                'db_is_system_schema' => $db_is_system_schema,
                'tbl_storage_engine' => $tbl_storage_engine,
                'url_query' => $url_query,
                'tbl_collation' => $tbl_collation,
                'is_innodb' => $is_innodb,
                'mergetable' => $mergetable,
                'avg_size' => isset($avg_size) ? $avg_size : null,
                'avg_unit' => isset($avg_unit) ? $avg_unit : null,
                'data_size' => $data_size,
                'data_unit' => $data_unit,
                'index_size' => isset($index_size) ? $index_size : null,
                'index_unit' => isset($index_unit) ? $index_unit : null,
                'free_size' => isset($free_size) ? $free_size : null,
                'free_unit' => isset($free_unit) ? $free_unit : null,
                'effect_size' => $effect_size,
                'effect_unit' => $effect_unit,
                'tot_size' => $tot_size,
                'tot_unit' => $tot_unit
            )
        );
    }

    /**
     * Gets table primary key
     *
     * @return string
     */
    protected function getKeyForTablePrimary()
    {
        $this->dbi->selectDb($this->_db);
        $result = $this->dbi->query(
            'SHOW KEYS FROM ' . PMA_Util::backquote($this->_table) . ';'
        );
        $primary = '';
        while ($row = $this->dbi->fetchAssoc($result)) {
            // Backups the list of primary keys
            if ($row['Key_name'] == 'PRIMARY') {
                $primary .= $row['Column_name'] . ', ';
            }
        } // end while
        $this->dbi->freeResult($result);

        return $primary;
    }

    /**
     * Get List of information for Submit Mult
     *
     * @param string $submit_mult mult_submit type
     * @param array  $selected    the selected columns
     * @param string $action      action type
     *
     * @return array
     */
    protected function getDataForSubmitMult($submit_mult, $selected, $action)
    {
        $what = null;
        $query_type = null;
        $is_unset_submit_mult = false;
        $mult_btn = null;
        $centralColsError = null;
        switch ($submit_mult) {
        case 'drop':
            $what     = 'drop_fld';
            break;
        case 'primary':
            // Gets table primary key
            $primary = $this->getKeyForTablePrimary();
            if (empty($primary)) {
                // no primary key, so we can safely create new
                $is_unset_submit_mult = true;
                $query_type = 'primary_fld';
                $mult_btn   = __('Yes');
            } else {
                // primary key exists, so lets as user
                $what = 'primary_fld';
            }
            break;
        case 'index':
            $is_unset_submit_mult = true;
            $query_type = 'index_fld';
            $mult_btn   = __('Yes');
            break;
        case 'unique':
            $is_unset_submit_mult = true;
            $query_type = 'unique_fld';
            $mult_btn   = __('Yes');
            break;
        case 'spatial':
            $is_unset_submit_mult = true;
            $query_type = 'spatial_fld';
            $mult_btn   = __('Yes');
            break;
        case 'ftext':
            $is_unset_submit_mult = true;
            $query_type = 'fulltext_fld';
            $mult_btn   = __('Yes');
            break;
        case 'add_to_central_columns':
            include_once 'libraries/central_columns.lib.php';
            $centralColsError = PMA_syncUniqueColumns($selected, false);
            break;
        case 'remove_from_central_columns':
            include_once 'libraries/central_columns.lib.php';
            $centralColsError = PMA_deleteColumnsFromList($selected, false);
            break;
        case 'change':
            $this->displayHtmlForColumnChange($selected, $action);
            // execution stops here but PMA_Response correctly finishes
            // the rendering
            exit;
        case 'browse':
            // this should already be handled by tbl_structure.php
        }

        return array(
            $what, $query_type, $is_unset_submit_mult, $mult_btn,
            $centralColsError
        );
    }
}
