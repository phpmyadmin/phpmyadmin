<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold the PMA_DisplayResults class
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Handle all the functionalities related to displaying results
 * of sql queries, stored procedure, browsing sql processes or
 * displaying binary log.
 *
 * @package PhpMyAdmin
 */
class PMA_DisplayResults
{

    // Define constants
    const NO_EDIT_OR_DELETE = 'nn';
    const UPDATE_ROW = 'ur';
    const DELETE_ROW = 'dr';
    const KILL_PROCESS = 'kp';

    const POSITION_LEFT = 'left';
    const POSITION_RIGHT = 'right';
    const POSITION_BOTH = 'both';
    const POSITION_NONE = 'none';

    const PLACE_TOP_DIRECTION_DROPDOWN = 'top_direction_dropdown';
    const PLACE_BOTTOM_DIRECTION_DROPDOWN = 'bottom_direction_dropdown';

    const DISP_DIR_HORIZONTAL = 'horizontal';
    const DISP_DIR_HORIZONTAL_FLIPPED = 'horizontalflipped';
    const DISP_DIR_VERTICAL = 'vertical';

    const DISPLAY_FULL_TEXT = 'F';
    const DISPLAY_PARTIAL_TEXT = 'P';

    const HEADER_FLIP_TYPE_AUTO = 'auto';
    const HEADER_FLIP_TYPE_CSS = 'css';
    const HEADER_FLIP_TYPE_FAKE = 'fake';

    const DATE_FIELD = 'date';
    const DATETIME_FIELD = 'datetime';
    const TIMESTAMP_FIELD = 'timestamp';
    const TIME_FIELD = 'time';
    const STRING_FIELD = 'string';
    const GEOMETRY_FIELD = 'geometry';
    const BLOB_FIELD = 'BLOB';
    const BINARY_FIELD = 'BINARY';

    const RELATIONAL_KEY = 'K';
    const RELATIONAL_DISPLAY_COLUMN = 'D';

    const GEOMETRY_DISP_GEOM = 'GEOM';
    const GEOMETRY_DISP_WKT = 'WKT';
    const GEOMETRY_DISP_WKB = 'WKB';

    const SMART_SORT_ORDER = 'SMART';
    const ASCENDING_SORT_DIR = 'ASC';
    const DESCENDING_SORT_DIR = 'DESC';

    const TABLE_TYPE_INNO_DB = 'InnoDB';
    const ALL_ROWS = 'all';
    const QUERY_TYPE_SELECT = 'SELECT';

    const ROUTINE_PROCEDURE = 'procedure';
    const ROUTINE_FUNCTION = 'function';

    const ACTION_LINK_CONTENT_ICONS = 'icons';
    const ACTION_LINK_CONTENT_TEXT = 'text';


    // Declare global fields

    /** array with properties of the class */
    private $_property_array = array(

        /** string Database name */
        'db' => null,

        /** string Table name */
        'table' => null,

        /** string the URL to go back in case of errors */
        'goto' => null,

        /** string the SQL query */
        'sql_query' => null,

        /**
         * integer the total number of rows returned by the SQL query without any
         *         appended "LIMIT" clause programmatically
         */
        'unlim_num_rows' => null,

        /** array meta information about fields */
        'fields_meta' => null,

        /** boolean */
        'is_count' => null,

        /** integer */
        'is_export' => null,

        /** boolean */
        'is_func' => null,

        /** integer */
        'is_analyse' => null,

        /** integer the total number of rows returned by the SQL query */
        'num_rows' => null,

        /** integer the total number of fields returned by the SQL query */
        'fields_cnt' => null,

        /** double time taken for execute the SQL query */
        'querytime' => null,

        /** string path for theme images directory */
        'pma_theme_image' => null,

        /** string */
        'text_dir' => null,

        /** boolean */
        'is_maint' => null,

        /** boolean */
        'is_explain' => null,

        /** boolean */
        'is_show' => null,

        /** array table definitions */
        'showtable' => null,

        /** string */
        'printview' => null,

        /** string URL query */
        'url_query' => null,

        /** array column names to highlight */
        'highlight_columns' => null,

        /** array informations used with vertical display mode */
        'vertical_display' => null,

        /** array mime types information of fields */
        'mime_map' => null,

        /** boolean */
        'editable' => null
    );

    /**
     * This variable contains the column transformation information
     * for some of the system databases.
     * One element of this array represent all relevant columns in all tables in
     * one specific database
     */
    public $transformation_info;


    /**
     * Get any property of this class
     *
     * @param string $property name of the property
     *
     * @return mixed|void if property exist, value of the relevant property
     */
    public function __get($property)
    {
        if (array_key_exists($property, $this->_property_array)) {
            return $this->_property_array[$property];
        }
    }


    /**
     * Set values for any property of this class
     *
     * @param string $property name of the property
     * @param mixed  $value    value to set
     *
     * @return void
     */
    public function __set($property, $value)
    {
        if (array_key_exists($property, $this->_property_array)) {
            $this->_property_array[$property] = $value;
        }
    }


    /**
     * Constructor for PMA_DisplayResults class
     *
     * @param string $db        the database name
     * @param string $table     the table name
     * @param string $goto      the URL to go back in case of errors
     * @param string $sql_query the SQL query
     *
     * @access  public
     */
    public function __construct($db, $table, $goto, $sql_query)
    {
        $this->_setDefaultTransformations();

        $this->__set('db', $db);
        $this->__set('table', $table);
        $this->__set('goto', $goto);
        $this->__set('sql_query', $sql_query);
    }

    /**
     * Sets default transformations for some columns
     *
     * @return void
     */
    private  function _setDefaultTransformations()
    {
        $sql_highlighting_data = array(
            'libraries/plugins/transformations/Text_Plain_Formatted.class.php',
            'Text_Plain_Formatted',
            'Text_Plain'
        );
        $this->transformation_info = array(
            'information_schema' => array(
                'events' => array(
                    'event_definition' => $sql_highlighting_data
                ),
                'processlist' => array(
                    'info' => $sql_highlighting_data
                ),
                'routines' => array(
                    'routine_definition' => $sql_highlighting_data
                ),
                'triggers' => array(
                    'action_statement' => $sql_highlighting_data
                ),
                'views' => array(
                    'view_definition' => $sql_highlighting_data
                )
            )
        );

        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['db']) {
            $this->transformation_info[$cfgRelation['db']] = array();
            $relDb = &$this->transformation_info[$cfgRelation['db']];
            if (! empty($cfgRelation['history'])) {
                $relDb[$cfgRelation['history']] = array(
                    'sqlquery' => $sql_highlighting_data
                );
            }
            if (! empty($cfgRelation['bookmark'])) {
                $relDb[$cfgRelation['bookmark']] = array(
                    'query' => $sql_highlighting_data
                );
            }
            if (! empty($cfgRelation['tracking'])) {
                $relDb[$cfgRelation['tracking']] = array(
                    'schema_sql' => $sql_highlighting_data,
                    'data_sql' => $sql_highlighting_data
                );
            }
        }
    }

    /**
     * Set properties which were not initialized at the constructor
     *
     * @param integer $unlim_num_rows the total number of rows returned by
     *                                     the SQL query without any appended
     *                                     "LIMIT" clause programmatically
     * @param array   $fields_meta    meta information about fields
     * @param boolean $is_count       statement is SELECT COUNT
     * @param integer $is_export      statement contains INTO OUTFILE
     * @param boolean $is_func        statement contains a function like SUM()
     * @param integer $is_analyse     statement contains PROCEDURE ANALYSE
     * @param integer $num_rows       total no. of rows returned by SQL query
     * @param integer $fields_cnt     total no.of fields returned by SQL query
     * @param double  $querytime      time taken for execute the SQL query
     * @param string  $pmaThemeImage  path for theme images directory
     * @param string  $text_dir       text direction
     * @param boolean $is_maint       statement contains a maintenance command
     * @param boolean $is_explain     statement contains EXPLAIN
     * @param boolean $is_show        statement contains SHOW
     * @param array   $showtable      table definitions
     * @param string  $printview      print view was requested
     * @param string  $url_query      URL query
     * @param boolean $editable       whether the results set is editable
     *
     * @return void
     *
     * @see     sql.php
     */
    public function setProperties(
        $unlim_num_rows, $fields_meta, $is_count, $is_export, $is_func,
        $is_analyse, $num_rows, $fields_cnt, $querytime, $pmaThemeImage, $text_dir,
        $is_maint, $is_explain, $is_show, $showtable, $printview, $url_query,
        $editable
    ) {

        $this->__set('unlim_num_rows', $unlim_num_rows);
        $this->__set('fields_meta', $fields_meta);
        $this->__set('is_count', $is_count);
        $this->__set('is_export', $is_export);
        $this->__set('is_func', $is_func);
        $this->__set('is_analyse', $is_analyse);
        $this->__set('num_rows', $num_rows);
        $this->__set('fields_cnt', $fields_cnt);
        $this->__set('querytime', $querytime);
        $this->__set('pma_theme_image', $pmaThemeImage);
        $this->__set('text_dir', $text_dir);
        $this->__set('is_maint', $is_maint);
        $this->__set('is_explain', $is_explain);
        $this->__set('is_show', $is_show);
        $this->__set('showtable', $showtable);
        $this->__set('printview', $printview);
        $this->__set('url_query', $url_query);
        $this->__set('editable', $editable);

    } // end of the 'setProperties()' function


    /**
     * Defines the display mode to use for the results of a SQL query
     *
     * It uses a synthetic string that contains all the required informations.
     * In this string:
     *   - the first two characters stand for the action to do while
     *     clicking on the "edit" link (e.g. 'ur' for update a row, 'nn' for no
     *     edit link...);
     *   - the next two characters stand for the action to do while
     *     clicking on the "delete" link (e.g. 'kp' for kill a process, 'nn' for
     *     no delete link...);
     *   - the next characters are boolean values (1/0) and respectively stand
     *     for sorting links, navigation bar, "insert a new row" link, the
     *     bookmark feature, the expand/collapse text/blob fields button and
     *     the "display printable view" option.
     *     Of course '0'/'1' means the feature won't/will be enabled.
     *
     * @param string  &$the_disp_mode the synthetic value for display_mode (see a few
     *                                lines above for explanations)
     * @param integer &$the_total     the total number of rows returned by the SQL
     *                                 query without any programmatically appended
     *                                 LIMIT clause
     *                                 (just a copy of $unlim_num_rows if it exists,
     *                                 elsecomputed inside this function)
     *
     * @return array    an array with explicit indexes for all the display
     *                   elements
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _setDisplayMode(&$the_disp_mode, &$the_total)
    {

        // Following variables are needed for use in isset/empty or
        // use with array indexes or safe use in foreach
        $db = $this->__get('db');
        $table = $this->__get('table');
        $unlim_num_rows = $this->__get('unlim_num_rows');
        $fields_meta = $this->__get('fields_meta');
        $printview = $this->__get('printview');

        // 1. Initializes the $do_display array
        $do_display              = array();
        $do_display['edit_lnk']  = $the_disp_mode[0] . $the_disp_mode[1];
        $do_display['del_lnk']   = $the_disp_mode[2] . $the_disp_mode[3];
        $do_display['sort_lnk']  = (string) $the_disp_mode[4];
        $do_display['nav_bar']   = (string) $the_disp_mode[5];
        $do_display['ins_row']   = (string) $the_disp_mode[6];
        $do_display['bkm_form']  = (string) $the_disp_mode[7];
        $do_display['text_btn']  = (string) $the_disp_mode[8];
        $do_display['pview_lnk'] = (string) $the_disp_mode[9];

        // 2. Display mode is not "false for all elements" -> updates the
        // display mode
        if ($the_disp_mode != 'nnnn000000') {

            if (isset($printview) && ($printview == '1')) {
                // 2.0 Print view -> set all elements to false!
                $do_display['edit_lnk']  = self::NO_EDIT_OR_DELETE; // no edit link
                $do_display['del_lnk']   = self::NO_EDIT_OR_DELETE; // no delete link
                $do_display['sort_lnk']  = (string) '0';
                $do_display['nav_bar']   = (string) '0';
                $do_display['ins_row']   = (string) '0';
                $do_display['bkm_form']  = (string) '0';
                $do_display['text_btn']  = (string) '0';
                $do_display['pview_lnk'] = (string) '0';

            } elseif ($this->__get('is_count') || $this->__get('is_analyse')
                || $this->__get('is_maint') || $this->__get('is_explain')
            ) {
                // 2.1 Statement is a "SELECT COUNT", a
                //     "CHECK/ANALYZE/REPAIR/OPTIMIZE", an "EXPLAIN" one or
                //     contains a "PROC ANALYSE" part
                $do_display['edit_lnk']  = self::NO_EDIT_OR_DELETE; // no edit link
                $do_display['del_lnk']   = self::NO_EDIT_OR_DELETE; // no delete link
                $do_display['sort_lnk']  = (string) '0';
                $do_display['nav_bar']   = (string) '0';
                $do_display['ins_row']   = (string) '0';
                $do_display['bkm_form']  = (string) '1';

                if ($this->__get('is_maint')) {
                    $do_display['text_btn']  = (string) '1';
                } else {
                    $do_display['text_btn']  = (string) '0';
                }
                $do_display['pview_lnk'] = (string) '1';

            } elseif ($this->__get('is_show')) {
                // 2.2 Statement is a "SHOW..."
                /**
                 * 2.2.1
                 * @todo defines edit/delete links depending on show statement
                 */
                preg_match(
                    '@^SHOW[[:space:]]+(VARIABLES|(FULL[[:space:]]+)?'
                    . 'PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS|DATABASES|FIELDS'
                    . ')@i',
                    $this->__get('sql_query'), $which
                );
                if (isset($which[1])
                    && (strpos(' ' . strtoupper($which[1]), 'PROCESSLIST') > 0)
                ) {
                    // no edit link
                    $do_display['edit_lnk'] = self::NO_EDIT_OR_DELETE;
                    // "kill process" type edit link
                    $do_display['del_lnk']  = self::KILL_PROCESS;
                } else {
                    // Default case -> no links
                    // no edit link
                    $do_display['edit_lnk'] = self::NO_EDIT_OR_DELETE;
                    // no delete link
                    $do_display['del_lnk']  = self::NO_EDIT_OR_DELETE;
                }
                // 2.2.2 Other settings
                $do_display['sort_lnk']  = (string) '0';
                $do_display['nav_bar']   = (string) '0';
                $do_display['ins_row']   = (string) '0';
                $do_display['bkm_form']  = (string) '1';
                $do_display['text_btn']  = (string) '1';
                $do_display['pview_lnk'] = (string) '1';

            } else {
                // 2.3 Other statements (ie "SELECT" ones) -> updates
                //     $do_display['edit_lnk'], $do_display['del_lnk'] and
                //     $do_display['text_btn'] (keeps other default values)
                $prev_table = $fields_meta[0]->table;
                $do_display['text_btn']  = (string) '1';

                for ($i = 0; $i < $this->__get('fields_cnt'); $i++) {

                    $is_link = ($do_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                        || ($do_display['del_lnk'] != self::NO_EDIT_OR_DELETE)
                        || ($do_display['sort_lnk'] != '0')
                        || ($do_display['ins_row'] != '0');

                    // 2.3.2 Displays edit/delete/sort/insert links?
                    if ($is_link
                        && (($fields_meta[$i]->table == '')
                        || ($fields_meta[$i]->table != $prev_table))
                    ) {
                        // don't display links
                        $do_display['edit_lnk'] = self::NO_EDIT_OR_DELETE;
                        $do_display['del_lnk']  = self::NO_EDIT_OR_DELETE;
                        /**
                         * @todo May be problematic with same field names
                         * in two joined table.
                         */
                        // $do_display['sort_lnk'] = (string) '0';
                        $do_display['ins_row']  = (string) '0';
                        if ($do_display['text_btn'] == '1') {
                            break;
                        }
                    } // end if (2.3.2)

                    // 2.3.3 Always display print view link
                    $do_display['pview_lnk']    = (string) '1';
                    $prev_table = $fields_meta[$i]->table;

                } // end for
            } // end if..elseif...else (2.1 -> 2.3)
        } // end if (2)

        // 3. Gets the total number of rows if it is unknown
        if (isset($unlim_num_rows) && $unlim_num_rows != '') {
            $the_total = $unlim_num_rows;
        } elseif ((($do_display['nav_bar'] == '1')
            || ($do_display['sort_lnk'] == '1'))
            && (strlen($db) && !empty($table))
        ) {
            $the_total   = PMA_Table::countRecords($db, $table);
        }

        // 4. If navigation bar or sorting fields names URLs should be
        //    displayed but there is only one row, change these settings to
        //    false
        if ($do_display['nav_bar'] == '1' || $do_display['sort_lnk'] == '1') {

            // - Do not display sort links if less than 2 rows.
            // - For a VIEW we (probably) did not count the number of rows
            //   so don't test this number here, it would remove the possibility
            //   of sorting VIEW results.
            if (isset($unlim_num_rows)
                && ($unlim_num_rows < 2)
                && ! PMA_Table::isView($db, $table)
            ) {
                // force display of navbar for vertical/horizontal display-choice.
                // $do_display['nav_bar']  = (string) '0';
                $do_display['sort_lnk'] = (string) '0';
            }
        } // end if (3)

        // 5. Updates the synthetic var
        $the_disp_mode = join('', $do_display);

        return $do_display;

    } // end of the 'setDisplayMode()' function


    /**
     * Return true if we are executing a query in the form of
     * "SELECT * FROM <a table> ..."
     *
     * @param array $analyzed_sql the analyzed query
     *
     * @return boolean
     *
     * @access  private
     *
     * @see     _getTableHeaders(), _getColumnParams()
     */
    private function _isSelect($analyzed_sql)
    {
        if (!isset($analyzed_sql[0]['select_expr'])) {
            $analyzed_sql[0]['select_expr'] = 0;
        }

        return ! ($this->__get('is_count') || $this->__get('is_export')
            || $this->__get('is_func') || $this->__get('is_analyse'))
            && (count($analyzed_sql[0]['select_expr']) == 0)
            && isset($analyzed_sql[0]['queryflags']['select_from'])
            && (count($analyzed_sql[0]['table_ref']) == 1);
    }


    /**
     * Get a navigation button
     *
     * @param string  $caption            iconic caption for button
     * @param string  $title              text for button
     * @param integer $pos                position for next query
     * @param string  $html_sql_query     query ready for display
     * @param string  $onsubmit           optional onsubmit clause
     * @param string  $input_for_real_end optional hidden field for special treatment
     * @param string  $onclick            optional onclick clause
     *
     * @return string                     html content
     *
     * @access  private
     *
     * @see     _getMoveBackwardButtonsForTableNavigation(),
     *          _getMoveForwardButtonsForTableNavigation()
     */
    private function _getTableNavigationButton(
        $caption, $title, $pos, $html_sql_query, $onsubmit = '',
        $input_for_real_end = '', $onclick = ''
    ) {

        $caption_output = '';
        if (PMA_Util::showIcons('TableNavigationLinksMode')) {
            $caption_output .= $caption;
        }

        if (PMA_Util::showText('TableNavigationLinksMode')) {
            $caption_output .= '&nbsp;' . $title;
        }
        $title_output = ' title="' . $title . '"';

        return '<td>'
            . '<form action="sql.php" method="post" ' . $onsubmit . '>'
            . PMA_URL_getHiddenInputs(
                $this->__get('db'), $this->__get('table')
            )
            . '<input type="hidden" name="sql_query" value="'
            . $html_sql_query . '" />'
            . '<input type="hidden" name="pos" value="' . $pos . '" />'
            . '<input type="hidden" name="goto" value="' . $this->__get('goto')
            . '" />'
            . $input_for_real_end
            . '<input type="submit" name="navig"'
            . ' class="ajax" '
            . 'value="' . $caption_output . '" ' . $title_output . $onclick . ' />'
            . '</form>'
            . '</td>';

    } // end function _getTableNavigationButton()


    /**
     * Get a navigation bar to browse among the results of a SQL query
     *
     * @param integer $pos_next                  the offset for the "next" page
     * @param integer $pos_prev                  the offset for the "previous" page
     * @param string  $id_for_direction_dropdown the id for the direction dropdown
     * @param boolean $is_innodb                 whether its InnoDB or not
     *
     * @return string                            html content
     *
     * @access  private
     *
     * @see     _getTable()
     */
    private function _getTableNavigation(
        $pos_next, $pos_prev, $id_for_direction_dropdown, $is_innodb
    ) {

        $table_navigation_html = '';
        $showtable = $this->__get('showtable'); // To use in isset

        // here, using htmlentities() would cause problems if the query
        // contains accented characters
        $html_sql_query = htmlspecialchars($this->__get('sql_query'));

        /**
         * @todo move this to a central place
         * @todo for other future table types
         */
        $is_innodb = (isset($showtable['Type'])
            && $showtable['Type'] == self::TABLE_TYPE_INNO_DB);

        // Navigation bar
        $table_navigation_html .= '<table class="navigation nospacing nopadding">'
            . '<tr>'
            . '<td class="navigation_separator"></td>';

        // Move to the beginning or to the previous page
        if ($_SESSION['tmpval']['pos']
            && ($_SESSION['tmpval']['max_rows'] != self::ALL_ROWS)
        ) {

            $table_navigation_html
                .= $this->_getMoveBackwardButtonsForTableNavigation(
                    $html_sql_query, $pos_prev
                );

        } // end move back

        $nbTotalPage = 1;
        //page redirection
        // (unless we are showing all records)
        if ($_SESSION['tmpval']['max_rows'] != self::ALL_ROWS) { //if1

            $pageNow = @floor(
                $_SESSION['tmpval']['pos']
                / $_SESSION['tmpval']['max_rows']
            ) + 1;

            $nbTotalPage = @ceil(
                $this->__get('unlim_num_rows')
                / $_SESSION['tmpval']['max_rows']
            );

            if ($nbTotalPage > 1) { //if2

                $table_navigation_html .= '<td>';
                $_url_params = array(
                    'db'        => $this->__get('db'),
                    'table'     => $this->__get('table'),
                    'sql_query' => $this->__get('sql_query'),
                    'goto'      => $this->__get('goto'),
                );

                //<form> to keep the form alignment of button < and <<
                // and also to know what to execute when the selector changes
                $table_navigation_html .= '<form action="sql.php'
                    . PMA_URL_getCommon($_url_params)
                    . '" method="post">';

                $table_navigation_html .= PMA_Util::pageselector(
                    'pos',
                    $_SESSION['tmpval']['max_rows'],
                    $pageNow, $nbTotalPage, 200, 5, 5, 20, 10
                );

                $table_navigation_html .= '</form>'
                    . '</td>';
            } //_if2
        } //_if1

        // Display the "Show all" button if allowed
        if (($this->__get('num_rows') < $this->__get('unlim_num_rows'))
            && ($GLOBALS['cfg']['ShowAll']
            || ($this->__get('unlim_num_rows') <= 500))
        ) {

            $table_navigation_html .= $this->_getShowAllButtonForTableNavigation(
                $html_sql_query
            );

        } // end show all

        // Move to the next page or to the last one
        $endpos = $_SESSION['tmpval']['pos']
            + $_SESSION['tmpval']['max_rows'];

        if (($endpos < $this->__get('unlim_num_rows'))
            && ($this->__get('num_rows') >= $_SESSION['tmpval']['max_rows'])
            && ($_SESSION['tmpval']['max_rows'] != self::ALL_ROWS)
        ) {

            $table_navigation_html
                .= $this->_getMoveForwardButtonsForTableNavigation(
                    $html_sql_query, $pos_next, $is_innodb
                );

        } // end move toward

        // show separator if pagination happen
        if ($nbTotalPage > 1) {
            $table_navigation_html
                .= '<td><div class="navigation_separator">|</div></td>';
        }

        $table_navigation_html .= '<td>'
            . '<div class="save_edited hide">'
            . '<input type="submit" value="' . __('Save edited data') . '" />'
            . '<div class="navigation_separator">|</div>'
            . '</div>'
            . '</td>'
            . '<td>'
            . '<div class="restore_column hide">'
            . '<input type="submit" value="' . __('Restore column order') . '" />'
            . '<div class="navigation_separator">|</div>'
            . '</div>'
            . '</td>';

        // if displaying a VIEW, $unlim_num_rows could be zero because
        // of $cfg['MaxExactCountViews']; in this case, avoid passing
        // the 5th parameter to checkFormElementInRange()
        // (this means we can't validate the upper limit
        $table_navigation_html .= '<td class="navigation_goto">';

        $table_navigation_html .= '<form action="sql.php" method="post" '
            . 'onsubmit="return '
                . '(checkFormElementInRange('
                    . 'this, '
                    . '\'session_max_rows\', '
                    . '\''
                    . str_replace('\'', '\\\'', __('%d is not valid row number.'))
                    . '\', '
                    . '1)'
                . ' &amp;&amp; '
                . 'checkFormElementInRange('
                    . 'this, '
                    . '\'pos\', '
                    . '\''
                    . str_replace('\'', '\\\'', __('%d is not valid row number.'))
                    . '\', '
                    . '0'
                    . (($this->__get('unlim_num_rows') > 0)
                        ? ', ' . ($this->__get('unlim_num_rows') - 1)
                        : ''
                    )
                    . ')'
                . ')'
            . '">';

        $table_navigation_html .= PMA_URL_getHiddenInputs(
            $this->__get('db'), $this->__get('table')
        );

        $table_navigation_html .= $this->_getAdditionalFieldsForTableNavigation(
            $html_sql_query, $id_for_direction_dropdown
        );

        $table_navigation_html .= '</form>'
            . '</td>'
            . '<td class="navigation_separator"></td>'
            . '<td>'
            . '<span>' . __('Filter rows') . ':</span>'
            . '<input type="text" class="filter_rows" placeholder="'
            . __('Search this table') . '">'
            . '</td>'
            . '<td class="navigation_separator"></td>'
            . '</tr>'
            . '</table>';

        return $table_navigation_html;

    } // end of the '_getTableNavigation()' function


    /**
     * Prepare move backward buttons - previous and first
     *
     * @param string  $html_sql_query the sql encoded by html special characters
     * @param integer $pos_prev       the offset for the "previous" page
     *
     * @return  string                  html content
     *
     * @access  private
     *
     * @see     _getTableNavigation()
     */
    private function _getMoveBackwardButtonsForTableNavigation(
        $html_sql_query, $pos_prev
    ) {
        return $this->_getTableNavigationButton(
            '&lt;&lt;', _pgettext('First page', 'Begin'), 0, $html_sql_query
        )
        . $this->_getTableNavigationButton(
            '&lt;', _pgettext('Previous page', 'Previous'), $pos_prev,
            $html_sql_query
        );
    } // end of the '_getMoveBackwardButtonsForTableNavigation()' function


    /**
     * Prepare Show All button for table navigation
     *
     * @param string $html_sql_query the sql encoded by html special characters
     *
     * @return  string                          html content
     *
     * @access  private
     *
     * @see     _getTableNavigation()
     */
    private function _getShowAllButtonForTableNavigation($html_sql_query)
    {
        return "\n"
            . '<td>'
            . '<form action="sql.php" method="post">'
            . PMA_URL_getHiddenInputs(
                $this->__get('db'), $this->__get('table')
            )
            . '<input type="hidden" name="sql_query" value="'
            . $html_sql_query . '" />'
            . '<input type="hidden" name="pos" value="0" />'
            . '<input type="hidden" name="session_max_rows" value="all" />'
            . '<input type="hidden" name="goto" value="' . $this->__get('goto')
            . '" />'
            . '<input type="submit" name="navig" value="' . __('Show all') . '" />'
            . '</form>'
            . '</td>';
    } // end of the '_getShowAllButtonForTableNavigation()' function


    /**
     * Prepare move forward buttons - next and last
     *
     * @param string  $html_sql_query the sql encoded by htmlspecialchars()
     * @param integer $pos_next       the offset for the "next" page
     * @param boolean $is_innodb      whether it's InnoDB or not
     *
     * @return  string  $buttons_html   html content
     *
     * @access  private
     *
     * @see     _getTableNavigation()
     */
    private function _getMoveForwardButtonsForTableNavigation(
        $html_sql_query, $pos_next, $is_innodb
    ) {

        // display the Next button
        $buttons_html = $this->_getTableNavigationButton(
            '&gt;',
            _pgettext('Next page', 'Next'),
            $pos_next,
            $html_sql_query
        );

        // prepare some options for the End button
        if ($is_innodb
            && $this->__get('unlim_num_rows') > $GLOBALS['cfg']['MaxExactCount']
        ) {
            $input_for_real_end = '<input id="real_end_input" type="hidden" '
                . 'name="find_real_end" value="1" />';
            // no backquote around this message
            $onclick = '';
        } else {
            $input_for_real_end = $onclick = '';
        }

        $maxRows = $_SESSION['tmpval']['max_rows'];
        $onsubmit = 'onsubmit="return '
            . ($_SESSION['tmpval']['pos']
                + $maxRows
                < $this->__get('unlim_num_rows')
                && $this->__get('num_rows') >= $maxRows)
            ? 'true'
            : 'false' . '"';

        // display the End button
        $buttons_html .= $this->_getTableNavigationButton(
            '&gt;&gt;',
            _pgettext('Last page', 'End'),
            @((ceil(
                $this->__get('unlim_num_rows')
                / $_SESSION['tmpval']['max_rows']
            )- 1) * $maxRows),
            $html_sql_query, $onsubmit, $input_for_real_end, $onclick
        );

        return $buttons_html;

    } // end of the '_getMoveForwardButtonsForTableNavigation()' function


    /**
     * Prepare fields for table navigation
     * Number of rows
     *
     * @param string $html_sql_query            the sql encoded by htmlspecialchars()
     * @param string $id_for_direction_dropdown the id for the direction dropdown
     *
     * @return  string  $additional_fields_html html content
     *
     * @access  private
     *
     * @see     _getTableNavigation()
     */
    private function _getAdditionalFieldsForTableNavigation(
        $html_sql_query, $id_for_direction_dropdown
    ) {

        $additional_fields_html = '';

        $additional_fields_html .= '<input type="hidden" name="sql_query" '
            . 'value="' . $html_sql_query . '" />'
            . '<input type="hidden" name="goto" value="' . $this->__get('goto')
            . '" />'
            . '<input type="hidden" name="pos" size="3" value="'
            // Do not change the position when changing the number of rows
            . $_SESSION['tmpval']['pos'] . '" />';

        $numberOfRowsChoices = array(
            '25'  => 25,
            '50'  => 50,
            '100' => 100,
            '250' => 250,
            '500' => 500
        );
        $additional_fields_html .= __('Number of rows:') . ' ';
        $additional_fields_html .= PMA_Util::getDropdown(
            'session_max_rows', $numberOfRowsChoices,
            $_SESSION['tmpval']['max_rows'], '', 'autosubmit'
        );

        if ($GLOBALS['cfg']['ShowDisplayDirection']) {
            // Display mode (horizontal/vertical)
            $additional_fields_html .= __('Mode:') . ' ' . "\n";
            $choices = array(
                'horizontal'        => __('horizontal'),
                'horizontalflipped' => __('horizontal (rotated headers)'),
                'vertical'          => __('vertical')
            );

            $additional_fields_html .= PMA_Util::getDropdown(
                'disp_direction', $choices,
                $_SESSION['tmpval']['disp_direction'],
                $id_for_direction_dropdown, 'autosubmit'
            );
            unset($choices);
        }

        return $additional_fields_html;

    } // end of the '_getAdditionalFieldsForTableNavigation()' function


    /**
     * Get the headers of the results table
     *
     * @param array        &$is_display                 which elements to display
     * @param array|string $analyzed_sql                the analyzed query
     * @param array        $sort_expression             sort expression
     * @param array        $sort_expression_nodirection sort expression
     *                                                  without direction
     * @param array        $sort_direction              sort direction
     * @param boolean      $is_limited_display          with limited operations
     *                                                  or not
     *
     * @return string html content
     *
     * @access private
     *
     * @see    getTable()
     */
    private function _getTableHeaders(
        &$is_display, $analyzed_sql = '',
        $sort_expression = '', $sort_expression_nodirection = '',
        $sort_direction = '', $is_limited_display = false
    ) {

        $table_headers_html = '';
        // Following variable are needed for use in isset/empty or
        // use with array indexes/safe use in foreach
        $fields_meta = $this->__get('fields_meta');
        $highlight_columns = $this->__get('highlight_columns');
        $printview = $this->__get('printview');
        $vertical_display = $this->__get('vertical_display');

        // required to generate sort links that will remember whether the
        // "Show all" button has been clicked
        $sql_md5 = md5($this->__get('sql_query'));
        $session_max_rows = $is_limited_display
            ? 0
            : $_SESSION['tmpval']['query'][$sql_md5]['max_rows'];

        $direction = isset($_SESSION['tmpval']['disp_direction'])
            ? $_SESSION['tmpval']['disp_direction']
            : '';

        if ($analyzed_sql == '') {
            $analyzed_sql = array();
        }

        $directionCondition = ($direction == self::DISP_DIR_HORIZONTAL)
            || ($direction == self::DISP_DIR_HORIZONTAL_FLIPPED);

        // can the result be sorted?
        if ($is_display['sort_lnk'] == '1') {

            list($unsorted_sql_query, $drop_down_html)
                = $this->_getUnsortedSqlAndSortByKeyDropDown(
                    $analyzed_sql, $sort_expression
                );

            $table_headers_html .= $drop_down_html;

        }

        // Output data needed for grid editing
        $table_headers_html .= '<input id="save_cells_at_once" type="hidden" value="'
            . $GLOBALS['cfg']['SaveCellsAtOnce'] . '" />'
            . '<div class="common_hidden_inputs">'
            . PMA_URL_getHiddenInputs(
                $this->__get('db'), $this->__get('table')
            )
            . '</div>';

        // Output data needed for column reordering and show/hide column
        if ($this->_isSelect($analyzed_sql)) {
            $table_headers_html .= $this->_getDataForResettingColumnOrder();
        }

        $vertical_display['emptypre']   = 0;
        $vertical_display['emptyafter'] = 0;
        $vertical_display['textbtn']    = '';
        $full_or_partial_text_link = null;

        $this->__set('vertical_display', $vertical_display);

        // Display options (if we are not in print view)
        if (! (isset($printview) && ($printview == '1')) && ! $is_limited_display) {

            $table_headers_html .= $this->_getOptionsBlock();

            // prepare full/partial text button or link
            $full_or_partial_text_link = $this->_getFullOrPartialTextButtonOrLink();
        }

        // Start of form for multi-rows edit/delete/export
        $table_headers_html .= $this->_getFormForMultiRowOperations(
            $is_display['del_lnk']
        );

        // 1. Set $colspan or $rowspan and generate html with full/partial
        // text button or link
        list($colspan, $rowspan, $button_html)
            = $this->_getFieldVisibilityParams(
                $directionCondition, $is_display, $full_or_partial_text_link
            );

        $table_headers_html .= $button_html;

        // 2. Displays the fields' name
        // 2.0 If sorting links should be used, checks if the query is a "JOIN"
        //     statement (see 2.1.3)

        // 2.0.1 Prepare Display column comments if enabled
        //       ($GLOBALS['cfg']['ShowBrowseComments']).
        //       Do not show comments, if using horizontalflipped mode,
        //       because of space usage
        $comments_map = $this->_getTableCommentsArray($direction, $analyzed_sql);

        if ($GLOBALS['cfgRelation']['commwork']
            && $GLOBALS['cfgRelation']['mimework']
            && $GLOBALS['cfg']['BrowseMIME']
            && ! $_SESSION['tmpval']['hide_transformation']
        ) {
            include_once './libraries/transformations.lib.php';
            $this->__set(
                'mime_map',
                PMA_getMIME($this->__get('db'), $this->__get('table'))
            );
        }

        // See if we have to highlight any header fields of a WHERE query.
        // Uses SQL-Parser results.
        $this->_setHighlightedColumnGlobalField($analyzed_sql);

        list($col_order, $col_visib) = $this->_getColumnParams($analyzed_sql);

        for ($j = 0; $j < $this->__get('fields_cnt'); $j++) {

            // assign $i with appropriate column order
            $i = $col_order ? $col_order[$j] : $j;

            //  See if this column should get highlight because it's used in the
            //  where-query.
            $condition_field = (isset($highlight_columns[$fields_meta[$i]->name])
                || isset(
                    $highlight_columns[PMA_Util::backquote($fields_meta[$i]->name)])
                )
                ? true
                : false;

            // 2.0 Prepare comment-HTML-wrappers for each row, if defined/enabled.
            $comments = $this->_getCommentForRow($comments_map, $fields_meta[$i]);

            $vertical_display = $this->__get('vertical_display');

            if (($is_display['sort_lnk'] == '1') && ! $is_limited_display) {

                list($order_link, $sorted_header_html)
                    = $this->_getOrderLinkAndSortedHeaderHtml(
                        $fields_meta[$i], $sort_expression,
                        $sort_expression_nodirection, $i, $unsorted_sql_query,
                        $session_max_rows, $direction, $comments,
                        $sort_direction, $directionCondition, $col_visib,
                        $col_visib[$j]
                    );

                $table_headers_html .= $sorted_header_html;

                $vertical_display['desc'][] = '    <th '
                    . 'class="draggable'
                    . ($condition_field ? ' condition' : '')
                    . '" data-column="' . htmlspecialchars($fields_meta[$i]->name)
                    . '">' . "\n" . $order_link . $comments . '    </th>' . "\n";
            } else {
                // 2.2 Results can't be sorted

                if ($directionCondition) {
                    $table_headers_html
                        .= $this->_getDraggableClassForNonSortableColumns(
                            $col_visib, $col_visib[$j], $condition_field,
                            $direction, $fields_meta[$i], $comments
                        );
                }

                $vertical_display['desc'][] = '    <th '
                    . 'class="draggable'
                    . ($condition_field ? ' condition"' : '')
                    . '" data-column="' . htmlspecialchars($fields_meta[$i]->name)
                    . '">' . "\n" . '        '
                    . htmlspecialchars($fields_meta[$i]->name)
                    . "\n" . $comments . '    </th>';
            } // end else (2.2)

            $this->__set('vertical_display', $vertical_display);

        } // end for

        // Display column at rightside - checkboxes or empty column
        if (! $printview) {
            $table_headers_html .= $this->_getColumnAtRightSide(
                $is_display, $directionCondition, $full_or_partial_text_link,
                $colspan, $rowspan
            );
        }

        if ($directionCondition) {
            $table_headers_html .= '</tr>'
                . '</thead>';
        }

        return $table_headers_html;

    } // end of the '_getTableHeaders()' function


    /**
     * Prepare unsorted sql query and sort by key drop down
     *
     * @param array  $analyzed_sql    the analyzed query
     * @param string $sort_expression sort expression
     *
     * @return  array   two element array - $unsorted_sql_query, $drop_down_html
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getUnsortedSqlAndSortByKeyDropDown(
        $analyzed_sql, $sort_expression
    ) {

        $drop_down_html = '';

        // Just as fallback
        $unsorted_sql_query     = $this->__get('sql_query');
        if (isset($analyzed_sql[0]['unsorted_query'])) {
            $unsorted_sql_query = $analyzed_sql[0]['unsorted_query'];
        }
        // Handles the case of multiple clicks on a column's header
        // which would add many spaces before "ORDER BY" in the
        // generated query.
        $unsorted_sql_query = trim($unsorted_sql_query);

        // sorting by indexes, only if it makes sense (only one table ref)
        if (isset($analyzed_sql)
            && isset($analyzed_sql[0])
            && isset($analyzed_sql[0]['querytype'])
            && ($analyzed_sql[0]['querytype'] == self::QUERY_TYPE_SELECT)
            && isset($analyzed_sql[0]['table_ref'])
            && (count($analyzed_sql[0]['table_ref']) == 1)
        ) {
            // grab indexes data:
            $indexes = PMA_Index::getFromTable(
                $this->__get('table'),
                $this->__get('db')
            );

            // do we have any index?
            if ($indexes) {
                $drop_down_html = $this->_getSortByKeyDropDown(
                    $indexes, $sort_expression,
                    $unsorted_sql_query
                );
            }
        }

        return array($unsorted_sql_query, $drop_down_html);

    } // end of the '_getUnsortedSqlAndSortByKeyDropDown()' function


    /**
     * Prepare sort by key dropdown - html code segment
     *
     * @param array  $indexes            the indexes of the table for sort criteria
     * @param string $sort_expression    the sort expression
     * @param string $unsorted_sql_query the unsorted sql query
     *
     * @return  string  $drop_down_html         html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getSortByKeyDropDown(
        $indexes, $sort_expression, $unsorted_sql_query
    ) {

        $drop_down_html = '';

        $drop_down_html .= '<form action="sql.php" method="post">' . "\n"
            . PMA_URL_getHiddenInputs(
                $this->__get('db'), $this->__get('table')
            )
            . __('Sort by key')
            . ': <select name="sql_query" class="autosubmit">' . "\n";

        $used_index = false;
        $local_order = (isset($sort_expression) ? $sort_expression : '');

        foreach ($indexes as $index) {

            $asc_sort = '`'
                . implode('` ASC, `', array_keys($index->getColumns()))
                . '` ASC';

            $desc_sort = '`'
                . implode('` DESC, `', array_keys($index->getColumns()))
                . '` DESC';

            $used_index = $used_index
                || ($local_order == $asc_sort)
                || ($local_order == $desc_sort);

            if (preg_match(
                '@(.*)([[:space:]](LIMIT (.*)|PROCEDURE (.*)|'
                . 'FOR UPDATE|LOCK IN SHARE MODE))@is',
                $unsorted_sql_query, $my_reg
            )) {
                $unsorted_sql_query_first_part = $my_reg[1];
                $unsorted_sql_query_second_part = $my_reg[2];
            } else {
                $unsorted_sql_query_first_part = $unsorted_sql_query;
                $unsorted_sql_query_second_part = '';
            }

            $drop_down_html .= '<option value="'
                . htmlspecialchars(
                    $unsorted_sql_query_first_part  . "\n"
                    . ' ORDER BY ' . $asc_sort
                    . $unsorted_sql_query_second_part
                )
                . '"' . ($local_order == $asc_sort
                    ? ' selected="selected"'
                    : '')
                . '>' . htmlspecialchars($index->getName()) . ' ('
                . __('Ascending') . ')</option>';

            $drop_down_html .= '<option value="'
                . htmlspecialchars(
                    $unsorted_sql_query_first_part . "\n"
                    . ' ORDER BY ' . $desc_sort
                    . $unsorted_sql_query_second_part
                )
                . '"' . ($local_order == $desc_sort
                    ? ' selected="selected"'
                    : '')
                . '>' . htmlspecialchars($index->getName()) . ' ('
                . __('Descending') . ')</option>';
        }

        $drop_down_html .= '<option value="' . htmlspecialchars($unsorted_sql_query)
            . '"' . ($used_index ? '' : ' selected="selected"') . '>' . __('None')
            . '</option>'
            . '</select>' . "\n"
            . '</form>' . "\n";

        return $drop_down_html;

    } // end of the '_getSortByKeyDropDown()' function


    /**
     * Set column span, row span and prepare html with full/partial
     * text button or link
     *
     * @param boolean $directionCondition        display direction horizontal or
     *                                           horizontalflipped
     * @param array   &$is_display               which elements to display
     * @param string  $full_or_partial_text_link full/partial link or text button
     *
     * @return  array   3 element array - $colspan, $rowspan, $button_html
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getFieldVisibilityParams(
        $directionCondition, &$is_display, $full_or_partial_text_link
    ) {

        $button_html = '';
        $colspan = $rowspan = null;
        $vertical_display = $this->__get('vertical_display');

        // 1. Displays the full/partial text button (part 1)...
        if ($directionCondition) {

            $button_html .= '<thead><tr>' . "\n";

            $colspan = (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE))
                ? ' colspan="4"'
                : '';

        } else {
            $rowspan = (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE))
                ? ' rowspan="4"'
                : '';
        }

        //     ... before the result table
        if ((($is_display['edit_lnk'] == self::NO_EDIT_OR_DELETE)
            && ($is_display['del_lnk'] == self::NO_EDIT_OR_DELETE))
            && ($is_display['text_btn'] == '1')
        ) {

            $vertical_display['emptypre']
                = (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE)) ? 4 : 0;

            if ($directionCondition) {

                $button_html .= '<th colspan="' . $this->__get('fields_cnt') . '">'
                    . '</th>'
                    . '</tr>'
                    . '<tr>';

                // end horizontal/horizontalflipped mode
            } else {

                $span = $this->__get('num_rows') + 1 + floor(
                    $this->__get('num_rows')
                    / $_SESSION['tmpval']['repeat_cells']
                );
                $button_html .= '<tr><th colspan="' . $span . '"></th></tr>';

            } // end vertical mode

        } elseif ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_LEFT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && ($is_display['text_btn'] == '1')
        ) {
            //     ... at the left column of the result table header if possible
            //     and required

            $vertical_display['emptypre']
                = (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE)) ? 4 : 0;

            if ($directionCondition) {

                $button_html .= '<th ' . $colspan . '>'
                    . $full_or_partial_text_link . '</th>';
                // end horizontal/horizontalflipped mode

            } else {

                $vertical_display['textbtn']
                    = '    <th ' . $rowspan . ' class="vmiddle">' . "\n"
                    . '        ' . "\n"
                    . '    </th>' . "\n";
            } // end vertical mode

        } elseif ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_LEFT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
            || ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE))
        ) {
            //     ... elseif no button, displays empty(ies) col(s) if required

            $vertical_display['emptypre']
                = (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE)) ? 4 : 0;

            if ($directionCondition) {

                $button_html .= '<td ' . $colspan . '></td>';

                // end horizontal/horizontalfipped mode
            } else {
                $vertical_display['textbtn'] = '    <td' . $rowspan .
                    '></td>' . "\n";
            } // end vertical mode

        } elseif (($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_NONE)
            && ($directionCondition)
        ) {
            // ... elseif display an empty column if the actions links are
            //  disabled to match the rest of the table
            $button_html .= '<th></th>';
        }

        $this->__set('vertical_display', $vertical_display);

        return array($colspan, $rowspan, $button_html);

    } // end of the '_getFieldVisibilityParams()' function


    /**
     * Get table comments as array
     *
     * @param boolean $direction    display direction, horizontal
     *                              or horizontalflipped
     * @param array   $analyzed_sql the analyzed query
     *
     * @return  array $comments_map table comments when condition true
     *          null                when condition falls
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getTableCommentsArray($direction, $analyzed_sql)
    {

        $comments_map = null;

        if ($GLOBALS['cfg']['ShowBrowseComments']
            && ($direction != self::DISP_DIR_HORIZONTAL_FLIPPED)
        ) {
            $comments_map = array();
            if (isset($analyzed_sql[0])
                && is_array($analyzed_sql[0])
                && isset($analyzed_sql[0]['table_ref'])
            ) {
                foreach ($analyzed_sql[0]['table_ref'] as $tbl) {
                    $tb = $tbl['table_true_name'];
                    $comments_map[$tb] = PMA_getComments($this->__get('db'), $tb);
                    unset($tb);
                }
            }
        }

        return $comments_map;

    } // end of the '_getTableCommentsArray()' function


    /**
     * Set global array for store highlighted header fields
     *
     * @param array $analyzed_sql the analyzed query
     *
     * @return  void
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _setHighlightedColumnGlobalField($analyzed_sql)
    {

        $highlight_columns = array();
        if (isset($analyzed_sql) && isset($analyzed_sql[0])
            && isset($analyzed_sql[0]['where_clause_identifiers'])
            && is_array($analyzed_sql[0]['where_clause_identifiers'])
        ) {
            foreach ($analyzed_sql[0]['where_clause_identifiers'] as $wci) {
                $highlight_columns[$wci] = 'true';
            }
        }

        $this->__set('highlight_columns', $highlight_columns);

    } // end of the '_setHighlightedColumnGlobalField()' function


    /**
     * Prepare data for column restoring and show/hide
     *
     * @return  string  $data_html      html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getDataForResettingColumnOrder()
    {

        $data_html = '';

        // generate the column order, if it is set
        $pmatable = new PMA_Table($this->__get('table'), $this->__get('db'));
        $col_order = $pmatable->getUiProp(PMA_Table::PROP_COLUMN_ORDER);

        if ($col_order) {
            $data_html .= '<input id="col_order" type="hidden" value="'
                . implode(',', $col_order) . '" />';
        }

        $col_visib = $pmatable->getUiProp(PMA_Table::PROP_COLUMN_VISIB);

        if ($col_visib) {
            $data_html .= '<input id="col_visib" type="hidden" value="'
                . implode(',', $col_visib) . '" />';
        }

        // generate table create time
        if (! PMA_Table::isView($this->__get('db'), $this->__get('table'))) {
            $data_html .= '<input id="table_create_time" type="hidden" value="'
                . PMA_Table::sGetStatusInfo(
                    $this->__get('db'), $this->__get('table'), 'Create_time'
                ) . '" />';
        }

        return $data_html;

    } // end of the '_getDataForResettingColumnOrder()' function


    /**
     * Prepare option fields block
     *
     * @return  string  $options_html   html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getOptionsBlock()
    {

        $options_html = '';

        $options_html .= '<form method="post" action="sql.php" '
            . 'name="displayOptionsForm" '
            . 'id="displayOptionsForm"';

        $options_html .= ' class="ajax" ';

        $options_html .= '>';
        $url_params = array(
            'db' => $this->__get('db'),
            'table' => $this->__get('table'),
            'sql_query' => $this->__get('sql_query'),
            'goto' => $this->__get('goto'),
            'display_options_form' => 1
        );

        $options_html .= PMA_URL_getHiddenInputs($url_params)
            . '<br />'
            . PMA_Util::getDivForSliderEffect(
                'displayoptions', __('Options')
            )
            . '<fieldset>';

        $options_html .= '<div class="formelement">';
        $choices = array(
            'P'   => __('Partial texts'),
            'F'   => __('Full texts')
        );

        // pftext means "partial or full texts" (done to reduce line lengths)
        $options_html .= PMA_Util::getRadioFields(
            'pftext', $choices,
            $_SESSION['tmpval']['pftext']
        )
        . '</div>';

        if ($GLOBALS['cfgRelation']['relwork']
            && $GLOBALS['cfgRelation']['displaywork']
        ) {
            $options_html .= '<div class="formelement">';
            $choices = array(
                'K'   => __('Relational key'),
                'D'   => __('Relational display column')
            );

            $options_html .= PMA_Util::getRadioFields(
                'relational_display', $choices,
                $_SESSION['tmpval']['relational_display']
            )
            . '</div>';
        }

        $options_html .= '<div class="formelement">'
            . PMA_Util::getCheckbox(
                'display_binary', __('Show binary contents'),
                ! empty($_SESSION['tmpval']['display_binary']), false
            )
            . '<br />'
            . PMA_Util::getCheckbox(
                'display_blob', __('Show BLOB contents'),
                ! empty($_SESSION['tmpval']['display_blob']), false
            )
            . '<br />'
            . PMA_Util::getCheckbox(
                'display_binary_as_hex', __('Show binary contents as HEX'),
                ! empty($_SESSION['tmpval']['display_binary_as_hex']), false
            )
            . '</div>';

        // I would have preferred to name this "display_transformation".
        // This is the only way I found to be able to keep this setting sticky
        // per SQL query, and at the same time have a default that displays
        // the transformations.
        $options_html .= '<div class="formelement">'
            . PMA_Util::getCheckbox(
                'hide_transformation', __('Hide browser transformation'),
                ! empty($_SESSION['tmpval']['hide_transformation']), false
            )
            . '</div>';

        if (! PMA_DRIZZLE) {
            $options_html .= '<div class="formelement">';
            $choices = array(
                'GEOM'  => __('Geometry'),
                'WKT'   => __('Well Known Text'),
                'WKB'   => __('Well Known Binary')
            );

            $options_html .= PMA_Util::getRadioFields(
                'geoOption', $choices,
                $_SESSION['tmpval']['geoOption']
            )
                . '</div>';
        }

        $options_html .= '<div class="clearfloat"></div>'
            . '</fieldset>';

        $options_html .= '<fieldset class="tblFooters">'
            . '<input type="submit" value="' . __('Go') . '" />'
            . '</fieldset>'
            . '</div>'
            . '</form>';

        return $options_html;

    } // end of the '_getOptionsBlock()' function


    /**
     * Get full/partial text button or link
     *
     * @return string html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getFullOrPartialTextButtonOrLink()
    {

        $url_params_full_text = array(
            'db' => $this->__get('db'),
            'table' => $this->__get('table'),
            'sql_query' => $this->__get('sql_query'),
            'goto' => $this->__get('goto'),
            'full_text_button' => 1
        );

        if ($_SESSION['tmpval']['pftext'] == self::DISPLAY_FULL_TEXT) {
            // currently in fulltext mode so show the opposite link
            $tmp_image_file = $this->__get('pma_theme_image') . 's_partialtext.png';
            $tmp_txt = __('Partial texts');
            $url_params_full_text['pftext'] = self::DISPLAY_PARTIAL_TEXT;
        } else {
            $tmp_image_file = $this->__get('pma_theme_image') . 's_fulltext.png';
            $tmp_txt = __('Full texts');
            $url_params_full_text['pftext'] = self::DISPLAY_FULL_TEXT;
        }

        $tmp_image = '<img class="fulltext" src="' . $tmp_image_file . '" alt="'
                     . $tmp_txt . '" title="' . $tmp_txt . '" />';
        $tmp_url = 'sql.php' . PMA_URL_getCommon($url_params_full_text);

        return PMA_Util::linkOrButton(
            $tmp_url, $tmp_image, array(), false
        );

    } // end of the '_getFullOrPartialTextButtonOrLink()' function


    /**
     * Prepare html form for multi row operations
     *
     * @param string $del_lnk the delete link of current row
     *
     * @return  string  $form_html          html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getFormForMultiRowOperations($del_lnk)
    {

        $form_html = '';

        if (($del_lnk == self::DELETE_ROW) || ($del_lnk == self::KILL_PROCESS)) {

            $form_html .= '<form method="post" action="tbl_row_action.php" '
                . 'name="resultsForm" id="resultsForm"';

            $form_html .= ' class="ajax" ';

            $form_html .= '>'
                . PMA_URL_getHiddenInputs(
                    $this->__get('db'), $this->__get('table'), 1
                )
                . '<input type="hidden" name="goto" value="sql.php" />';
        }

        $form_html .= '<table id="table_results" class="data';
        $form_html .= ' ajax';
        $form_html .= '">';

        return $form_html;

    } // end of the '_getFormForMultiRowOperations()' function


    /**
     * Get comment for row
     *
     * @param array $comments_map comments array
     * @param array $fields_meta  set of field properties
     *
     * @return  string  $comment        html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getCommentForRow($comments_map, $fields_meta)
    {
        $comments = '';
        if (isset($comments_map)
            && isset($comments_map[$fields_meta->table])
            && isset($comments_map[$fields_meta->table][$fields_meta->name])
        ) {
            $sanitized_comments = htmlspecialchars(
                $comments_map[$fields_meta->table][$fields_meta->name]
            );

            $comments = '<span class="tblcomment" title="'
                . $sanitized_comments . '">';
            $limitChars = $GLOBALS['cfg']['LimitChars'];
            if ($GLOBALS['PMA_String']->strlen($sanitized_comments) > $limitChars) {
                $sanitized_comments = $GLOBALS['PMA_String']->substr(
                    $sanitized_comments, 0, $limitChars
                ) . '…';
            }
            $comments .= $sanitized_comments;
            $comments .= '</span>';
        }
        return $comments;
    } // end of the '_getCommentForRow()' function


    /**
     * Prepare parameters and html for sorted table header fields
     *
     * @param array   $fields_meta                 set of field properties
     * @param array   $sort_expression             sort expression
     * @param array   $sort_expression_nodirection sort expression without direction
     * @param integer $column_index                the index of the column
     * @param string  $unsorted_sql_query          the unsorted sql query
     * @param integer $session_max_rows            maximum rows resulted by sql
     * @param string  $direction                   the display direction
     * @param string  $comments                    comment for row
     * @param array   $sort_direction              sort direction
     * @param boolean $directionCondition          display direction horizontal
     *                                             or horizontalflipped
     * @param boolean $col_visib                   column is visible(false)
     *        array                                column isn't visible(string array)
     * @param string  $col_visib_j                 element of $col_visib array
     *
     * @return  array   2 element array - $order_link, $sorted_header_html
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getOrderLinkAndSortedHeaderHtml(
        $fields_meta, $sort_expression, $sort_expression_nodirection,
        $column_index, $unsorted_sql_query, $session_max_rows, $direction,
        $comments, $sort_direction, $directionCondition, $col_visib, $col_visib_j
    ) {

        $sorted_header_html = '';

        // Checks if the table name is required; it's the case
        // for a query with a "JOIN" statement and if the column
        // isn't aliased, or in queries like
        // SELECT `1`.`master_field` , `2`.`master_field`
        // FROM `PMA_relation` AS `1` , `PMA_relation` AS `2`

        $sort_tbl = (isset($fields_meta->table)
            && strlen($fields_meta->table))
            ? PMA_Util::backquote(
                $fields_meta->table
            ) . '.'
            : '';

        $name_to_use_in_sort = $fields_meta->name;

        // Generates the orderby clause part of the query which is part
        // of URL
        list($single_sort_order, $multi_sort_order, $order_img) = $this->_getSingleAndMultiSortUrls(
            $sort_expression, $sort_expression_nodirection, $sort_tbl,
            $name_to_use_in_sort, $sort_direction, $fields_meta, $column_index
        );

        if (preg_match(
            '@(.*)([[:space:]](LIMIT (.*)|PROCEDURE (.*)|FOR UPDATE|'
            . 'LOCK IN SHARE MODE))@is',
            $unsorted_sql_query, $regs3
        )) {
            $single_sorted_sql_query = $regs3[1] . $single_sort_order . $regs3[2];
            $multi_sorted_sql_query = $regs3[1] . $multi_sort_order . $regs3[2];
        } else {
            $single_sorted_sql_query = $unsorted_sql_query . $single_sort_order;
            $multi_sorted_sql_query = $unsorted_sql_query . $multi_sort_order;
        }

        $_single_url_params = array(
            'db'                => $this->__get('db'),
            'table'             => $this->__get('table'),
            'sql_query'         => $single_sorted_sql_query,
            'session_max_rows'  => $session_max_rows
        );

        $_multi_url_params = array(
            'db'                => $this->__get('db'),
            'table'             => $this->__get('table'),
            'sql_query'         => $multi_sorted_sql_query,
            'session_max_rows'  => $session_max_rows
        );
        $single_order_url  = 'sql.php' . PMA_URL_getCommon($_single_url_params);
        $multi_order_url = 'sql.php' . PMA_URL_getCommon($_multi_url_params);

        // Displays the sorting URL
        // enable sort order swapping for image
        $order_link = $this->_getSortOrderLink(
            $order_img, $column_index, $direction,
            $fields_meta, $single_order_url, $multi_order_url
        );

        $sorted_header_html .= $this->_getDraggableClassForSortableColumns(
            $col_visib, $col_visib_j, $direction,
            $fields_meta, $order_link, $comments
        );

        return array($order_link, $sorted_header_html);

    } // end of the '_getOrderLinkAndSortedHeaderHtml()' function

    /**
     * Prepare parameters and html for sorted table header fields
     *
     * @param array   $sort_expression             sort expression
     * @param array   $sort_expression_nodirection sort expression without direction
     * @param string  $sort_tbl                    The name of the table to which
     *                                             the current column belongs to
     * @param string  $name_to_use_in_sort         The current column under
     *                                             consideration
     * @param array   $sort_direction              sort direction
     * @param array   $fields_meta                 set of field properties
     * @param integer $column_index                The index number to current column
     *
     * @return  array   3 element array - $single_sort_order, $sort_order, $order_img
     *
     * @access  private
     *
     * @see     _getOrderLinkAndSortedHeaderHtml()
     */
    private function _getSingleAndMultiSortUrls(
        $sort_expression, $sort_expression_nodirection, $sort_tbl,
        $name_to_use_in_sort, $sort_direction, $fields_meta, $column_index
    ) {
        $sort_order = "";
        // Check if the current column is in the order by clause
        $is_in_sort = $this->_isInSorted(
            $sort_expression, $sort_expression_nodirection,
            $sort_tbl, $name_to_use_in_sort
        );
        $current_name = $name_to_use_in_sort;
        if ($sort_expression_nodirection[0] == '' || !$is_in_sort) {
            $special_index = $sort_expression_nodirection[0] == ''
                ? 0
                : count($sort_expression_nodirection);
            $sort_expression_nodirection[$special_index]
                = PMA_Util::backquote(
                    $current_name
                );
            $sort_direction[$special_index] = (preg_match(
                '@time|date@i',
                $fields_meta->type
            )) ? self::DESCENDING_SORT_DIR : self::ASCENDING_SORT_DIR;

        }
        $sort_expression_nodirection = array_filter($sort_expression_nodirection);
        foreach ($sort_expression_nodirection as $index=>$expression) {
            // check if this is the first clause,
            // if it is then we have to add "order by"
            $is_first_clause = ($index == 0);
            $name_to_use_in_sort = $expression;
            $sort_tbl_new = $sort_tbl;
            // Test to detect if the column name is a standard name
            // Standard name has the table name prefixed to the column name
            $is_standard_name = false;
            if (strpos($name_to_use_in_sort, '.') !== false) {
                $matches = explode('.', $name_to_use_in_sort);
                // Matches[0] has the table name
                // Matches[1] has the column name
                $name_to_use_in_sort = $matches[1];
                $sort_tbl_new = $matches[0];
                $is_standard_name = true;
            }


            // $name_to_use_in_sort might contain a space due to
            // formatting of function expressions like "COUNT(name )"
            // so we remove the space in this situation
            $name_to_use_in_sort = str_replace(' )', ')', $name_to_use_in_sort);
            $name_to_use_in_sort = str_replace('`', '', $name_to_use_in_sort);

            // If this the first column name in the order by clause add
            // order by clause to the  column name
            $query_head = $is_first_clause ? "\nORDER BY " : "";
            $tbl = $is_standard_name ? $sort_tbl_new : $sort_tbl;
            // Again a check to see if the given column is a aggregate column
            if (strpos($name_to_use_in_sort, '(') !== false) {
                $sort_order .=  $query_head  . $name_to_use_in_sort . ' ' ;
            } else {
                $sort_order .=  $query_head  . $sort_tbl_new . "."
                  . PMA_Util::backquote(
                      $name_to_use_in_sort
                  ) .  ' ' ;
            }

            // For a special case where the code generates two dots between
            // column name and table name.
            $sort_order = preg_replace("/\.\./", ".", $sort_order);
            // Incase this is the current column save $single_sort_order
            if ($current_name == $name_to_use_in_sort) {
                if (strpos($current_name, '(') !== false) {
                    $single_sort_order = "\n" . 'ORDER BY ' . $current_name . ' ';
                } else {
                    $single_sort_order = "\n" . 'ORDER BY ' . $sort_tbl
                        . PMA_Util::backquote(
                            $current_name
                        ) . ' ';
                }
                if ($is_in_sort) {
                    list($single_sort_order, $order_img) = $this->_getSortingUrlParams(
                        $sort_direction, $single_sort_order, $column_index, $index
                    );
                } else {
                    $single_sort_order .= strtoupper($sort_direction[$index]);
                }
            }
            if ($current_name == $name_to_use_in_sort && $is_in_sort) {
                // We need to generate the arrow button and related html
                list($sort_order, $order_img) = $this->_getSortingUrlParams(
                    $sort_direction, $sort_order, $column_index, $index
                );
                $order_img .= " <small>" . ($index + 1) . "</small>";
            } else {
                $sort_order .= strtoupper($sort_direction[$index]);
            }
            // Separate columns by a comma
            $sort_order .= ", ";

            unset($name_to_use_in_sort);
        }
        // remove the comma from the last column name in the newly
        // constructed clause
        $sort_order = substr($sort_order, 0, strlen($sort_order)-2);
        if (empty($order_img)) {
            $order_img = '';
        }
        return array($single_sort_order, $sort_order, $order_img);
    }

    /**
     * Check whether the column is sorted
     *
     * @param array  $sort_expression             sort expression
     * @param array  $sort_expression_nodirection sort expression without direction
     * @param string $sort_tbl                    the table name
     * @param string $name_to_use_in_sort         the sorting column name
     *
     * @return boolean $is_in_sort                   the column sorted or not
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _isInSorted(
        $sort_expression, $sort_expression_nodirection, $sort_tbl,
        $name_to_use_in_sort
    ) {

        $index_in_expression = 0;

        foreach ($sort_expression_nodirection as $index => $clause) {
            if (strpos($clause, '.') !== false) {
                $fragments = explode('.', $clause);
                $clause2 = $fragments[0] . "." . str_replace('`', '', $fragments[1]);
            } else {
                $clause2 = $sort_tbl . str_replace('`', '', $clause);
            }
            if ($clause2 === $sort_tbl . $name_to_use_in_sort) {
                $index_in_expression = $index;
                break;
            }
        }
        if (empty($sort_expression[$index_in_expression])) {
            $is_in_sort = false;
        } else {
            // Field name may be preceded by a space, or any number
            // of characters followed by a dot (tablename.fieldname)
            // so do a direct comparison for the sort expression;
            // this avoids problems with queries like
            // "SELECT id, count(id)..." and clicking to sort
            // on id or on count(id).
            // Another query to test this:
            // SELECT p.*, FROM_UNIXTIME(p.temps) FROM mytable AS p
            // (and try clicking on each column's header twice)
            if (! empty($sort_tbl)
                && strpos($sort_expression_nodirection[$index_in_expression], $sort_tbl) === false
                && strpos($sort_expression_nodirection[$index_in_expression], '(') === false
            ) {
                $new_sort_expression_nodirection = $sort_tbl
                    . $sort_expression_nodirection[$index_in_expression];
            } else {
                $new_sort_expression_nodirection
                    = $sort_expression_nodirection[$index_in_expression];
            }

            //Back quotes are removed in next comparison, so remove them from value
            //to compare.
            $name_to_use_in_sort = str_replace('`', '', $name_to_use_in_sort);

            $is_in_sort = false;
            $sort_name = str_replace('`', '', $sort_tbl) . $name_to_use_in_sort;

            if ($sort_name == str_replace('`', '', $new_sort_expression_nodirection)
                || $sort_name == str_replace('`', '', $sort_expression_nodirection[$index_in_expression])
            ) {
                $is_in_sort = true;
            }
        }

        return $is_in_sort;

    } // end of the '_isInSorted()' function


    /**
     * Get sort url paramaeters - sort order and order image
     *
     * @param array   $sort_direction the sort direction
     * @param string  $sort_order     the sorting order
     * @param integer $column_index   the index of the column
     * @param integer $index          the index of sort direction array.
     *
     * @return  array                       2 element array - $sort_order, $order_img
     *
     * @access  private
     *
     * @see     _getSingleAndMultiSortUrls()
     */
    private function _getSortingUrlParams(
        $sort_direction, $sort_order, $column_index, $index
    ) {
        if (strtoupper(trim($sort_direction[$index])) ==  self::DESCENDING_SORT_DIR) {
            $sort_order .= ' ASC';
            $order_img   = ' ' . PMA_Util::getImage(
                's_desc.png', __('Descending'),
                array('class' => "soimg$column_index", 'title' => '')
            );
            $order_img  .= ' ' . PMA_Util::getImage(
                's_asc.png', __('Ascending'),
                array('class' => "soimg$column_index hide", 'title' => '')
            );
        } else {
            $sort_order .= ' DESC';
            $order_img   = ' ' . PMA_Util::getImage(
                's_asc.png', __('Ascending'),
                array('class' => "soimg$column_index", 'title' => '')
            );
            $order_img  .=  ' ' . PMA_Util::getImage(
                's_desc.png', __('Descending'),
                array('class' => "soimg$column_index hide", 'title' => '')
            );
        }
        return array($sort_order, $order_img);
    } // end of the '_getSortingUrlParams()' function


    /**
     * Get sort order link
     *
     * @param string  $order_img   the sort order image
     * @param integer $col_index   the index of the column
     * @param string  $direction   the display direction
     * @param array   $fields_meta set of field properties
     * @param string  $order_url   the url for sort
     * @param string  $multi_order_url   the url for sort
     *
     * @return  string                      the sort order link
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getSortOrderLink(
        $order_img, $col_index, $direction, $fields_meta, $order_url, $multi_order_url
    ) {

        $order_link_params = array();
        if (isset($order_img) && ($order_img != '')) {
            if (strstr($order_img, 'asc')) {
                $order_link_params['onmouseover'] = "$('.soimg$col_index').toggle()";
                $order_link_params['onmouseout']  = "$('.soimg$col_index').toggle()";
            } elseif (strstr($order_img, 'desc')) {
                $order_link_params['onmouseover'] = "$('.soimg$col_index').toggle()";
                $order_link_params['onmouseout']  = "$('.soimg$col_index').toggle()";
            }
        }

        if ($GLOBALS['cfg']['HeaderFlipType'] == self::HEADER_FLIP_TYPE_AUTO) {

            $GLOBALS['cfg']['HeaderFlipType']
                = (PMA_USR_BROWSER_AGENT == 'IE')
                ? self::HEADER_FLIP_TYPE_CSS
                : self::HEADER_FLIP_TYPE_FAKE;
        }

        if ($direction == self::DISP_DIR_HORIZONTAL_FLIPPED
            && $GLOBALS['cfg']['HeaderFlipType'] == self::HEADER_FLIP_TYPE_CSS
        ) {
            $order_link_params['style'] = 'direction: ltr; writing-mode: tb-rl;';
        }

        $order_link_content = (($direction == self::DISP_DIR_HORIZONTAL_FLIPPED)
            && ($GLOBALS['cfg']['HeaderFlipType'] == self::HEADER_FLIP_TYPE_FAKE))
            ? PMA_Util::flipstring(
                htmlspecialchars($fields_meta->name),
                "<br />\n"
            )
            : htmlspecialchars($fields_meta->name);
        $inner_link_content = $order_link_content . $order_img
            . '<input type="hidden" value="' .  $multi_order_url . '" />';

        return PMA_Util::linkOrButton(
            $order_url, $inner_link_content,
            $order_link_params, false, true
        );

    } // end of the '_getSortOrderLink()' function


    /**
     * Prepare columns to draggable effect for sortable columns
     *
     * @param boolean $col_visib   the column is visible (false)
     *        array                the column is not visible (string array)
     * @param string  $col_visib_j element of $col_visib array
     * @param string  $direction   the display direction
     * @param array   $fields_meta set of field properties
     * @param string  $order_link  the order link
     * @param string  $comments    the comment for the column
     *
     * @return  string  $draggable_html     html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getDraggableClassForSortableColumns(
        $col_visib, $col_visib_j, $direction, $fields_meta,
        $order_link, $comments
    ) {

        $draggable_html = '<th';
        $th_class = array();
        $th_class[] = 'draggable';

        if ($col_visib && !$col_visib_j) {
            $th_class[] = 'hide';
        }

        $th_class[] = 'column_heading';
        if ($GLOBALS['cfg']['BrowsePointerEnable'] == true) {
            $th_class[] = 'pointer';
        }

        if ($GLOBALS['cfg']['BrowseMarkerEnable'] == true) {
            $th_class[] = 'marker';
        }

        $draggable_html .= ' class="' . implode(' ', $th_class);

        if ($direction == self::DISP_DIR_HORIZONTAL_FLIPPED) {
            $draggable_html .= ' vbottom';
        }

        $draggable_html .= '" data-column="' . htmlspecialchars($fields_meta->name)
            . '">' . $order_link . $comments . '</th>';

        return $draggable_html;

    } // end of the '_getDraggableClassForSortableColumns()' function


    /**
     * Prepare columns to draggable effect for non sortable columns
     *
     * @param boolean $col_visib       the column is visible (false)
     *        array                    the column is not visible (string array)
     * @param string  $col_visib_j     element of $col_visib array
     * @param boolean $condition_field whether to add CSS class condition
     * @param string  $direction       the display direction
     * @param array   $fields_meta     set of field properties
     * @param string  $comments        the comment for the column
     *
     * @return  string  $draggable_html         html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getDraggableClassForNonSortableColumns(
        $col_visib, $col_visib_j, $condition_field,
        $direction, $fields_meta, $comments
    ) {

        $draggable_html = '<th';
        $th_class = array();
        $th_class[] = 'draggable';

        if ($col_visib && !$col_visib_j) {
            $th_class[] = 'hide';
        }

        if ($condition_field) {
            $th_class[] = 'condition';
        }

        $draggable_html .= ' class="' . implode(' ', $th_class);
        if ($direction == self::DISP_DIR_HORIZONTAL_FLIPPED) {
            $draggable_html .= ' vbottom';
        }

        $draggable_html .= '"';
        if (($direction == self::DISP_DIR_HORIZONTAL_FLIPPED)
            && ($GLOBALS['cfg']['HeaderFlipType'] == self::HEADER_FLIP_TYPE_CSS)
        ) {
            $draggable_html .= ' style="direction: ltr; writing-mode: tb-rl;"';
        }

        $draggable_html .= ' data-column="'
            . htmlspecialchars($fields_meta->name) . '">';

        if (($direction == self::DISP_DIR_HORIZONTAL_FLIPPED)
            && ($GLOBALS['cfg']['HeaderFlipType'] == self::HEADER_FLIP_TYPE_FAKE)
        ) {

            $draggable_html .= PMA_Util::flipstring(
                htmlspecialchars($fields_meta->name), '<br />'
            );

        } else {
            $draggable_html .= htmlspecialchars($fields_meta->name);
        }

        $draggable_html .= "\n" . $comments . '</th>';

        return $draggable_html;

    } // end of the '_getDraggableClassForNonSortableColumns()' function


    /**
     * Prepare column to show at right side - check boxes or empty column
     *
     * @param array   &$is_display               which elements to display
     * @param boolean $directionCondition        display direction horizontal
     *                                           or horizontalflipped
     * @param string  $full_or_partial_text_link full/partial link or text button
     * @param string  $colspan                   column span of table header
     * @param string  $rowspan                   row span of table header
     *
     * @return  string  html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getColumnAtRightSide(
        &$is_display, $directionCondition, $full_or_partial_text_link,
        $colspan, $rowspan
    ) {

        $right_column_html = '';
        $vertical_display = $this->__get('vertical_display');

        // Displays the needed checkboxes at the right
        // column of the result table header if possible and required...
        if ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_RIGHT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
            || ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE))
            && ($is_display['text_btn'] == '1')
        ) {

            $vertical_display['emptyafter']
                = (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE)) ? 4 : 1;

            if ($directionCondition) {
                $right_column_html .= "\n"
                    . '<th ' . $colspan . '>' . $full_or_partial_text_link
                    . '</th>';

                // end horizontal/horizontalflipped mode
            } else {
                $vertical_display['textbtn'] = '    <th ' . $rowspan
                    . ' class="vmiddle">' . "\n"
                    . '        ' . "\n"
                    . '    </th>' . "\n";
            } // end vertical mode
        } elseif ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_LEFT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && (($is_display['edit_lnk'] == self::NO_EDIT_OR_DELETE)
            && ($is_display['del_lnk'] == self::NO_EDIT_OR_DELETE))
            && (! isset($GLOBALS['is_header_sent']) || ! $GLOBALS['is_header_sent'])
        ) {
            //     ... elseif no button, displays empty columns if required
            // (unless coming from Browse mode print view)

            $vertical_display['emptyafter']
                = (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE)) ? 4 : 1;

            if ($directionCondition) {
                $right_column_html .= "\n"
                    . '<td ' . $colspan . '></td>';

                // end horizontal/horizontalflipped mode
            } else {
                $vertical_display['textbtn'] = '    <td' . $rowspan
                    . '></td>' . "\n";
            } // end vertical mode
        }

        $this->__set('vertical_display', $vertical_display);

        return $right_column_html;

    } // end of the '_getColumnAtRightSide()' function


    /**
     * Prepares the display for a value
     *
     * @param string $class           class of table cell
     * @param bool   $condition_field whether to add CSS class condition
     * @param string $value           value to display
     *
     * @return string  the td
     *
     * @access  private
     *
     * @see     _getDataCellForBlobColumns(), _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericAndNonBlobColumns()
     */
    private function _buildValueDisplay($class, $condition_field, $value)
    {
        return '<td class="left ' . $class . ($condition_field ? ' condition' : '')
            . '">' . $value . '</td>';
    } // end of the '_buildValueDisplay()' function


    /**
     * Prepares the display for a null value
     *
     * @param string $class           class of table cell
     * @param bool   $condition_field whether to add CSS class condition
     * @param object $meta            the meta-information about this field
     * @param string $align           cell allignment
     *
     * @return string  the td
     *
     * @access  private
     *
     * @see     _getDataCellForNumericColumns(), _getDataCellForBlobColumns(),
     *          _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericAndNonBlobColumns()
     */
    private function _buildNullDisplay($class, $condition_field, $meta, $align = '')
    {
        // the null class is needed for grid editing
        return '<td ' . $align . ' data-decimals="' . $meta->decimals
            . '" data-type="' . $meta->type . '"  class="'
            . $this->_addClass(
                $class, $condition_field, $meta, ''
            )
            . ' null"><i>NULL</i></td>';
    } // end of the '_buildNullDisplay()' function


    /**
     * Prepares the display for an empty value
     *
     * @param string $class           class of table cell
     * @param bool   $condition_field whether to add CSS class condition
     * @param object $meta            the meta-information about this field
     * @param string $align           cell allignment
     *
     * @return string  the td
     *
     * @access  private
     *
     * @see     _getDataCellForNumericColumns(), _getDataCellForBlobColumns(),
     *          _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericAndNonBlobColumns()
     */
    private function _buildEmptyDisplay($class, $condition_field, $meta, $align = '')
    {
        return '<td ' . $align . ' class="'
            . $this->_addClass(
                $class, $condition_field, $meta, ' nowrap'
            )
            . '"></td>';
    } // end of the '_buildEmptyDisplay()' function


    /**
     * Adds the relevant classes.
     *
     * @param string $class                 class of table cell
     * @param bool   $condition_field       whether to add CSS class condition
     * @param object $meta                  the meta-information about the field
     * @param string $nowrap                avoid wrapping
     * @param bool   $is_field_truncated    is field truncated (display ...)
     * @param string $transformation_plugin transformation plugin.
     *                                      Can also be the default function:
     *                                      PMA_mimeDefaultFunction
     * @param string $default_function      default transformation function
     *
     * @return string the list of classes
     *
     * @access  private
     *
     * @see     _buildNullDisplay(), _getRowData()
     */
    private function _addClass(
        $class, $condition_field, $meta, $nowrap, $is_field_truncated = false,
        $transformation_plugin = '', $default_function = ''
    ) {

        // Define classes to be added to this data field based on the type of data
        $enum_class = '';
        if (strpos($meta->flags, 'enum') !== false) {
            $enum_class = ' enum';
        }

        $set_class = '';
        if (strpos($meta->flags, 'set') !== false) {
            $set_class = ' set';
        }

        $bit_class = '';
        if (strpos($meta->type, 'bit') !== false) {
            $bit_class = ' bit';
        }

        $mime_type_class = '';
        if (isset($meta->mimetype)) {
            $mime_type_class = ' ' . preg_replace('/\//', '_', $meta->mimetype);
        }

        return $class . ($condition_field ? ' condition' : '') . $nowrap
            . ' ' . ($is_field_truncated ? ' truncated' : '')
            . ($transformation_plugin != $default_function ? ' transformed' : '')
            . $enum_class . $set_class . $bit_class . $mime_type_class;

    } // end of the '_addClass()' function


    /**
     * Prepare the body of the results table
     *
     * @param integer &$dt_result         the link id associated to the query
     *                                    which results have to be displayed
     * @param array   &$is_display        which elements to display
     * @param array   $map                the list of relations
     * @param array   $analyzed_sql       the analyzed query
     * @param boolean $is_limited_display with limited operations or not
     *
     * @return string $table_body_html  html content
     *
     * @global array   $row             current row data
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _getTableBody(
        &$dt_result, &$is_display, $map, $analyzed_sql, $is_limited_display = false
    ) {

        global $row; // mostly because of browser transformations,
                     // to make the row-data accessible in a plugin

        $table_body_html = '';

        // query without conditions to shorten URLs when needed, 200 is just
        // guess, it should depend on remaining URL length
        $url_sql_query = $this->_getUrlSqlQuery($analyzed_sql);

        $vertical_display = $this->__get('vertical_display');

        if (! is_array($map)) {
            $map = array();
        }

        $row_no                         = 0;
        $vertical_display['edit']       = array();
        $vertical_display['copy']       = array();
        $vertical_display['delete']     = array();
        $vertical_display['data']       = array();
        $vertical_display['row_delete'] = array();
        $this->__set('vertical_display', $vertical_display);

        // name of the class added to all grid editable elements;
        // if we don't have all the columns of a unique key in the result set,
        //  do not permit grid editing
        if ($is_limited_display || ! $this->__get('editable')) {
            $grid_edit_class = '';
        } else {
            switch ($GLOBALS['cfg']['GridEditing']) {
            case 'double-click':
                // trying to reduce generated HTML by using shorter
                // classes like click1 and click2
                $grid_edit_class = 'grid_edit click2';
                break;
            case 'click':
                $grid_edit_class = 'grid_edit click1';
                break;
            case 'disabled':
                $grid_edit_class = '';
                break;
            }
        }

        // prepare to get the column order, if available
        list($col_order, $col_visib) = $this->_getColumnParams($analyzed_sql);

        // Correction University of Virginia 19991216 in the while below
        // Previous code assumed that all tables have keys, specifically that
        // the phpMyAdmin GUI should support row delete/edit only for such
        // tables.
        // Although always using keys is arguably the prescribed way of
        // defining a relational table, it is not required. This will in
        // particular be violated by the novice.
        // We want to encourage phpMyAdmin usage by such novices. So the code
        // below has been changed to conditionally work as before when the
        // table being displayed has one or more keys; but to display
        // delete/edit options correctly for tables without keys.

        $odd_row = true;
        $directionCondition
            = ($_SESSION['tmpval']['disp_direction']
                == self::DISP_DIR_HORIZONTAL)
            || ($_SESSION['tmpval']['disp_direction']
                == self::DISP_DIR_HORIZONTAL_FLIPPED);

        while ($row = $GLOBALS['dbi']->fetchRow($dt_result)) {

            // "vertical display" mode stuff
            $table_body_html .= $this->_getVerticalDisplaySupportSegments(
                $vertical_display, $row_no, $directionCondition
            );

            $alternating_color_class = ($odd_row ? 'odd' : 'even');
            $odd_row = ! $odd_row;

            if ($directionCondition) {
                // pointer code part
                $table_body_html .= '<tr class="' . $alternating_color_class . '">';
            }

            // 1. Prepares the row
            // 1.1 Results from a "SELECT" statement -> builds the
            //     WHERE clause to use in links (a unique key if possible)
            /**
             * @todo $where_clause could be empty, for example a table
             *       with only one field and it's a BLOB; in this case,
             *       avoid to display the delete and edit links
             */
            list($where_clause, $clause_is_unique, $condition_array)
                = PMA_Util::getUniqueCondition(
                    $dt_result,
                    $this->__get('fields_cnt'),
                    $this->__get('fields_meta'),
                    $row
                );
            $where_clause_html = urlencode($where_clause);

            // In print view these variable needs toinitialized
            $del_url = $del_query = $del_str = $edit_anchor_class
                = $edit_str = $js_conf = $copy_url = $copy_str = $edit_url = null;

            // 1.2 Defines the URLs for the modify/delete link(s)

            if (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                || ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE)
            ) {
                // We need to copy the value
                // or else the == 'both' check will always return true

                if ($GLOBALS['cfg']['ActionLinksMode'] === self::POSITION_BOTH) {
                    $iconic_spacer = '<div class="nowrap">';
                } else {
                    $iconic_spacer = '';
                }

                // 1.2.1 Modify link(s) - update row case
                if ($is_display['edit_lnk'] == self::UPDATE_ROW) {

                    list($edit_url, $copy_url, $edit_str, $copy_str,
                        $edit_anchor_class)
                            = $this->_getModifiedLinks(
                                $where_clause,
                                $clause_is_unique, $url_sql_query
                            );

                } // end if (1.2.1)

                // 1.2.2 Delete/Kill link(s)
                if (($is_display['del_lnk'] == self::DELETE_ROW)
                    || ($is_display['del_lnk'] == self::KILL_PROCESS)
                ) {

                    list($del_query, $del_url, $del_str, $js_conf)
                        = $this->_getDeleteAndKillLinks(
                            $where_clause, $clause_is_unique,
                            $url_sql_query, $is_display['del_lnk'],
                            $row
                        );

                } // end if (1.2.2)

                // 1.3 Displays the links at left if required
                if ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_LEFT)
                    || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
                    && $directionCondition
                ) {

                    $table_body_html .= $this->_getPlacedLinks(
                        self::POSITION_LEFT, $del_url, $is_display, $row_no,
                        $where_clause, $where_clause_html, $condition_array,
                        $del_query, 'l', $edit_url, $copy_url, $edit_anchor_class,
                        $edit_str, $copy_str, $del_str, $js_conf
                    );

                } elseif (($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_NONE)
                    && $directionCondition
                ) {

                    $table_body_html .= $this->_getPlacedLinks(
                        self::POSITION_NONE, $del_url, $is_display, $row_no,
                        $where_clause, $where_clause_html, $condition_array,
                        $del_query, 'l', $edit_url, $copy_url, $edit_anchor_class,
                        $edit_str, $copy_str, $del_str, $js_conf
                    );

                } // end if (1.3)
            } // end if (1)

            // 2. Displays the rows' values
            $table_body_html .= $this->_getRowValues(
                $dt_result, $row, $row_no, $col_order, $map,
                $grid_edit_class, $col_visib, $where_clause,
                $url_sql_query, $analyzed_sql, $directionCondition
            );

            // 3. Displays the modify/delete links on the right if required
            if ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_RIGHT)
                || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
                && $directionCondition
            ) {

                $table_body_html .= $this->_getPlacedLinks(
                    self::POSITION_RIGHT, $del_url, $is_display, $row_no,
                    $where_clause, $where_clause_html, $condition_array,
                    $del_query, 'r', $edit_url, $copy_url, $edit_anchor_class,
                    $edit_str, $copy_str, $del_str, $js_conf
                );

            } // end if (3)

            if ($directionCondition) {
                $table_body_html .= '</tr>';
            } // end if

            // 4. Gather links of del_urls and edit_urls in an array for later
            //    output
            $this->_gatherLinksForLaterOutputs(
                $row_no, $is_display, $where_clause, $where_clause_html, $js_conf,
                $del_url, $del_query, $del_str, $edit_anchor_class, $edit_url,
                $edit_str, $copy_url, $copy_str, $alternating_color_class,
                $condition_array
            );

            $table_body_html .= $directionCondition ? "\n" : '';
            $row_no++;

        } // end while

        return $table_body_html;

    } // end of the '_getTableBody()' function


    /**
     * Get the values for one data row
     *
     * @param integer &$dt_result         the link id associated to the query
     *                                    which results have to be displayed
     * @param array   $row                current row data
     * @param integer $row_no             the index of current row
     * @param array   $col_order          the column order
     *                                    false when a property not found
     * @param array   $map                the list of relations
     * @param string  $grid_edit_class    the class for all editable columns
     * @param boolean $col_visib          column is visible(false)
     *        array                       column isn't visible(string array)
     * @param string  $where_clause       where clause
     * @param string  $url_sql_query      the analyzed sql query
     * @param array   $analyzed_sql       the analyzed query
     * @param boolean $directionCondition the directional condition
     *
     * @return  string $row_values_html  html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getRowValues(
        &$dt_result, $row, $row_no, $col_order, $map,
        $grid_edit_class, $col_visib, $where_clause,
        $url_sql_query, $analyzed_sql, $directionCondition
    ) {

        $row_values_html = '';

        // Following variable are needed for use in isset/empty or
        // use with array indexes/safe use in foreach
        $sql_query = $this->__get('sql_query');
        $fields_meta = $this->__get('fields_meta');
        $highlight_columns = $this->__get('highlight_columns');
        $mime_map = $this->__get('mime_map');

        $row_info = $this->_getRowInfoForSpecialLinks($row, $col_order);

        for ($currentColumn = 0;
                $currentColumn < $this->__get('fields_cnt');
                ++$currentColumn) {

            // assign $i with appropriate column order
            $i = $col_order ? $col_order[$currentColumn] : $currentColumn;

            $meta    = $fields_meta[$i];
            $not_null_class = $meta->not_null ? 'not_null' : '';
            $relation_class = isset($map[$meta->name]) ? 'relation' : '';
            $hide_class = ($col_visib && ! $col_visib[$currentColumn]
                // hide per <td> only if the display dir is not vertical
                && ($_SESSION['tmpval']['disp_direction']
                    != self::DISP_DIR_VERTICAL))
                ? 'hide'
                : '';

            // handle datetime-related class, for grid editing
            $field_type_class
                = $this->_getClassForDateTimeRelatedFields($meta->type);

            $is_field_truncated = false;
            // combine all the classes applicable to this column's value
            $class = $this->_getClassesForColumn(
                $grid_edit_class, $not_null_class, $relation_class,
                $hide_class, $field_type_class, $row_no
            );

            //  See if this column should get highlight because it's used in the
            //  where-query.
            $condition_field = (isset($highlight_columns)
                && (isset($highlight_columns[$meta->name])
                || isset($highlight_columns[PMA_Util::backquote($meta->name)])))
                ? true
                : false;

            // Wrap MIME-transformations. [MIME]
            $default_function = '_mimeDefaultFunction'; // default_function
            $transformation_plugin = $default_function;
            $transform_options = array();

            if ($GLOBALS['cfgRelation']['mimework']
                && $GLOBALS['cfg']['BrowseMIME']
            ) {

                if (isset($mime_map[$meta->name]['mimetype'])
                    && isset($mime_map[$meta->name]['transformation'])
                    && !empty($mime_map[$meta->name]['transformation'])
                ) {

                    $file = $mime_map[$meta->name]['transformation'];
                    $include_file = 'libraries/plugins/transformations/' . $file;

                    if (file_exists($include_file)) {

                        include_once $include_file;
                        $class_name = str_replace('.class.php', '', $file);
                        // todo add $plugin_manager
                        $plugin_manager = null;
                        $transformation_plugin = new $class_name(
                            $plugin_manager
                        );

                        $transform_options  = PMA_Transformation_getOptions(
                            isset($mime_map[$meta->name]
                                ['transformation_options']
                            )
                            ? $mime_map[$meta->name]
                            ['transformation_options']
                            : ''
                        );

                        $meta->mimetype = str_replace(
                            '_', '/',
                            $mime_map[$meta->name]['mimetype']
                        );

                    } // end if file_exists
                } // end if transformation is set
            } // end if mime/transformation works.

            $_url_params = array(
                'db'            => $this->__get('db'),
                'table'         => $this->__get('table'),
                'where_clause'  => $where_clause,
                'transform_key' => $meta->name,
            );

            if (! empty($sql_query)) {
                $_url_params['sql_query'] = $url_sql_query;
            }

            $transform_options['wrapper_link']
                = PMA_URL_getCommon($_url_params);

            $vertical_display = $this->__get('vertical_display');

            // Check whether the field needs to display with syntax highlighting

            if (! empty($this->transformation_info[strtolower($this->__get('db'))][strtolower($this->__get('table'))][strtolower($meta->name)])
                && (trim($row[$i]) != '')
            ) {
                $row[$i] = PMA_Util::formatSql($row[$i]);
                include_once $this->transformation_info
                    [strtolower($this->__get('db'))]
                    [strtolower($this->__get('table'))]
                    [strtolower($meta->name)][0];
                $transformation_plugin = new $this->transformation_info
                    [strtolower($this->__get('db'))]
                    [strtolower($this->__get('table'))]
                    [strtolower($meta->name)][1](null);

                $transform_options  = PMA_Transformation_getOptions(
                    isset($mime_map[$meta->name]['transformation_options'])
                    ? $mime_map[$meta->name]['transformation_options']
                    : ''
                );

                $meta->mimetype = str_replace(
                    '_', '/',
                    $this->transformation_info[strtolower($this->__get('db'))]
                    [strtolower($this->__get('table'))]
                    [strtolower($meta->name)][2]
                );

            }

            // Check for the predefined fields need to show as link in schemas
            include_once 'libraries/special_schema_links.lib.php';

            if (isset($GLOBALS['special_schema_links'])
                && (! empty($GLOBALS['special_schema_links'][strtolower($this->__get('db'))][strtolower($this->__get('table'))][strtolower($meta->name)]))
            ) {

                $linking_url = $this->_getSpecialLinkUrl(
                    $row[$i], $row_info, strtolower($meta->name)
                );
                include_once
                    "libraries/plugins/transformations/Text_Plain_Link.class.php";
                $transformation_plugin = new Text_Plain_Link(null);

                $transform_options  = array(
                    0 => $linking_url,
                    2 => true
                );

                $meta->mimetype = str_replace(
                    '_', '/',
                    'Text/Plain'
                );

            }

            if ($meta->numeric == 1) {
                // n u m e r i c

                $vertical_display['data'][$row_no][$i]
                    = $this->_getDataCellForNumericColumns(
                        $row[$i], $class, $condition_field, $meta, $map,
                        $is_field_truncated, $analyzed_sql,
                        $transformation_plugin, $default_function,
                        $transform_options
                    );

            } elseif (stristr($meta->type, self::BLOB_FIELD)) {
                //  b l o b

                // PMA_mysql_fetch_fields returns BLOB in place of
                // TEXT fields type so we have to ensure it's really a BLOB
                $field_flags = $GLOBALS['dbi']->fieldFlags($dt_result, $i);

                $vertical_display['data'][$row_no][$i]
                    = $this->_getDataCellForBlobColumns(
                        $row[$i], $class, $meta, $_url_params, $field_flags,
                        $transformation_plugin, $default_function,
                        $transform_options, $condition_field, $is_field_truncated
                    );

            } elseif ($meta->type == self::GEOMETRY_FIELD) {
                // g e o m e t r y

                // Remove 'grid_edit' from $class as we do not allow to
                // inline-edit geometry data.
                $class = str_replace('grid_edit', '', $class);

                $vertical_display['data'][$row_no][$i]
                    = $this->_getDataCellForGeometryColumns(
                        $row[$i], $class, $meta, $map, $_url_params,
                        $condition_field, $transformation_plugin,
                        $default_function, $transform_options,
                        $is_field_truncated, $analyzed_sql
                    );

            } else {
                // n o t   n u m e r i c   a n d   n o t   B L O B

                $vertical_display['data'][$row_no][$i]
                    = $this->_getDataCellForNonNumericAndNonBlobColumns(
                        $row[$i], $class, $meta, $map, $_url_params,
                        $condition_field, $transformation_plugin,
                        $default_function, $transform_options,
                        $is_field_truncated, $analyzed_sql, $dt_result, $i
                    );

            }

            // output stored cell
            if ($directionCondition) {
                $row_values_html
                    .= $vertical_display['data'][$row_no][$i];
            }

            if (isset($vertical_display['rowdata'][$i][$row_no])) {
                $vertical_display['rowdata'][$i][$row_no]
                    .= $vertical_display['data'][$row_no][$i];
            } else {
                $vertical_display['rowdata'][$i][$row_no]
                    = $vertical_display['data'][$row_no][$i];
            }

            $this->__set('vertical_display', $vertical_display);

        } // end for

        return $row_values_html;

    } // end of the '_getRowValues()' function


    /**
     * Gather delete/edit url links for further outputs
     *
     * @param integer $row_no                  the index of current row
     * @param array   $is_display              which elements to display
     * @param string  $where_clause            where clause
     * @param string  $where_clause_html       the html encoded where clause
     * @param string  $js_conf                 text for the JS confirmation
     * @param string  $del_url                 the url for delete row
     * @param string  $del_query               the query for delete row
     * @param string  $del_str                 the label for delete row
     * @param string  $edit_anchor_class       the class for html element for edit
     * @param string  $edit_url                the url for edit row
     * @param string  $edit_str                the label for edit row
     * @param string  $copy_url                the url for copy row
     * @param string  $copy_str                the label for copy row
     * @param string  $alternating_color_class class for display two colors in rows
     * @param array   $condition_array         array of keys
     *                                         (primary,unique,condition)
     *
     * @return  void
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _gatherLinksForLaterOutputs(
        $row_no, $is_display, $where_clause, $where_clause_html, $js_conf,
        $del_url, $del_query, $del_str, $edit_anchor_class, $edit_url, $edit_str,
        $copy_url, $copy_str, $alternating_color_class, $condition_array
    ) {

        $vertical_display = $this->__get('vertical_display');

        if (! isset($vertical_display['edit'][$row_no])) {
            $vertical_display['edit'][$row_no]       = '';
            $vertical_display['copy'][$row_no]       = '';
            $vertical_display['delete'][$row_no]     = '';
            $vertical_display['row_delete'][$row_no] = '';
        }

        $vertical_class = ' row_' . $row_no;
        if ($GLOBALS['cfg']['BrowsePointerEnable'] == true) {
            $vertical_class .= ' vpointer';
        }

        if ($GLOBALS['cfg']['BrowseMarkerEnable'] == true) {
            $vertical_class .= ' vmarker';
        }

        if (!empty($del_url)
            && ($is_display['del_lnk'] != self::KILL_PROCESS)
        ) {

            $vertical_display['row_delete'][$row_no]
                .= $this->_getCheckboxForMultiRowSubmissions(
                    $del_url, $is_display, $row_no, $where_clause_html,
                    $condition_array, $del_query, '[%_PMA_CHECKBOX_DIR_%]',
                    $alternating_color_class . $vertical_class
                );

        } else {
            unset($vertical_display['row_delete'][$row_no]);
        }

        if (isset($edit_url)) {

            $vertical_display['edit'][$row_no] .= $this->_getEditLink(
                $edit_url,
                $alternating_color_class . ' ' . $edit_anchor_class
                . $vertical_class, $edit_str,
                $where_clause,
                $where_clause_html
            );

        } else {
            unset($vertical_display['edit'][$row_no]);
        }

        if (isset($copy_url)) {

            $vertical_display['copy'][$row_no] .= $this->_getCopyLink(
                $copy_url, $copy_str, $where_clause, $where_clause_html,
                $alternating_color_class . $vertical_class
            );

        } else {
            unset($vertical_display['copy'][$row_no]);
        }

        if (isset($del_url)) {

            if (! isset($js_conf)) {
                $js_conf = '';
            }

            $vertical_display['delete'][$row_no]
                .= $this->_getDeleteLink(
                    $del_url, $del_str, $js_conf,
                    $alternating_color_class . $vertical_class
                );

        } else {
            unset($vertical_display['delete'][$row_no]);
        }

        $this->__set('vertical_display', $vertical_display);

    } // end of the '_gatherLinksForLaterOutputs()' function


    /**
     * Get link for display special schema links
     *
     * @param string $column_value column value
     * @param array  $row_info     information about row
     * @param string $field_name   column name
     *
     * @return string generated link
     */
    private function _getSpecialLinkUrl($column_value, $row_info, $field_name)
    {

        $linking_url_params = array();
        $link_relations = $GLOBALS['special_schema_links']
            [strtolower($this->__get('db'))]
            [strtolower($this->__get('table'))]
            [$field_name];

        if (! is_array($link_relations['link_param'])) {
            $linking_url_params[$link_relations['link_param']] = $column_value;
        } else {
            // Consider only the case of creating link for column field
            // sql query that needs to be passed as url param
            $sql = 'SELECT `' . $column_value . '` FROM `'
                . $row_info[$link_relations['link_param'][1]] . '`.`'
                . $row_info[$link_relations['link_param'][2]] . '`';
            $linking_url_params[$link_relations['link_param'][0]] = $sql;
        }


        if (! empty($link_relations['link_dependancy_params'])) {

            foreach ($link_relations['link_dependancy_params'] as $new_param) {

                // If param_info is an array, set the key and value
                // from that array
                if (is_array($new_param['param_info'])) {
                    $linking_url_params[$new_param['param_info'][0]]
                        = $new_param['param_info'][1];
                } else {

                    $linking_url_params[$new_param['param_info']]
                        = $row_info[strtolower($new_param['column_name'])];

                    // Special case 1 - when executing routines, according
                    // to the type of the routine, url param changes
                    if (!empty($row_info['routine_type'])) {
                        $lowerRoutineType = strtolower($row_info['routine_type']);
                        if ($lowerRoutineType == self::ROUTINE_PROCEDURE
                            || $lowerRoutineType == self::ROUTINE_FUNCTION
                        ) {
                            $linking_url_params['edit_item'] = 1;
                        }
                    }
                }

            }

        }

        return $link_relations['default_page']
            . PMA_URL_getCommon($linking_url_params);

    }


    /**
     * Prepare row information for display special links
     *
     * @param array $row       current row data
     * @param array $col_order the column order
     *
     * @return array $row_info associative array with column nama -> value
     */
    private function _getRowInfoForSpecialLinks($row, $col_order)
    {

        $row_info = array();
        $fields_meta = $this->__get('fields_meta');

        for ($n = 0; $n < $this->__get('fields_cnt'); ++$n) {
            $m = $col_order ? $col_order[$n] : $n;
            $row_info[strtolower($fields_meta[$m]->name)] = $row[$m];
        }

        return $row_info;

    }


    /**
     * Get url sql query without conditions to shorten URLs
     *
     * @param array $analyzed_sql analyzed query
     *
     * @return  string  $url_sql        analyzed sql query
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getUrlSqlQuery($analyzed_sql)
    {

        if (isset($analyzed_sql)
            && isset($analyzed_sql[0])
            && isset($analyzed_sql[0]['querytype'])
            && ($analyzed_sql[0]['querytype'] == self::QUERY_TYPE_SELECT)
            && (strlen($this->__get('sql_query')) > 200)
        ) {

            $url_sql_query = 'SELECT ';
            if (isset($analyzed_sql[0]['queryflags']['distinct'])) {
                $url_sql_query .= ' DISTINCT ';
            }

            $url_sql_query .= $analyzed_sql[0]['select_expr_clause'];
            if (!empty($analyzed_sql[0]['from_clause'])) {
                $url_sql_query .= ' FROM ' . $analyzed_sql[0]['from_clause'];
            }

            return $url_sql_query;
        }

        return $this->__get('sql_query');

    } // end of the '_getUrlSqlQuery()' function


    /**
     * Get column order and column visibility
     *
     * @param array $analyzed_sql the analyzed query
     *
     * @return  array           2 element array - $col_order, $col_visib
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getColumnParams($analyzed_sql)
    {
        if ($this->_isSelect($analyzed_sql)) {
            $pmatable = new PMA_Table($this->__get('table'), $this->__get('db'));
            $col_order = $pmatable->getUiProp(PMA_Table::PROP_COLUMN_ORDER);
            $col_visib = $pmatable->getUiProp(PMA_Table::PROP_COLUMN_VISIB);
        } else {
            $col_order = false;
            $col_visib = false;
        }

        return array($col_order, $col_visib);
    } // end of the '_getColumnParams()' function


    /**
     * Prepare vertical display mode necessay HTML stuff
     *
     * @param array   $vertical_display   informations used with vertical
     *                                    display mode
     * @param integer $row_no             the index of current row
     * @param boolean $directionCondition the directional condition
     *
     * @return  string  $vertical_disp_html     html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getVerticalDisplaySupportSegments(
        $vertical_display, $row_no, $directionCondition
    ) {

        $support_html = '';

        if ((($row_no != 0) && ($_SESSION['tmpval']['repeat_cells'] != 0))
            && !($row_no % $_SESSION['tmpval']['repeat_cells'])
            && $directionCondition
        ) {

            $support_html .= '<tr>' . "\n";

            if ($vertical_display['emptypre'] > 0) {

                $support_html .= '    <th colspan="'
                    . $vertical_display['emptypre'] . '">'
                    . "\n" . '        &nbsp;</th>' . "\n";

            } else if ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_NONE) {
                $support_html .= '    <th></th>' . "\n";
            }

            foreach ($vertical_display['desc'] as $val) {
                $support_html .= $val;
            }

            if ($vertical_display['emptyafter'] > 0) {
                $support_html
                    .= '    <th colspan="' . $vertical_display['emptyafter']
                    . '">'
                    . "\n" . '        &nbsp;</th>' . "\n";
            }
            $support_html .= '</tr>' . "\n";
        } // end if

        return $support_html;

    } // end of the '_getVerticalDisplaySupportSegments()' function


    /**
     * Get modified links
     *
     * @param string  $where_clause     the where clause of the sql
     * @param boolean $clause_is_unique the unique condition of clause
     * @param string  $url_sql_query    the analyzed sql query
     *
     * @return  array                   5 element array - $edit_url, $copy_url,
     *                                  $edit_str, $copy_str, $edit_anchor_class
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getModifiedLinks(
        $where_clause, $clause_is_unique, $url_sql_query
    ) {

        $_url_params = array(
                'db'               => $this->__get('db'),
                'table'            => $this->__get('table'),
                'where_clause'     => $where_clause,
                'clause_is_unique' => $clause_is_unique,
                'sql_query'        => $url_sql_query,
                'goto'             => 'sql.php',
            );

        $edit_url = 'tbl_change.php'
            . PMA_URL_getCommon(
                $_url_params + array('default_action' => 'update')
            );

        $copy_url = 'tbl_change.php'
            . PMA_URL_getCommon(
                $_url_params + array('default_action' => 'insert')
            );

        $edit_str = $this->_getActionLinkContent(
            'b_edit.png', __('Edit')
        );
        $copy_str = $this->_getActionLinkContent(
            'b_insrow.png', __('Copy')
        );

        // Class definitions required for grid editing jQuery scripts
        $edit_anchor_class = "edit_row_anchor";
        if ( $clause_is_unique == 0) {
            $edit_anchor_class .= ' nonunique';
        }

        return array($edit_url, $copy_url, $edit_str, $copy_str, $edit_anchor_class);

    } // end of the '_getModifiedLinks()' function


    /**
     * Get delete and kill links
     *
     * @param string  $where_clause     the where clause of the sql
     * @param boolean $clause_is_unique the unique condition of clause
     * @param string  $url_sql_query    the analyzed sql query
     * @param string  $del_lnk          the delete link of current row
     * @param array   $row              the current row
     *
     * @return  array                       4 element array - $del_query,
     *                                      $del_url, $del_str, $js_conf
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getDeleteAndKillLinks(
        $where_clause, $clause_is_unique, $url_sql_query, $del_lnk, $row
    ) {

        $goto = $this->__get('goto');

        if ($del_lnk == self::DELETE_ROW) { // delete row case

            $_url_params = array(
                'db'        => $this->__get('db'),
                'table'     => $this->__get('table'),
                'sql_query' => $url_sql_query,
                'message_to_show' => __('The row has been deleted.'),
                'goto'      => (empty($goto) ? 'tbl_sql.php' : $goto),
            );

            $lnk_goto = 'sql.php' . PMA_URL_getCommon($_url_params, 'text');

            $del_query = 'DELETE FROM '
                . PMA_Util::backquote($this->__get('db')) . '.'
                . PMA_Util::backquote($this->__get('table'))
                . ' WHERE ' . $where_clause .
                ($clause_is_unique ? '' : ' LIMIT 1');

            $_url_params = array(
                    'db'        => $this->__get('db'),
                    'table'     => $this->__get('table'),
                    'sql_query' => $del_query,
                    'message_to_show' => __('The row has been deleted.'),
                    'goto'      => $lnk_goto,
                );
            $del_url  = 'sql.php' . PMA_URL_getCommon($_url_params);

            $js_conf  = 'DELETE FROM ' . PMA_jsFormat($this->__get('db')) . '.'
                . PMA_jsFormat($this->__get('table'))
                . ' WHERE ' . PMA_jsFormat($where_clause, false)
                . ($clause_is_unique ? '' : ' LIMIT 1');

            $del_str = $this->_getActionLinkContent('b_drop.png', __('Delete'));

        } elseif ($del_lnk == self::KILL_PROCESS) { // kill process case

            $_url_params = array(
                    'db'        => $this->__get('db'),
                    'table'     => $this->__get('table'),
                    'sql_query' => $url_sql_query,
                    'goto'      => 'index.php',
                );

            $lnk_goto = 'sql.php'
                . PMA_URL_getCommon(
                    $_url_params, 'text'
                );

            $_url_params = array(
                    'db'        => 'mysql',
                    'sql_query' => 'KILL ' . $row[0],
                    'goto'      => $lnk_goto,
                );

            $del_url  = 'sql.php' . PMA_URL_getCommon($_url_params);
            $del_query = 'KILL ' . $row[0];
            $js_conf  = 'KILL ' . $row[0];
            $del_str = PMA_Util::getIcon(
                'b_drop.png', __('Kill')
            );
        }

        return array($del_query, $del_url, $del_str, $js_conf);

    } // end of the '_getDeleteAndKillLinks()' function


    /**
     * Get content inside the table row action links (Edit/Copy/Delete)
     *
     * @param string $icon         The name of the file to get
     * @param string $display_text The text displaying after the image icon
     *
     * @return  string
     *
     * @access  private
     *
     * @see     _getModifiedLinks(), _getDeleteAndKillLinks()
     */
    private function _getActionLinkContent($icon, $display_text)
    {

        $linkContent = '';

        if (isset($GLOBALS['cfg']['RowActionType'])
            && $GLOBALS['cfg']['RowActionType'] == self::ACTION_LINK_CONTENT_ICONS
        ) {

            $linkContent .= '<span class="nowrap">'
                . PMA_Util::getImage(
                    $icon, $display_text
                )
                . '</span>';

        } else if (isset($GLOBALS['cfg']['RowActionType'])
            && $GLOBALS['cfg']['RowActionType'] == self::ACTION_LINK_CONTENT_TEXT
        ) {

            $linkContent .= '<span class="nowrap">' . $display_text . '</span>';

        } else {

            $linkContent .= PMA_Util::getIcon(
                $icon, $display_text
            );

        }

        return $linkContent;

    }


    /**
     * Prepare placed links
     *
     * @param string  $dir               the direction of links should place
     * @param string  $del_url           the url for delete row
     * @param array   $is_display        which elements to display
     * @param integer $row_no            the index of current row
     * @param string  $where_clause      the where clause of the sql
     * @param string  $where_clause_html the html encoded where clause
     * @param array   $condition_array   array of keys (primary, unique, condition)
     * @param string  $del_query         the query for delete row
     * @param string  $dir_letter        the letter denoted the direction
     * @param string  $edit_url          the url for edit row
     * @param string  $copy_url          the url for copy row
     * @param string  $edit_anchor_class the class for html element for edit
     * @param string  $edit_str          the label for edit row
     * @param string  $copy_str          the label for copy row
     * @param string  $del_str           the label for delete row
     * @param string  $js_conf           text for the JS confirmation
     *
     * @return  string                      html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getPlacedLinks(
        $dir, $del_url, $is_display, $row_no, $where_clause, $where_clause_html,
        $condition_array, $del_query, $dir_letter, $edit_url, $copy_url,
        $edit_anchor_class, $edit_str, $copy_str, $del_str, $js_conf
    ) {

        if (! isset($js_conf)) {
            $js_conf = '';
        }

        return $this->_getCheckboxAndLinks(
            $dir, $del_url, $is_display,
            $row_no, $where_clause, $where_clause_html, $condition_array,
            $del_query, 'l', $edit_url, $copy_url, $edit_anchor_class,
            $edit_str, $copy_str, $del_str, $js_conf
        );

    } // end of the '_getPlacedLinks()' function


    /**
     * Get the combined classes for a column
     *
     * @param string  $grid_edit_class  the class for all editable columns
     * @param string  $not_null_class   the class for not null columns
     * @param string  $relation_class   the class for relations in a column
     * @param string  $hide_class       the class for visibility of a column
     * @param string  $field_type_class the class related to type of the field
     * @param integer $row_no           the row index
     *
     * @return string $class the combined classes
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getClassesForColumn(
        $grid_edit_class, $not_null_class, $relation_class,
        $hide_class, $field_type_class, $row_no
    ) {

        $printview = $this->__get('printview');

        $class = 'data ' . $grid_edit_class . ' ' . $not_null_class . ' '
            . $relation_class . ' ' . $hide_class . ' ' . $field_type_class;

        $disp_direction = $_SESSION['tmpval']['disp_direction'];
        if (($disp_direction == self::DISP_DIR_VERTICAL)
            && (! isset($printview) || ($printview != '1'))
        ) {
            // the row number corresponds to a data row, not HTML table row
            $class .= ' row_' . $row_no;
            if ($GLOBALS['cfg']['BrowsePointerEnable'] == true) {
                $class .= ' vpointer';
            }

            if ($GLOBALS['cfg']['BrowseMarkerEnable'] == true) {
                $class .= ' vmarker';
            }
        }

        return $class;

    } // end of the '_getClassesForColumn()' function


    /**
     * Get class for datetime related fields
     *
     * @param string $type the type of the column field
     *
     * @return  string  $field_type_class   the class for the column
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getClassForDateTimeRelatedFields($type)
    {
        if ((substr($type, 0, 9) == self::TIMESTAMP_FIELD)
            || ($type == self::DATETIME_FIELD)
        ) {
            $field_type_class = 'datetimefield';
        } elseif ($type == self::DATE_FIELD) {
            $field_type_class = 'datefield';
        } elseif ($type == self::TIME_FIELD) {
            $field_type_class = 'timefield';
        } else {
            $field_type_class = '';
        }
        return $field_type_class;
    } // end of the '_getClassForDateTimeRelatedFields()' function


    /**
     * Prepare data cell for numeric type fields
     *
     * @param string  $column                the relevant column in data row
     * @param string  $class                 the html class for column
     * @param boolean $condition_field       the column should highlighted
     *                                       or not
     * @param object  $meta                  the meta-information about this
     *                                       field
     * @param array   $map                   the list of relations
     * @param boolean $is_field_truncated    the condition for blob data
     *                                       replacements
     * @param array   $analyzed_sql          the analyzed query
     * @param string  $transformation_plugin the name of transformation plugin
     * @param string  $default_function      the default transformation function
     * @param array   $transform_options     the transformation parameters
     *
     * @return  string  $cell               the prepared cell, html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getDataCellForNumericColumns(
        $column, $class, $condition_field, $meta, $map, $is_field_truncated,
        $analyzed_sql, $transformation_plugin, $default_function,
        $transform_options
    ) {

        if (! isset($column) || is_null($column)) {

            $cell = $this->_buildNullDisplay(
                'right ' . $class, $condition_field, $meta, ''
            );

        } elseif ($column != '') {

            $nowrap = ' nowrap';
            $where_comparison = ' = ' . $column;

            $cell = $this->_getRowData(
                'right ' . $class, $condition_field,
                $analyzed_sql, $meta, $map, $column,
                $transformation_plugin, $default_function, $nowrap,
                $where_comparison, $transform_options,
                $is_field_truncated
            );
        } else {

            $cell = $this->_buildEmptyDisplay(
                'right ' . $class, $condition_field, $meta, ''
            );
        }

        return $cell;

    } // end of the '_getDataCellForNumericColumns()' function


    /**
     * Get data cell for blob type fields
     *
     * @param string  $column                the relevant column in data row
     * @param string  $class                 the html class for column
     * @param object  $meta                  the meta-information about this
     *                                       field
     * @param array   $_url_params           the parameters for generate url
     * @param string  $field_flags           field flags for column(blob,
     *                                       primary etc)
     * @param string  $transformation_plugin the name of transformation function
     * @param string  $default_function      the default transformation function
     * @param array   $transform_options     the transformation parameters
     * @param boolean $condition_field       the column should highlighted
     *                                       or not
     * @param boolean $is_field_truncated    the condition for blob data
     *                                       replacements
     *
     * @return  string  $cell                the prepared cell, html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getDataCellForBlobColumns(
        $column, $class, $meta, $_url_params, $field_flags, $transformation_plugin,
        $default_function, $transform_options, $condition_field, $is_field_truncated
    ) {

        if (stristr($field_flags, self::BINARY_FIELD)) {

            // remove 'grid_edit' from $class as we can't edit binary data.
            $class = str_replace('grid_edit', '', $class);

            if (! isset($column) || is_null($column)) {

                $cell = $this->_buildNullDisplay($class, $condition_field, $meta);

            } else {

                $blobtext = $this->_handleNonPrintableContents(
                    self::BLOB_FIELD, (isset($column) ? $column : ''),
                    $transformation_plugin, $transform_options,
                    $default_function, $meta, $_url_params
                );

                $cell = $this->_buildValueDisplay(
                    $class, $condition_field, $blobtext
                );
                unset($blobtext);
            }
        } else {
            // not binary:

            if (! isset($column) || is_null($column)) {

                $cell = $this->_buildNullDisplay($class, $condition_field, $meta);

            } elseif ($column != '') {

                // if a transform function for blob is set, none of these
                // replacements will be made
                $limitChars = $GLOBALS['cfg']['LimitChars'];
                if (($GLOBALS['PMA_String']->strlen($column) > $limitChars)
                    && ($_SESSION['tmpval']['pftext'] == self::DISPLAY_PARTIAL_TEXT)
                    && empty($this->transformation_info[strtolower($this->__get('db'))][strtolower($this->__get('table'))][strtolower(strtolower($meta->name))])
                ) {
                    $column = $GLOBALS['PMA_String']->substr(
                        $column, 0, $GLOBALS['cfg']['LimitChars']
                    ) . '...';
                    $is_field_truncated = true;
                }

                // displays all space characters, 4 space
                // characters for tabulations and <cr>/<lf>
                $column = ($default_function != $transformation_plugin)
                    ? $transformation_plugin->applyTransformation(
                        $column,
                        $transform_options,
                        $meta
                    )
                    : $this->$default_function($column, array(), $meta);

                if ($is_field_truncated) {
                    $class .= ' truncated';
                }

                $cell = $this->_buildValueDisplay($class, $condition_field, $column);

            } else {
                $cell = $this->_buildEmptyDisplay($class, $condition_field, $meta);
            }
        }

        return $cell;

    } // end of the '_getDataCellForBlobColumns()' function


    /**
     * Get data cell for geometry type fields
     *
     * @param string  $column                the relevant column in data row
     * @param string  $class                 the html class for column
     * @param object  $meta                  the meta-information about this field
     * @param array   $map                   the list of relations
     * @param array   $_url_params           the parameters for generate url
     * @param boolean $condition_field       the column should highlighted or not
     * @param string  $transformation_plugin the name of transformation function
     * @param string  $default_function      the default transformation function
     * @param array   $transform_options     the transformation parameters
     * @param boolean $is_field_truncated    the condition for blob data replacements
     * @param array   $analyzed_sql          the analyzed query
     *
     * @return  string  $cell                  the prepared data cell, html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getDataCellForGeometryColumns(
        $column, $class, $meta, $map, $_url_params, $condition_field,
        $transformation_plugin, $default_function, $transform_options,
        $is_field_truncated, $analyzed_sql
    ) {

        $pftext = $_SESSION['tmpval']['pftext'];
        $limitChars = $GLOBALS['cfg']['LimitChars'];

        if (! isset($column) || is_null($column)) {

            $cell = $this->_buildNullDisplay($class, $condition_field, $meta);

        } elseif ($column != '') {

            // Display as [GEOMETRY - (size)]
            if ($_SESSION['tmpval']['geoOption'] == self::GEOMETRY_DISP_GEOM) {

                $geometry_text = $this->_handleNonPrintableContents(
                    strtoupper(self::GEOMETRY_FIELD),
                    (isset($column) ? $column : ''), $transformation_plugin,
                    $transform_options, $default_function, $meta
                );

                $cell = $this->_buildValueDisplay(
                    $class, $condition_field, $geometry_text
                );

            } elseif ($_SESSION['tmpval']['geoOption'] == self::GEOMETRY_DISP_WKT) {
                // Prepare in Well Known Text(WKT) format.

                $where_comparison = ' = ' . $column;

                // Convert to WKT format
                $wktval = PMA_Util::asWKT($column);

                if (($GLOBALS['PMA_String']->strlen($wktval) > $limitChars)
                    && ($pftext == self::DISPLAY_PARTIAL_TEXT)
                ) {
                    $wktval = $GLOBALS['PMA_String']->substr(
                        $wktval, 0, $limitChars
                    ) . '...';
                    $is_field_truncated = true;
                }

                $cell = $this->_getRowData(
                    $class, $condition_field, $analyzed_sql, $meta, $map,
                    $wktval, $transformation_plugin, $default_function, '',
                    $where_comparison, $transform_options,
                    $is_field_truncated
                );

            } else {
                // Prepare in  Well Known Binary (WKB) format.

                if ($_SESSION['tmpval']['display_binary']) {

                    $where_comparison = ' = ' . $column;

                    $wkbval = $this->_displayBinaryAsPrintable($column, 'binary', 8);

                    if (($GLOBALS['PMA_String']->strlen($wkbval) > $limitChars)
                        && ($pftext == self::DISPLAY_PARTIAL_TEXT)
                    ) {
                        $wkbval = $GLOBALS['PMA_String']->substr(
                            $wkbval, 0, $GLOBALS['cfg']['LimitChars']
                        ) . '...';
                        $is_field_truncated = true;
                    }

                    $cell = $this->_getRowData(
                        $class, $condition_field,
                        $analyzed_sql, $meta, $map, $wkbval,
                        $transformation_plugin, $default_function, '',
                        $where_comparison, $transform_options,
                        $is_field_truncated
                    );

                } else {
                    $wkbval = $this->_handleNonPrintableContents(
                        self::BINARY_FIELD, $column, $transformation_plugin,
                        $transform_options, $default_function, $meta,
                        $_url_params
                    );

                    $cell = $this->_buildValueDisplay(
                        $class, $condition_field, $wkbval
                    );
                }
            }
        } else {
            $cell = $this->_buildEmptyDisplay($class, $condition_field, $meta);
        }

        return $cell;

    } // end of the '_getDataCellForGeometryColumns()' function


    /**
     * Get data cell for non numeric and non blob type fields
     *
     * @param string  $column                the relevant column in data row
     * @param string  $class                 the html class for column
     * @param object  $meta                  the meta-information about the field
     * @param array   $map                   the list of relations
     * @param array   $_url_params           the parameters for generate url
     * @param boolean $condition_field       the column should highlighted
     *                                       or not
     * @param string  $transformation_plugin the name of transformation function
     * @param string  $default_function      the default transformation function
     * @param array   $transform_options     the transformation parameters
     * @param boolean $is_field_truncated    the condition for blob data
     *                                       replacements
     * @param array   $analyzed_sql          the analyzed query
     * @param integer &$dt_result            the link id associated to the query
     *                                        which results have to be displayed
     * @param integer $col_index             the column index
     *
     * @return  string  $cell               the prepared data cell, html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getDataCellForNonNumericAndNonBlobColumns(
        $column, $class, $meta, $map, $_url_params, $condition_field,
        $transformation_plugin, $default_function, $transform_options,
        $is_field_truncated, $analyzed_sql, &$dt_result, $col_index
    ) {

        $limitChars = $GLOBALS['cfg']['LimitChars'];
        $is_analyse = $this->__get('is_analyse');
        $field_flags = $GLOBALS['dbi']->fieldFlags($dt_result, $col_index);
        if (stristr($field_flags, self::BINARY_FIELD)
            && ($GLOBALS['cfg']['ProtectBinary'] == 'all'
            || $GLOBALS['cfg']['ProtectBinary'] == 'noblob')
        ) {
            $class = str_replace('grid_edit', '', $class);
        }

        if (! isset($column) || is_null($column)) {

            $cell = $this->_buildNullDisplay($class, $condition_field, $meta);

        } elseif ($column != '') {

            // Cut all fields to $GLOBALS['cfg']['LimitChars']
            // (unless it's a link-type transformation)
            if ($GLOBALS['PMA_String']->strlen($column) > $limitChars
                && ($_SESSION['tmpval']['pftext'] == self::DISPLAY_PARTIAL_TEXT)
                && ! (gettype($transformation_plugin) == "object"
                && strpos($transformation_plugin->getName(), 'Link') !== false)
            ) {
                $column = $GLOBALS['PMA_String']->substr(
                    $column, 0, $GLOBALS['cfg']['LimitChars']
                ) . '...';
                $is_field_truncated = true;
            }

            $formatted = false;
            if (isset($meta->_type) && $meta->_type === MYSQLI_TYPE_BIT) {

                $column = PMA_Util::printableBitValue(
                    $column, $meta->length
                );

                // some results of PROCEDURE ANALYSE() are reported as
                // being BINARY but they are quite readable,
                // so don't treat them as BINARY
            } elseif (stristr($field_flags, self::BINARY_FIELD)
                && ($meta->type == self::STRING_FIELD)
                && !(isset($is_analyse) && $is_analyse)
            ) {

                if ($_SESSION['tmpval']['display_binary']) {

                    // user asked to see the real contents of BINARY
                    // fields
                    $column = $this->_displayBinaryAsPrintable($column, 'binary');

                } else {
                    // we show the BINARY message and field's size
                    // (or maybe use a transformation)
                    $column = $this->_handleNonPrintableContents(
                        self::BINARY_FIELD, $column, $transformation_plugin,
                        $transform_options, $default_function,
                        $meta, $_url_params
                    );
                    $formatted = true;
                }
            } elseif (((substr($meta->type, 0, 9) == self::TIMESTAMP_FIELD)
                || ($meta->type == self::DATETIME_FIELD)
                || ($meta->type == self::TIME_FIELD)
                || ($meta->type == self::TIME_FIELD))
                && (strpos($column, ".") === true)
            ) {
                $column = PMA_Util::addMicroseconds($column);
            }

            if ($formatted) {

                $cell = $this->_buildValueDisplay(
                    $class, $condition_field, $column
                );

            } else {

                // transform functions may enable no-wrapping:
                $function_nowrap = 'applyTransformationNoWrap';

                $bool_nowrap = (($default_function != $transformation_plugin)
                    && function_exists($transformation_plugin->$function_nowrap()))
                    ? $transformation_plugin->$function_nowrap($transform_options)
                    : false;

                // do not wrap if date field type
                $nowrap = (preg_match('@DATE|TIME@i', $meta->type)
                    || $bool_nowrap) ? ' nowrap' : '';

                $where_comparison = ' = \''
                    . PMA_Util::sqlAddSlashes($column)
                    . '\'';

                $cell = $this->_getRowData(
                    $class, $condition_field,
                    $analyzed_sql, $meta, $map, $column,
                    $transformation_plugin, $default_function, $nowrap,
                    $where_comparison, $transform_options,
                    $is_field_truncated
                );
            }

        } else {
            $cell = $this->_buildEmptyDisplay($class, $condition_field, $meta);
        }

        return $cell;

    } // end of the '_getDataCellForNonNumericAndNonBlobColumns()' function


    /**
     * Get the resulted table with the vertical direction mode.
     *
     * @param array $analyzed_sql the analyzed query
     * @param array $is_display   display mode
     *
     * @return string       html content
     *
     * @access  private
     *
     * @see     _getTable()
     */
    private function _getVerticalTable($analyzed_sql, $is_display)
    {

        $vertical_table_html = '';
        $vertical_display = $this->__get('vertical_display');

        // Prepares "multi row delete" link at top if required
        if (($GLOBALS['cfg']['RowActionLinks'] != self::POSITION_RIGHT)
            && is_array($vertical_display['row_delete'])
            && ((count($vertical_display['row_delete']) > 0)
            || !empty($vertical_display['textbtn']))
        ) {

            $vertical_table_html .= '<tr>' . "\n";
            if ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_NONE) {
                // if we are not showing the RowActionLinks, then we need to show
                // the Multi-Row-Action checkboxes
                $vertical_table_html .= '<th></th>' . "\n";
            }

            $vertical_table_html .= $vertical_display['textbtn']
                . $this->_getCheckBoxesForMultipleRowOperations('_left', $is_display)
                . '</tr>' . "\n";
        } // end if

        // Prepares "edit" link at top if required
        if ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_LEFT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && is_array($vertical_display['edit'])
            && ((count($vertical_display['edit']) > 0)
            || !empty($vertical_display['textbtn']))
        ) {
            $vertical_table_html .= $this->_getOperationLinksForVerticleTable(
                'edit'
            );
        } // end if

        // Prepares "copy" link at top if required
        if ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_LEFT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && is_array($vertical_display['copy'])
            && ((count($vertical_display['copy']) > 0)
            || !empty($vertical_display['textbtn']))
        ) {
            $vertical_table_html .= $this->_getOperationLinksForVerticleTable(
                'copy'
            );
        } // end if

        // Prepares "delete" link at top if required
        if ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_LEFT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && is_array($vertical_display['delete'])
            && ((count($vertical_display['delete']) > 0)
            || !empty($vertical_display['textbtn']))
        ) {
            $vertical_table_html .= $this->_getOperationLinksForVerticleTable(
                'delete'
            );
        } // end if

        list($col_order, $col_visib) = $this->_getColumnParams($analyzed_sql);

        // Prepares data
        foreach ($vertical_display['desc'] as $j => $val) {

            // assign appropriate key with current column order
            $key = $col_order ? $col_order[$j] : $j;

            $vertical_table_html .= '<tr'
                . (($col_visib && !$col_visib[$j]) ? ' class="hide"' : '')
                . '>' . "\n"
                . $val;

            $cell_displayed = 0;
            foreach ($vertical_display['rowdata'][$key] as $subval) {

                if (($cell_displayed != 0)
                    && ($_SESSION['tmpval']['repeat_cells'] != 0)
                    && ! ($cell_displayed % $_SESSION['tmpval']['repeat_cells'])
                ) {
                    $vertical_table_html .= $val;
                }

                $vertical_table_html .= $subval;
                $cell_displayed++;

            } // end while

            $vertical_table_html .= '</tr>' . "\n";
        } // end while

        // Prepares "multi row delete" link at bottom if required
        if ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_RIGHT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && is_array($vertical_display['row_delete'])
            && ((count($vertical_display['row_delete']) > 0)
            || !empty($vertical_display['textbtn']))
        ) {

            $vertical_table_html .= '<tr>' . "\n"
                . $vertical_display['textbtn']
                . $this->_getCheckBoxesForMultipleRowOperations(
                    '_right', $is_display
                )
                . '</tr>' . "\n";
        } // end if

        // Prepares "edit" link at bottom if required
        if ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_RIGHT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && is_array($vertical_display['edit'])
            && ((count($vertical_display['edit']) > 0)
            || !empty($vertical_display['textbtn']))
        ) {
            $vertical_table_html .= $this->_getOperationLinksForVerticleTable(
                'edit'
            );
        } // end if

        // Prepares "copy" link at bottom if required
        if ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_RIGHT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && is_array($vertical_display['copy'])
            && ((count($vertical_display['copy']) > 0)
            || !empty($vertical_display['textbtn']))
        ) {
            $vertical_table_html .= $this->_getOperationLinksForVerticleTable(
                'copy'
            );
        } // end if

        // Prepares "delete" link at bottom if required
        if ((($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_RIGHT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH))
            && is_array($vertical_display['delete'])
            && ((count($vertical_display['delete']) > 0)
            || !empty($vertical_display['textbtn']))
        ) {
            $vertical_table_html .= $this->_getOperationLinksForVerticleTable(
                'delete'
            );
        }

        return $vertical_table_html;

    } // end of the '_getVerticalTable' function


    /**
     * Prepare edit, copy and delete links for verticle table
     *
     * @param string $operation edit/copy/delete
     *
     * @return  string  $links_html  html content
     *
     * @access  private
     *
     * @see     _getVerticalTable()
     */
    private function _getOperationLinksForVerticleTable($operation)
    {

        $link_html = '<tr>' . "\n";
        $vertical_display = $this->__get('vertical_display');

        if (! is_array($vertical_display['row_delete'])) {

            if (($operation == 'edit') || ($operation == 'copy')) {
                $link_html .= $vertical_display['textbtn'];

            } elseif ($operation == 'delete') {

                if (! is_array($vertical_display['edit'])) {
                    $link_html .= $vertical_display['textbtn'];
                }
            }
        }

        foreach ($vertical_display[$operation] as $val) {
            $link_html .= $val;
        } // end while

        $link_html .= '</tr>' . "\n";

        return $link_html;

    } // end of the '_getOperationLinksForVerticleTable' function


    /**
     * Get checkboxes for multiple row data operations
     *
     * @param string $dir        _left / _right
     * @param array  $is_display display mode
     *
     * @return String $checkBoxes_html html content
     *
     * @access private
     *
     * @see    _getVerticalTable()
     */
    private function _getCheckBoxesForMultipleRowOperations($dir, $is_display)
    {

        $checkBoxes_html = '';
        $cell_displayed = 0;
        $vertical_display = $this->__get('vertical_display');

        foreach ($vertical_display['row_delete'] as $val) {

            if (($cell_displayed != 0)
                && ($_SESSION['tmpval']['repeat_cells'] != 0)
                && !($cell_displayed % $_SESSION['tmpval']['repeat_cells'])
            ) {

                $checkBoxes_html .= '<th'
                    . (($is_display['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                        && ($is_display['del_lnk'] != self::NO_EDIT_OR_DELETE))
                        ? ' rowspan="4"'
                        : ''
                    . '></th>' . "\n";

            }

            $checkBoxes_html .= str_replace('[%_PMA_CHECKBOX_DIR_%]', $dir, $val);
            $cell_displayed++;
        } // end while

        return $checkBoxes_html;

    } // end of the '_getCheckBoxesForMultipleRowOperations' function


    /**
     * Checks the posted options for viewing query resutls
     * and sets appropriate values in the session.
     *
     * @todo    make maximum remembered queries configurable
     * @todo    move/split into SQL class!?
     * @todo    currently this is called twice unnecessary
     * @todo    ignore LIMIT and ORDER in query!?
     *
     * @return void
     *
     * @access  public
     *
     * @see     sql.php file
     */
    public function setConfigParamsForDisplayTable()
    {

        $sql_md5 = md5($this->__get('sql_query'));
        $query = array();
        if (isset($_SESSION['tmpval']['query'][$sql_md5])) {
            $query = $_SESSION['tmpval']['query'][$sql_md5];
        }

        $query['sql'] = $this->__get('sql_query');

        $valid_disp_dir = PMA_isValid(
            $_REQUEST['disp_direction'],
            array(self::DISP_DIR_HORIZONTAL, self::DISP_DIR_VERTICAL,
                self::DISP_DIR_HORIZONTAL_FLIPPED
            )
        );

        if ($valid_disp_dir) {
            $query['disp_direction'] = $_REQUEST['disp_direction'];
            unset($_REQUEST['disp_direction']);
        } elseif (empty($query['disp_direction'])) {
            $query['disp_direction'] = $GLOBALS['cfg']['DefaultDisplay'];
        }

        if (empty($query['repeat_cells'])) {
            $query['repeat_cells'] = $GLOBALS['cfg']['RepeatCells'];
        }

        // as this is a form value, the type is always string so we cannot
        // use PMA_isValid($_REQUEST['session_max_rows'], 'integer')
        if (PMA_isValid($_REQUEST['session_max_rows'], 'numeric')) {
            $query['max_rows'] = (int)$_REQUEST['session_max_rows'];
            unset($_REQUEST['session_max_rows']);
        } elseif ($_REQUEST['session_max_rows'] == self::ALL_ROWS) {
            $query['max_rows'] = self::ALL_ROWS;
            unset($_REQUEST['session_max_rows']);
        } elseif (empty($query['max_rows'])) {
            $query['max_rows'] = $GLOBALS['cfg']['MaxRows'];
        }

        if (PMA_isValid($_REQUEST['pos'], 'numeric')) {
            $query['pos'] = $_REQUEST['pos'];
            unset($_REQUEST['pos']);
        } elseif (empty($query['pos'])) {
            $query['pos'] = 0;
        }

        if (PMA_isValid(
            $_REQUEST['pftext'],
            array(
                self::DISPLAY_PARTIAL_TEXT, self::DISPLAY_FULL_TEXT
            )
        )
        ) {
            $query['pftext'] = $_REQUEST['pftext'];
            unset($_REQUEST['pftext']);
        } elseif (empty($query['pftext'])) {
            $query['pftext'] = self::DISPLAY_PARTIAL_TEXT;
        }

        if (PMA_isValid(
            $_REQUEST['relational_display'],
            array(
                self::RELATIONAL_KEY, self::RELATIONAL_DISPLAY_COLUMN
            )
        )
        ) {
            $query['relational_display'] = $_REQUEST['relational_display'];
            unset($_REQUEST['relational_display']);
        } elseif (empty($query['relational_display'])) {
            $query['relational_display'] = self::RELATIONAL_KEY;
        }

        if (PMA_isValid(
            $_REQUEST['geoOption'],
            array(
                self::GEOMETRY_DISP_WKT, self::GEOMETRY_DISP_WKB,
                self::GEOMETRY_DISP_GEOM
            )
        )
        ) {
            $query['geoOption'] = $_REQUEST['geoOption'];
            unset($_REQUEST['geoOption']);
        } elseif (empty($query['geoOption'])) {
            $query['geoOption'] = self::GEOMETRY_DISP_GEOM;
        }

        if (isset($_REQUEST['display_binary'])) {
            $query['display_binary'] = true;
            unset($_REQUEST['display_binary']);
        } elseif (isset($_REQUEST['display_options_form'])) {
            // we know that the checkbox was unchecked
            unset($query['display_binary']);
        } elseif (isset($_REQUEST['full_text_button'])) {
            // do nothing to keep the value that is there in the session
        } else {
            // selected by default because some operations like OPTIMIZE TABLE
            // and all queries involving functions return "binary" contents,
            // according to low-level field flags
            $query['display_binary'] = true;
        }

        if (isset($_REQUEST['display_binary_as_hex'])) {
            $query['display_binary_as_hex'] = true;
            unset($_REQUEST['display_binary_as_hex']);
        } elseif (isset($_REQUEST['display_options_form'])) {
            // we know that the checkbox was unchecked
            unset($query['display_binary_as_hex']);
        } elseif (isset($_REQUEST['full_text_button'])) {
            // do nothing to keep the value that is there in the session
        } else {
            // display_binary_as_hex config option
            if (isset($GLOBALS['cfg']['DisplayBinaryAsHex'])
                && ($GLOBALS['cfg']['DisplayBinaryAsHex'] === true)
            ) {
                $query['display_binary_as_hex'] = true;
            }
        }

        if (isset($_REQUEST['display_blob'])) {
            $query['display_blob'] = true;
            unset($_REQUEST['display_blob']);
        } elseif (isset($_REQUEST['display_options_form'])) {
            // we know that the checkbox was unchecked
            unset($query['display_blob']);
        }

        if (isset($_REQUEST['hide_transformation'])) {
            $query['hide_transformation'] = true;
            unset($_REQUEST['hide_transformation']);
        } elseif (isset($_REQUEST['display_options_form'])) {
            // we know that the checkbox was unchecked
            unset($query['hide_transformation']);
        }

        // move current query to the last position, to be removed last
        // so only least executed query will be removed if maximum remembered
        // queries limit is reached
        unset($_SESSION['tmpval']['query'][$sql_md5]);
        $_SESSION['tmpval']['query'][$sql_md5] = $query;

        // do not exceed a maximum number of queries to remember
        if (count($_SESSION['tmpval']['query']) > 10) {
            array_shift($_SESSION['tmpval']['query']);
            //echo 'deleting one element ...';
        }

        // populate query configuration
        $_SESSION['tmpval']['pftext']
            = $query['pftext'];
        $_SESSION['tmpval']['relational_display']
            = $query['relational_display'];
        $_SESSION['tmpval']['geoOption']
            = $query['geoOption'];
        $_SESSION['tmpval']['display_binary'] = isset(
            $query['display_binary']
        );
        $_SESSION['tmpval']['display_binary_as_hex'] = isset(
            $query['display_binary_as_hex']
        );
        $_SESSION['tmpval']['display_blob'] = isset(
            $query['display_blob']
        );
        $_SESSION['tmpval']['hide_transformation'] = isset(
            $query['hide_transformation']
        );
        $_SESSION['tmpval']['pos']
            = $query['pos'];
        $_SESSION['tmpval']['max_rows']
            = $query['max_rows'];
        $_SESSION['tmpval']['repeat_cells']
            = $query['repeat_cells'];
        $_SESSION['tmpval']['disp_direction']
            = $query['disp_direction'];

    }


    /**
     * Prepare a table of results returned by a SQL query.
     * This function is called by the "sql.php" script.
     *
     * @param integer &$dt_result         the link id associated to the query
     *                                    which results have to be displayed
     * @param array   &$the_disp_mode     the display mode
     * @param array   $analyzed_sql       the analyzed query
     * @param boolean $is_limited_display With limited operations or not
     *
     * @return  string   $table_html   Generated HTML content for resulted table
     *
     * @access  public
     *
     * @see     sql.php file
     */
    public function getTable(
        &$dt_result, &$the_disp_mode, $analyzed_sql,
        $is_limited_display = false
    ) {

        $table_html = '';
        // Following variable are needed for use in isset/empty or
        // use with array indexes/safe use in foreach
        $fields_meta = $this->__get('fields_meta');
        $showtable = $this->__get('showtable');
        $printview = $this->__get('printview');

        // why was this called here? (already called from sql.php)
        //$this->setConfigParamsForDisplayTable();

        /**
         * @todo move this to a central place
         * @todo for other future table types
         */
        $is_innodb = (isset($showtable['Type'])
            && $showtable['Type'] == self::TABLE_TYPE_INNO_DB);

        if ($is_innodb
            && ! isset($analyzed_sql[0]['queryflags']['union'])
            && ! isset($analyzed_sql[0]['table_ref'][1]['table_name'])
            && (empty($analyzed_sql[0]['where_clause'])
            || ($analyzed_sql[0]['where_clause'] == '1 '))
        ) {
            // "j u s t   b r o w s i n g"
            $pre_count = '~';
            $after_count = PMA_Util::showHint(
                PMA_sanitize(
                    __('May be approximate. See [doc@faq3-11]FAQ 3.11[/doc].')
                )
            );
        } else {
            $pre_count = '';
            $after_count = '';
        }

        // 1. ----- Prepares the work -----

        // 1.1 Gets the informations about which functionalities should be
        //     displayed
        $total      = '';
        $is_display = $this->_setDisplayMode($the_disp_mode, $total);

        // 1.2 Defines offsets for the next and previous pages
        if ($is_display['nav_bar'] == '1') {
            list($pos_next, $pos_prev) = $this->_getOffsets();
        } // end if
        if (!isset($analyzed_sql[0]['order_by_clause'])) {
            $analyzed_sql[0]['order_by_clause'] = "";
        }

        // 1.3 Find the sort expression
        // we need $sort_expression and $sort_expression_nodirection
        // even if there are many table references
        list(
            $sort_expression, $sort_expression_nodirection,
            $sort_direction
        ) = $this->_getSortParams($analyzed_sql[0]['order_by_clause']);

        $number_of_columns = count($sort_expression_nodirection);
        // 1.4 Prepares display of first and last value of the sorted column
        $sorted_column_message = '';
        for ( $i = 0; $i < $number_of_columns; $i++ ) {
            $sorted_column_message .= $this->_getSortedColumnMessage(
                $dt_result, $sort_expression_nodirection[$i]
            );
        }

        // 2. ----- Prepare to display the top of the page -----

        // 2.1 Prepares a messages with position informations
        if (($is_display['nav_bar'] == '1') && isset($pos_next)) {

            $message = $this->_setMessageInformation(
                $sorted_column_message, $analyzed_sql[0]['limit_clause'],
                $total, $pos_next, $pre_count, $after_count
            );

            $table_html .= PMA_Util::getMessage(
                $message, $this->__get('sql_query'), 'success'
            );

        } elseif (! isset($printview) || ($printview != '1')) {

            $table_html .= PMA_Util::getMessage(
                __('Your SQL query has been executed successfully.'),
                $this->__get('sql_query'), 'success'
            );
        }

        // 2.3 Prepare the navigation bars
        if (! strlen($this->__get('table'))) {

            if (isset($analyzed_sql[0]['query_type'])
                && ($analyzed_sql[0]['query_type'] == self::QUERY_TYPE_SELECT)
            ) {
                // table does not always contain a real table name,
                // for example in MySQL 5.0.x, the query SHOW STATUS
                // returns STATUS as a table name
                $this->__set('table', $fields_meta[0]->table);
            } else {
                $this->__set('table', '');
            }

        }

        if (($is_display['nav_bar'] == '1')
            && empty($analyzed_sql[0]['limit_clause'])
        ) {

            $table_html .= $this->_getPlacedTableNavigations(
                $pos_next, $pos_prev, self::PLACE_TOP_DIRECTION_DROPDOWN,
                "\n", $is_innodb
            );

        } elseif (! isset($printview) || ($printview != '1')) {
            $table_html .= "\n" . '<br /><br />' . "\n";
        }

        // 2b ----- Get field references from Database -----
        // (see the 'relation' configuration variable)

        // initialize map
        $map = array();

        // find tables
        $target=array();
        if (isset($analyzed_sql[0]['table_ref'])
            && is_array($analyzed_sql[0]['table_ref'])
        ) {

            foreach ($analyzed_sql[0]['table_ref']
                as $table_ref_position => $table_ref) {
                $target[] = $analyzed_sql[0]['table_ref']
                    [$table_ref_position]['table_true_name'];
            }

        }

        if (strlen($this->__get('table'))) {
            // This method set the values for $map array
            $this->_setParamForLinkForeignKeyRelatedTables($map);
        } // end if
        // end 2b

        // 3. ----- Prepare the results table -----
        $table_html .= $this->_getTableHeaders(
            $is_display, $analyzed_sql, $sort_expression,
            $sort_expression_nodirection, $sort_direction, $is_limited_display
        )
        . '<tbody>' . "\n";

        $table_html .= $this->_getTableBody(
            $dt_result, $is_display, $map, $analyzed_sql, $is_limited_display
        );

        // vertical output case
        if ($_SESSION['tmpval']['disp_direction'] == self::DISP_DIR_VERTICAL) {
            $table_html .= $this->_getVerticalTable($analyzed_sql, $is_display);
        } // end if

        $this->__set('vertical_display', null);

        $table_html .= '</tbody>' . "\n"
            . '</table>';

        // 4. ----- Prepares the link for multi-fields edit and delete

        if ($is_display['del_lnk'] == self::DELETE_ROW
            && $is_display['del_lnk'] != self::KILL_PROCESS
        ) {

            $table_html .= $this->_getMultiRowOperationLinks(
                $dt_result, $analyzed_sql, $is_display['del_lnk']
            );

        }

        // 5. ----- Get the navigation bar at the bottom if required -----
        if (($is_display['nav_bar'] == '1')
            && empty($analyzed_sql[0]['limit_clause'])
        ) {
            $table_html .= $this->_getPlacedTableNavigations(
                $pos_next, $pos_prev, self::PLACE_BOTTOM_DIRECTION_DROPDOWN,
                '<br />' . "\n", $is_innodb
            );
        } elseif (! isset($printview) || ($printview != '1')) {
            $table_html .= "\n" . '<br /><br />' . "\n";
        }


        // 6. ----- Prepare "Query results operations"
        if ((! isset($printview) || ($printview != '1')) && ! $is_limited_display) {
            $table_html .= $this->_getResultsOperations(
                $the_disp_mode, $analyzed_sql
            );
        }

        return $table_html;

    } // end of the 'getTable()' function


    /**
     * Get offsets for next page and previous page
     *
     * @return  array           array with two elements - $pos_next, $pos_prev
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _getOffsets()
    {

        if ($_SESSION['tmpval']['max_rows'] == self::ALL_ROWS) {
            $pos_next     = 0;
            $pos_prev     = 0;
        } else {

            $pos_next     = $_SESSION['tmpval']['pos']
                            + $_SESSION['tmpval']['max_rows'];

            $pos_prev     = $_SESSION['tmpval']['pos']
                            - $_SESSION['tmpval']['max_rows'];

            if ($pos_prev < 0) {
                $pos_prev = 0;
            }
        }

        return array($pos_next, $pos_prev);

    } // end of the '_getOffsets()' function


    /**
     * Get sort parameters
     *
     * @param string $order_by_clause the order by clause of the sql query
     *
     * @return  array                 3 element array: $sort_expression,
     *                                $sort_expression_nodirection, $sort_direction
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _getSortParams($order_by_clause)
    {

        $sort_expression             = array();
        $sort_expression_nodirection = array();
        $sort_direction              = array();
        if (! empty($order_by_clause)) {
            // Each order by clause is assumed to be delimited by a comma
            // A typical order by clause would be order by column1 asc, column2 desc
            // The following line counts the number of columns in order by clause
            $matches = explode(',', $order_by_clause);
            // Iterate over each column in order by clause
            foreach ($matches as $index=>$order_by_clause2) {

                $sort_expression[$index] = trim(
                    str_replace('  ', ' ', $order_by_clause2)
                );
                /**
                 * Get rid of ASC|DESC
                 */
                preg_match(
                    '@(.*)([[:space:]]*(ASC|DESC))@si',
                    $sort_expression[$index], $matches
                );

                $sort_expression_nodirection[$index] = isset($matches[1])
                    ? trim($matches[1])
                    : $sort_expression[$index];
                $sort_direction[$index]
                    = isset($matches[2]) ? trim($matches[2]) : '';
            }
        } else {
            $sort_expression[0] = $sort_expression_nodirection[0]
                = $sort_direction[0] = '';
        }

        return array($sort_expression, $sort_expression_nodirection,
            $sort_direction
        );

    } // end of the '_getSortParams()' function


    /**
     * Prepare sorted column message
     *
     * @param integer &$dt_result                  the link id associated to the
     *                                              query which results have to
     *                                              be displayed
     * @param string  $sort_expression_nodirection sort expression without direction
     *
     * @return  string                              html content
     *          null                                if not found sorted column
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _getSortedColumnMessage(
        &$dt_result, $sort_expression_nodirection
    ) {

        $fields_meta = $this->__get('fields_meta'); // To use array indexes

        if (! empty($sort_expression_nodirection)) {

            if (strpos($sort_expression_nodirection, '.') === false) {
                $sort_table = $this->__get('table');
                $sort_column = $sort_expression_nodirection;
            } else {
                list($sort_table, $sort_column)
                    = explode('.', $sort_expression_nodirection);
            }

            $sort_table = PMA_Util::unQuote($sort_table);
            $sort_column = PMA_Util::unQuote($sort_column);

            // find the sorted column index in row result
            // (this might be a multi-table query)
            $sorted_column_index = false;

            foreach ($fields_meta as $key => $meta) {
                if (($meta->table == $sort_table) && ($meta->name == $sort_column)) {
                    $sorted_column_index = $key;
                    break;
                }
            }

            if ($sorted_column_index !== false) {

                // fetch first row of the result set
                $row = $GLOBALS['dbi']->fetchRow($dt_result);

                // initializing default arguments
                $default_function = '_mimeDefaultFunction';
                $transformation_plugin = $default_function;
                $transform_options = array();

                // check for non printable sorted row data
                $meta = $fields_meta[$sorted_column_index];

                if (stristr($meta->type, self::BLOB_FIELD)
                    || ($meta->type == self::GEOMETRY_FIELD)
                ) {

                    $column_for_first_row = $this->_handleNonPrintableContents(
                        $meta->type, $row[$sorted_column_index],
                        $transformation_plugin, $transform_options,
                        $default_function, $meta
                    );

                } else {
                    $column_for_first_row = $row[$sorted_column_index];
                }

                $column_for_first_row = strtoupper(
                    substr($column_for_first_row, 0, $GLOBALS['cfg']['LimitChars'])
                );

                // fetch last row of the result set
                $GLOBALS['dbi']->dataSeek($dt_result, $this->__get('num_rows') - 1);
                $row = $GLOBALS['dbi']->fetchRow($dt_result);

                // check for non printable sorted row data
                $meta = $fields_meta[$sorted_column_index];
                if (stristr($meta->type, self::BLOB_FIELD)
                    || ($meta->type == self::GEOMETRY_FIELD)
                ) {

                    $column_for_last_row = $this->_handleNonPrintableContents(
                        $meta->type, $row[$sorted_column_index],
                        $transformation_plugin, $transform_options,
                        $default_function, $meta
                    );

                } else {
                    $column_for_last_row = $row[$sorted_column_index];
                }

                $column_for_last_row = strtoupper(
                    substr($column_for_last_row, 0, $GLOBALS['cfg']['LimitChars'])
                );

                // reset to first row for the loop in _getTableBody()
                $GLOBALS['dbi']->dataSeek($dt_result, 0);

                // we could also use here $sort_expression_nodirection
                return ' [' . htmlspecialchars($sort_column)
                    . ': <strong>' . htmlspecialchars($column_for_first_row) . ' - '
                    . htmlspecialchars($column_for_last_row) . '</strong>]';
            }
        }

        return null;

    } // end of the '_getSortedColumnMessage()' function


    /**
     * Set the content that needs to be shown in message
     *
     * @param string  $sorted_column_message the message for sorted column
     * @param string  $limit_clause          the limit clause of analyzed query
     * @param integer $total                 the total number of rows returned by
     *                                       the SQL query without any
     *                                       programmatically appended LIMIT clause
     * @param integer $pos_next              the offset for next page
     * @param string  $pre_count             the string renders before row count
     * @param string  $after_count           the string renders after row count
     *
     * @return PMA_Message $message an object of PMA_Message
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _setMessageInformation(
        $sorted_column_message, $limit_clause, $total,
        $pos_next, $pre_count, $after_count
    ) {

        $unlim_num_rows = $this->__get('unlim_num_rows'); // To use in isset()

        if (! empty($limit_clause)) {

            $limit_data
                = PMA_Util::analyzeLimitClause($limit_clause);
            $first_shown_rec = $limit_data['start'];

            if ($limit_data['length'] < $total) {
                $last_shown_rec = $limit_data['start'] + $limit_data['length'] - 1;
            } else {
                $last_shown_rec = $limit_data['start'] + $total - 1;
            }

        } elseif (($_SESSION['tmpval']['max_rows'] == self::ALL_ROWS)
            || ($pos_next > $total)
        ) {

            $first_shown_rec = $_SESSION['tmpval']['pos'];
            $last_shown_rec  = $total - 1;

        } else {

            $first_shown_rec = $_SESSION['tmpval']['pos'];
            $last_shown_rec  = $pos_next - 1;

        }

        if (PMA_Table::isView($this->__get('db'), $this->__get('table'))
            && ($total == $GLOBALS['cfg']['MaxExactCountViews'])
        ) {

            $message = PMA_Message::notice(
                __(
                    'This view has at least this number of rows. '
                    . 'Please refer to %sdocumentation%s.'
                )
            );

            $message->addParam('[doc@cfg_MaxExactCount]');
            $message->addParam('[/doc]');
            $message_view_warning = PMA_Util::showHint($message);

        } else {
            $message_view_warning = false;
        }

        $message = PMA_Message::success(__('Showing rows %1s - %2s'));
        $message->addParam($first_shown_rec);

        if ($message_view_warning) {
            $message->addParam('... ' . $message_view_warning, false);
        } else {
            $message->addParam($last_shown_rec);
        }

        $message->addMessage('(');

        if (!$message_view_warning) {

            if (isset($unlim_num_rows) && ($unlim_num_rows != $total)) {
                $message_total = PMA_Message::notice(
                    $pre_count . __('%1$d total, %2$d in query')
                );
                $message_total->addParam($total);
                $message_total->addParam($unlim_num_rows);
            } else {
                $message_total = PMA_Message::notice($pre_count . __('%d total'));
                $message_total->addParam($total);
            }

            if (!empty($after_count)) {
                $message_total->addMessage($after_count);
            }
            $message->addMessage($message_total, '');

            $message->addMessage(', ', '');
        }

        $message_qt = PMA_Message::notice(__('Query took %01.4f seconds.') . ')');
        $message_qt->addParam($this->__get('querytime'));

        $message->addMessage($message_qt, '');
        if (! is_null($sorted_column_message)) {
            $message->addMessage($sorted_column_message, '');
        }

        return $message;

    } // end of the '_setMessageInformation()' function


    /**
     * Set the value of $map array for linking foreign key related tables
     *
     * @param array &$map the list of relations
     *
     * @return  void
     *
     * @access  private
     *
     * @see      getTable()
     */
    private function _setParamForLinkForeignKeyRelatedTables(&$map)
    {

        // To be able to later display a link to the related table,
        // we verify both types of relations: either those that are
        // native foreign keys or those defined in the phpMyAdmin
        // configuration storage. If no PMA storage, we won't be able
        // to use the "column to display" notion (for example show
        // the name related to a numeric id).
        $exist_rel = PMA_getForeigners(
            $this->__get('db'), $this->__get('table'), '', self::POSITION_BOTH
        );

        if ($exist_rel) {

            foreach ($exist_rel as $master_field => $rel) {

                $display_field = PMA_getDisplayField(
                    $rel['foreign_db'], $rel['foreign_table']
                );

                $map[$master_field] = array(
                        $rel['foreign_table'],
                        $rel['foreign_field'],
                        $display_field,
                        $rel['foreign_db']
                    );
            } // end while
        } // end if

    } // end of the '_setParamForLinkForeignKeyRelatedTables()' function


    /**
     * Prepare multi field edit/delete links
     *
     * @param integer &$dt_result   the link id associated to the query
     *                              which results have to be displayed
     * @param array   $analyzed_sql the analyzed query
     * @param string  $del_link     the display element - 'del_link'
     *
     * @return string $links_html html content
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _getMultiRowOperationLinks(
        &$dt_result, $analyzed_sql, $del_link
    ) {

        $links_html = '';
        $url_query = $this->__get('url_query');
        $delete_text = ($del_link == self::DELETE_ROW) ? __('Delete') : __('Kill');

        if ($_SESSION['tmpval']['disp_direction'] != self::DISP_DIR_VERTICAL) {

            $links_html .= '<img class="selectallarrow" width="38" height="22"'
                . ' src="' . $this->__get('pma_theme_image') . 'arrow_'
                . $this->__get('text_dir') . '.png' . '"'
                . ' alt="' . __('With selected:') . '" />';
        }

        $links_html .= '<input type="checkbox" id="resultsForm_checkall" '
            . 'class="checkall_box" title="' . __('Check All') . '" /> '
            . '<label for="resultsForm_checkall">' . __('Check All') . '</label> '
            . '<i style="margin-left: 2em">' . __('With selected:') . '</i>' . "\n";

        $links_html .= PMA_Util::getButtonOrImage(
            'submit_mult', 'mult_submit', 'submit_mult_change',
            __('Change'), 'b_edit.png', 'edit'
        );

        $links_html .= PMA_Util::getButtonOrImage(
            'submit_mult', 'mult_submit', 'submit_mult_delete',
            $delete_text, 'b_drop.png', 'delete'
        );

        if (isset($analyzed_sql[0])
            && $analyzed_sql[0]['querytype'] == self::QUERY_TYPE_SELECT
        ) {
            $links_html .= PMA_Util::getButtonOrImage(
                'submit_mult', 'mult_submit', 'submit_mult_export',
                __('Export'), 'b_tblexport.png', 'export'
            );
        }

        $links_html .= "\n";

        $links_html .= '<input type="hidden" name="sql_query"'
            . ' value="' . htmlspecialchars($this->__get('sql_query')) . '" />'
            . "\n";

        if (! empty($url_query)) {
            $links_html .= '<input type="hidden" name="url_query"'
                . ' value="' . $url_query . '" />' . "\n";
        }

        // fetch last row of the result set
        $GLOBALS['dbi']->dataSeek($dt_result, $this->__get('num_rows') - 1);
        $row = $GLOBALS['dbi']->fetchRow($dt_result);

        // $clause_is_unique is needed by getTable() to generate the proper param
        // in the multi-edit and multi-delete form
        list($where_clause, $clause_is_unique, $condition_array)
            = PMA_Util::getUniqueCondition(
                $dt_result,
                $this->__get('fields_cnt'),
                $this->__get('fields_meta'),
                $row
            );
        unset($where_clause, $condition_array);

        // reset to first row for the loop in _getTableBody()
        $GLOBALS['dbi']->dataSeek($dt_result, 0);

        $links_html .= '<input type="hidden" name="clause_is_unique"'
            . ' value="' . $clause_is_unique . '" />' . "\n";

        $links_html .= '</form>' . "\n";

        return $links_html;

    } // end of the '_getMultiRowOperationLinks()' function


    /**
     * Prepare table navigation bar at the top or bottom
     *
     * @param integer $pos_next   the offset for the "next" page
     * @param integer $pos_prev   the offset for the "previous" page
     * @param string  $place      the place to show navigation
     * @param string  $empty_line empty line depend on the $place
     * @param boolean $is_innodb  whether its InnoDB or not
     *
     * @return  string  html content of navigation bar
     *
     * @access  private
     *
     * @see     _getTable()
     */
    private function _getPlacedTableNavigations(
        $pos_next, $pos_prev, $place, $empty_line, $is_innodb
    ) {

        $navigation_html = '';

        if ($place == self::PLACE_BOTTOM_DIRECTION_DROPDOWN) {
            $navigation_html .= '<br />' . "\n";
        }

        $navigation_html .= $this->_getTableNavigation(
            $pos_next, $pos_prev, 'top_direction_dropdown', $is_innodb
        );

        if ($place == self::PLACE_TOP_DIRECTION_DROPDOWN) {
            $navigation_html .= "\n";
        }

        return $navigation_html;

    } // end of the '_getPlacedTableNavigations()' function

    /**
     * Generates HTML to display the Create view in span tag
     *
     * @param array  $analyzed_sql the analyzed Query
     * @param string $url_query    String with URL Parameters
     *
     * @return string
     *
     * @access private
     *
     * @see _getResultsOperations()
     */
    private function _getLinkForCreateView($analyzed_sql, $url_query)
    {
        $results_operations_html = '';
        if (!PMA_DRIZZLE && !isset($analyzed_sql[0]['queryflags']['procedure'])) {

            $ajax_class = ' ajax';

            $results_operations_html .= '<span>'
                . PMA_Util::linkOrButton(
                    'view_create.php' . $url_query,
                    PMA_Util::getIcon(
                        'b_views.png', __('Create view'), true
                    ),
                    array('class' => 'create_view' . $ajax_class), true, true, ''
                )
                . '</span>' . "\n";
        }
        return $results_operations_html;

    }

    /**
     * Calls the _getResultsOperations with $only_view as true
     *
     * @param array $analyzed_sql the analyzed Query
     *
     * @return string
     *
     * @access public
     *
     */
    public function getCreateViewQueryResultOp($analyzed_sql)
    {

        $results_operations_html = '';
        $fake_display_mode       = array();
        //calling to _getResultOperations with a fake display mode
        //and setting only_view parameter to be true to generate just view
        $results_operations_html .= $this->_getResultsOperations(
            $fake_display_mode,
            $analyzed_sql,
            true
        );
        return $results_operations_html;
    }

    /**
     * Get operations that are available on results.
     *
     * @param array   $the_disp_mode the display mode
     * @param array   $analyzed_sql  the analyzed query
     * @param boolean $only_view     Whether to show only view
     *
     * @return string $results_operations_html  html content
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _getResultsOperations(
        $the_disp_mode, $analyzed_sql, $only_view = false
    ) {
        global $printview;

        $results_operations_html = '';
        $fields_meta = $this->__get('fields_meta'); // To safe use in foreach
        $header_shown = false;
        $header = '<fieldset><legend>' . __('Query results operations')
            . '</legend>';

        $_url_params = array(
                    'db'        => $this->__get('db'),
                    'table'     => $this->__get('table'),
                    'printview' => '1',
                    'sql_query' => $this->__get('sql_query'),
                );
        $url_query = PMA_URL_getCommon($_url_params);

        if (!$header_shown) {
            $results_operations_html .= $header;
            $header_shown = true;
        }
        // if empty result set was produced we need to
        // show only view and not other options
        if ($only_view == true) {
            $results_operations_html .= $this->_getLinkForCreateView(
                $analyzed_sql, $url_query
            );

            if ($header_shown) {
                $results_operations_html .= '</fieldset><br />';
            }
            return $results_operations_html;
        }

        if (($the_disp_mode[6] == '1') || ($the_disp_mode[9] == '1')) {
            // Displays "printable view" link if required
            if ($the_disp_mode[9] == '1') {

                $results_operations_html
                    .= PMA_Util::linkOrButton(
                        'sql.php' . $url_query,
                        PMA_Util::getIcon(
                            'b_print.png', __('Print view'), true
                        ),
                        array('target' => 'print_view'),
                        true,
                        true,
                        'print_view'
                    )
                    . "\n";

                if ($_SESSION['tmpval']['pftext']) {

                    $_url_params['pftext'] = self::DISPLAY_FULL_TEXT;

                    $results_operations_html
                        .= PMA_Util::linkOrButton(
                            'sql.php' . PMA_URL_getCommon($_url_params),
                            PMA_Util::getIcon(
                                'b_print.png',
                                __('Print view (with full texts)'), true
                            ),
                            array('target' => 'print_view'),
                            true,
                            true,
                            'print_view'
                        )
                        . "\n";
                    unset($_url_params['pftext']);
                }
            } // end displays "printable view"
        }

        // Export link
        // (the url_query has extra parameters that won't be used to export)
        // (the single_table parameter is used in display_export.inc.php
        //  to hide the SQL and the structure export dialogs)
        // If the parser found a PROCEDURE clause
        // (most probably PROCEDURE ANALYSE()) it makes no sense to
        // display the Export link).
        if (isset($analyzed_sql[0])
            && ($analyzed_sql[0]['querytype'] == self::QUERY_TYPE_SELECT)
            && ! isset($printview)
            && ! isset($analyzed_sql[0]['queryflags']['procedure'])
        ) {

            if (isset($analyzed_sql[0]['table_ref'][0]['table_true_name'])
                && ! isset($analyzed_sql[0]['table_ref'][1]['table_true_name'])
            ) {
                $_url_params['single_table'] = 'true';
            }

            if (! $header_shown) {
                $results_operations_html .= $header;
                $header_shown = true;
            }

            $_url_params['unlim_num_rows'] = $this->__get('unlim_num_rows');

            /**
             * At this point we don't know the table name; this can happen
             * for example with a query like
             * SELECT bike_code FROM (SELECT bike_code FROM bikes) tmp
             * As a workaround we set in the table parameter the name of the
             * first table of this database, so that tbl_export.php and
             * the script it calls do not fail
             */
            if (empty($_url_params['table']) && ! empty($_url_params['db'])) {
                $_url_params['table'] = $GLOBALS['dbi']->fetchValue("SHOW TABLES");
                /* No result (probably no database selected) */
                if ($_url_params['table'] === false) {
                    unset($_url_params['table']);
                }
            }

            $results_operations_html .= PMA_Util::linkOrButton(
                'tbl_export.php' . PMA_URL_getCommon($_url_params),
                PMA_Util::getIcon(
                    'b_tblexport.png', __('Export'), true
                ),
                '',
                true,
                true,
                ''
            )
            . "\n";

            // prepare chart
            $results_operations_html .= PMA_Util::linkOrButton(
                'tbl_chart.php' . PMA_URL_getCommon($_url_params),
                PMA_Util::getIcon(
                    'b_chart.png', __('Display chart'), true
                ),
                '',
                true,
                true,
                ''
            )
            . "\n";

            // prepare GIS chart
            $geometry_found = false;
            // If atleast one geometry field is found
            foreach ($fields_meta as $meta) {
                if ($meta->type == self::GEOMETRY_FIELD) {
                    $geometry_found = true;
                    break;
                }
            }

            if ($geometry_found) {
                $results_operations_html
                    .= PMA_Util::linkOrButton(
                        'tbl_gis_visualization.php'
                        . PMA_URL_getCommon($_url_params),
                        PMA_Util::getIcon(
                            'b_globe.gif', __('Visualize GIS data'), true
                        ),
                        '',
                        true,
                        true,
                        ''
                    )
                    . "\n";
            }
        }

        // CREATE VIEW
        /**
         *
         * @todo detect privileges to create a view
         *       (but see 2006-01-19 note in display_create_table.lib.php,
         *        I think we cannot detect db-specific privileges reliably)
         * Note: we don't display a Create view link if we found a PROCEDURE clause
         */
        if (!$header_shown) {
            $results_operations_html .= $header;
            $header_shown = true;
        }

        $results_operations_html .= $this->_getLinkForCreateView(
            $analyzed_sql, $url_query
        );

        if ($header_shown) {
            $results_operations_html .= '</fieldset><br />';
        }

        return $results_operations_html;

    } // end of the '_getResultsOperations()' function


    /**
     * Verifies what to do with non-printable contents (binary or BLOB)
     * in Browse mode.
     *
     * @param string $category              BLOB|BINARY|GEOMETRY
     * @param string $content               the binary content
     * @param string $transformation_plugin transformation plugin.
     *                                      Can also be the default function:
     *                                      PMA_mimeDefaultFunction
     * @param array  $transform_options     transformation parameters
     * @param string $default_function      default transformation function
     * @param object $meta                  the meta-information about the field
     * @param array  $url_params            parameters that should go to the
     *                                      download link
     *
     * @return mixed  string or float
     *
     * @access  private
     *
     * @see     _getDataCellForBlobColumns(),
     *          _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericAndNonBlobColumns(),
     *          _getSortedColumnMessage()
     */
    private function _handleNonPrintableContents(
        $category, $content, $transformation_plugin, $transform_options,
        $default_function, $meta, $url_params = array()
    ) {

        $result = '[' . $category;

        if (isset($content)) {

            $size = strlen($content);
            $display_size = PMA_Util::formatByteDown($size, 3, 1);
            $result .= ' - ' . $display_size[0] . ' ' . $display_size[1];

        } else {

            $result .= ' - NULL';
            $size = 0;

        }

        $result .= ']';

        // if we want to use a text transformation on a BLOB column
        if (gettype($transformation_plugin) == "object"
            && (strpos($transformation_plugin->getMIMESubtype(), 'Octetstream')
            || strpos($transformation_plugin->getMIMEtype(), 'Text') !== false)
        ) {
            $result = $content;
        }

        if ($size > 0) {

            if ($default_function != $transformation_plugin) {
                $result = $transformation_plugin->applyTransformation(
                    $result,
                    $transform_options,
                    $meta
                );
            } else {

                $result = $this->$default_function($result, array(), $meta);
                if (stristr($meta->type, self::BLOB_FIELD)
                    && $_SESSION['tmpval']['display_blob']
                ) {
                    // in this case, restart from the original $content
                    $result = $this->_displayBinaryAsPrintable($content, 'blob');
                }

                /* Create link to download */
                if (count($url_params) > 0) {
                    $result = '<a href="tbl_get_field.php'
                        . PMA_URL_getCommon($url_params)
                        . '" class="disableAjax">'
                        . $result . '</a>';
                }
            }
        }

        return($result);

    } // end of the '_handleNonPrintableContents()' function


    /**
     * Prepares the displayable content of a data cell in Browse mode,
     * taking into account foreign key description field and transformations
     *
     * @param string $class                 css classes for the td element
     * @param bool   $condition_field       whether the column is a part of the
     *                                      where clause
     * @param array  $analyzed_sql          the analyzed query
     * @param object $meta                  the meta-information about the field
     * @param array  $map                   the list of relations
     * @param string $data                  data
     * @param string $transformation_plugin transformation plugin.
     *                                      Can also be the default function:
     *                                      PMA_mimeDefaultFunction
     * @param string $default_function      default function
     * @param string $nowrap                'nowrap' if the content should not
     *                                      be wrapped
     * @param string $where_comparison      data for the where clause
     * @param array  $transform_options     array of options for transformation
     * @param bool   $is_field_truncated    whether the field is truncated
     *
     * @return string  formatted data
     *
     * @access  private
     *
     * @see     _getDataCellForNumericColumns(), _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericAndNonBlobColumns(),
     *
     */
    private function _getRowData(
        $class, $condition_field, $analyzed_sql, $meta, $map, $data,
        $transformation_plugin, $default_function, $nowrap, $where_comparison,
        $transform_options, $is_field_truncated
    ) {

        $relational_display = $_SESSION['tmpval']['relational_display'];
        $printview = $this->__get('printview');
        $result = '<td data-decimals="' . $meta->decimals . '" data-type="'
            . $meta->type . '" class="'
            . $this->_addClass(
                $class, $condition_field, $meta, $nowrap,
                $is_field_truncated, $transformation_plugin, $default_function
            )
            . '">';

        if (isset($analyzed_sql[0]['select_expr'])
            && is_array($analyzed_sql[0]['select_expr'])
        ) {

            foreach ($analyzed_sql[0]['select_expr']
                as $select_expr_position => $select_expr
            ) {

                $alias = $analyzed_sql[0]['select_expr']
                    [$select_expr_position]['alias'];

                if (!isset($alias) || !strlen($alias)) {
                    continue;
                } // end if

                $true_column = $analyzed_sql[0]['select_expr']
                    [$select_expr_position]['column'];

                if ($alias == $meta->name) {
                    // this change in the parameter does not matter
                    // outside of the function
                    $meta->name = $true_column;
                } // end if

            } // end foreach
        } // end if

        if (isset($map[$meta->name])) {

            // Field to display from the foreign table?
            if (isset($map[$meta->name][2]) && strlen($map[$meta->name][2])) {

                $dispsql = 'SELECT '
                    . PMA_Util::backquote($map[$meta->name][2])
                    . ' FROM '
                    . PMA_Util::backquote($map[$meta->name][3])
                    . '.'
                    . PMA_Util::backquote($map[$meta->name][0])
                    . ' WHERE '
                    . PMA_Util::backquote($map[$meta->name][1])
                    . $where_comparison;

                $dispresult = $GLOBALS['dbi']->tryQuery(
                    $dispsql,
                    null,
                    PMA_DatabaseInterface::QUERY_STORE
                );

                if ($dispresult && $GLOBALS['dbi']->numRows($dispresult) > 0) {
                    list($dispval) = $GLOBALS['dbi']->fetchRow($dispresult, 0);
                } else {
                    $dispval = __('Link not found!');
                }

                @$GLOBALS['dbi']->freeResult($dispresult);

            } else {
                $dispval     = '';
            } // end if... else...

            if (isset($printview) && ($printview == '1')) {

                $result .= ($transformation_plugin != $default_function
                    ? $transformation_plugin->applyTransformation(
                        $data,
                        $transform_options,
                        $meta
                    )
                    : $this->$default_function($data)
                )
                . ' <code>[-&gt;' . $dispval . ']</code>';

            } else {

                if ($relational_display == self::RELATIONAL_KEY) {

                    // user chose "relational key" in the display options, so
                    // the title contains the display field
                    $title = (! empty($dispval))
                        ? ' title="' . htmlspecialchars($dispval) . '"'
                        : '';

                } else {
                    $title = ' title="' . htmlspecialchars($data) . '"';
                }

                $_url_params = array(
                    'db'    => $map[$meta->name][3],
                    'table' => $map[$meta->name][0],
                    'pos'   => '0',
                    'sql_query' => 'SELECT * FROM '
                        . PMA_Util::backquote(
                            $map[$meta->name][3]
                        ) . '.'
                        . PMA_Util::backquote(
                            $map[$meta->name][0]
                        )
                        . ' WHERE '
                        . PMA_Util::backquote(
                            $map[$meta->name][1]
                        )
                        . $where_comparison,
                );

                $result .= '<a class="ajax" href="sql.php'
                    . PMA_URL_getCommon($_url_params)
                    . '"' . $title . '>';

                if ($transformation_plugin != $default_function) {
                    // always apply a transformation on the real data,
                    // not on the display field
                    $result .= $transformation_plugin->applyTransformation(
                        $data,
                        $transform_options,
                        $meta
                    );
                } else {

                    if ($relational_display == self::RELATIONAL_DISPLAY_COLUMN) {
                        // user chose "relational display field" in the
                        // display options, so show display field in the cell
                        $result .= $this->$default_function($dispval);
                    } else {
                        // otherwise display data in the cell
                        $result .= $this->$default_function($data);
                    }

                }
                $result .= '</a>';
            }

        } else {
            $result .= ($transformation_plugin != $default_function
                ? $transformation_plugin->applyTransformation(
                    $data,
                    $transform_options,
                    $meta
                )
                : $this->$default_function($data)
            );
        }

        // create hidden field if results from structure table
        if (isset($_GET['browse_distinct']) && ($_GET['browse_distinct'] == 1)) {

            $where_comparison = " = '" . $data . "'";

            $_url_params_for_show_data_row = array(
                'db'    => $this->__get('db'),
                'table' => $meta->orgtable,
                'pos'   => '0',
                'sql_query' => 'SELECT * FROM '
                    . PMA_Util::backquote($this->__get('db'))
                    . '.' . PMA_Util::backquote($meta->orgtable)
                    . ' WHERE '
                    . PMA_Util::backquote($meta->orgname)
                    . $where_comparison,
            );

            $result .= '<input type="hidden" class="data_browse_link" value="'
                . PMA_URL_getCommon($_url_params_for_show_data_row) . '" />';

        }

        $result .= '</td>' . "\n";

        return $result;

    } // end of the '_getRowData()' function


    /**
     * Prepares a checkbox for multi-row submits
     *
     * @param string $del_url           delete url
     * @param array  $is_display        array with explicit indexes for all
     *                                  the display elements
     * @param string $row_no            the row number
     * @param string $where_clause_html url encoded where clause
     * @param array  $condition_array   array of conditions in the where clause
     * @param string $del_query         delete query
     * @param string $id_suffix         suffix for the id
     * @param string $class             css classes for the td element
     *
     * @return string  the generated HTML
     *
     * @access  private
     *
     * @see     _getTableBody(), _getCheckboxAndLinks()
     */
    private function _getCheckboxForMultiRowSubmissions(
        $del_url, $is_display, $row_no, $where_clause_html, $condition_array,
        $del_query, $id_suffix, $class
    ) {

        $ret = '';

        if (! empty($del_url) && $is_display['del_lnk'] != self::KILL_PROCESS) {

            $ret .= '<td ';
            if (! empty($class)) {
                $ret .= 'class="' . $class . '"';
            }

            $ret .= ' class="center">'
                . '<input type="checkbox" id="id_rows_to_delete'
                . $row_no . $id_suffix
                . '" name="rows_to_delete[' . $row_no . ']"'
                . ' class="multi_checkbox checkall"'
                . ' value="' . $where_clause_html . '" '
                . ' />'
                . '<input type="hidden" class="condition_array" value="'
                . htmlspecialchars(json_encode($condition_array)) . '" />'
                . '    </td>';
        }

        return $ret;

    } // end of the '_getCheckboxForMultiRowSubmissions()' function


    /**
     * Prepares an Edit link
     *
     * @param string $edit_url          edit url
     * @param string $class             css classes for td element
     * @param string $edit_str          text for the edit link
     * @param string $where_clause      where clause
     * @param string $where_clause_html url encoded where clause
     *
     * @return string  the generated HTML
     *
     * @access  private
     *
     * @see     _getTableBody(), _getCheckboxAndLinks()
     */
    private function _getEditLink(
        $edit_url, $class, $edit_str, $where_clause, $where_clause_html
    ) {

        $ret = '';
        if (! empty($edit_url)) {

            $ret .= '<td class="' . $class . ' center" ' . ' ><span class="nowrap">'
               . PMA_Util::linkOrButton(
                   $edit_url, $edit_str, array(), false
               );
            /*
             * Where clause for selecting this row uniquely is provided as
             * a hidden input. Used by jQuery scripts for handling grid editing
             */
            if (! empty($where_clause)) {
                $ret .= '<input type="hidden" class="where_clause" value ="'
                    . $where_clause_html . '" />';
            }
            $ret .= '</span></td>';
        }

        return $ret;

    } // end of the '_getEditLink()' function


    /**
     * Prepares an Copy link
     *
     * @param string $copy_url          copy url
     * @param string $copy_str          text for the copy link
     * @param string $where_clause      where clause
     * @param string $where_clause_html url encoded where clause
     * @param string $class             css classes for the td element
     *
     * @return string  the generated HTML
     *
     * @access  private
     *
     * @see     _getTableBody(), _getCheckboxAndLinks()
     */
    private function _getCopyLink(
        $copy_url, $copy_str, $where_clause, $where_clause_html, $class
    ) {

        $ret = '';
        if (! empty($copy_url)) {

            $ret .= '<td class="';
            if (! empty($class)) {
                $ret .= $class . ' ';
            }

            $ret .= 'center" ' . ' ><span class="nowrap">'
               . PMA_Util::linkOrButton(
                   $copy_url, $copy_str, array(), false
               );

            /*
             * Where clause for selecting this row uniquely is provided as
             * a hidden input. Used by jQuery scripts for handling grid editing
             */
            if (! empty($where_clause)) {
                $ret .= '<input type="hidden" class="where_clause" value="'
                    . $where_clause_html . '" />';
            }
            $ret .= '</span></td>';
        }

        return $ret;

    } // end of the '_getCopyLink()' function


    /**
     * Prepares a Delete link
     *
     * @param string $del_url delete url
     * @param string $del_str text for the delete link
     * @param string $js_conf text for the JS confirmation
     * @param string $class   css classes for the td element
     *
     * @return string  the generated HTML
     *
     * @access  private
     *
     * @see     _getTableBody(), _getCheckboxAndLinks()
     */
    private function _getDeleteLink($del_url, $del_str, $js_conf, $class)
    {

        $ret = '';
        if (! empty($del_url)) {

            $ret .= '<td class="';
            if (! empty($class)) {
                $ret .= $class . ' ';
            }
            $ajax = PMA_Response::getInstance()->isAjax() ? ' ajax' : '';
            $ret .= 'center" ' . ' >'
               . PMA_Util::linkOrButton(
                   $del_url, $del_str, array('class' => 'delete_row' . $ajax), false
               )
               . '<div class="hide">' . $js_conf . '</div>'
               . '</td>';
        }

        return $ret;

    } // end of the '_getDeleteLink()' function


    /**
     * Prepare checkbox and links at some position (left or right)
     * (only called for horizontal mode)
     *
     * @param string $position          the position of the checkbox and links
     * @param string $del_url           delete url
     * @param array  $is_display        array with explicit indexes for all the
     *                                  display elements
     * @param string $row_no            row number
     * @param string $where_clause      where clause
     * @param string $where_clause_html url encoded where clause
     * @param array  $condition_array   array of conditions in the where clause
     * @param string $del_query         delete query
     * @param string $id_suffix         suffix for the id
     * @param string $edit_url          edit url
     * @param string $copy_url          copy url
     * @param string $class             css classes for the td elements
     * @param string $edit_str          text for the edit link
     * @param string $copy_str          text for the copy link
     * @param string $del_str           text for the delete link
     * @param string $js_conf           text for the JS confirmation
     *
     * @return string  the generated HTML
     *
     * @access  private
     *
     * @see     _getPlacedLinks()
     */
    private function _getCheckboxAndLinks(
        $position, $del_url, $is_display, $row_no, $where_clause,
        $where_clause_html, $condition_array, $del_query, $id_suffix,
        $edit_url, $copy_url, $class, $edit_str, $copy_str, $del_str, $js_conf
    ) {

        $ret = '';

        if ($position == self::POSITION_LEFT) {

            $ret .= $this->_getCheckboxForMultiRowSubmissions(
                $del_url, $is_display, $row_no, $where_clause_html, $condition_array,
                $del_query, $id_suffix = '_left', ''
            );

            $ret .= $this->_getEditLink(
                $edit_url, $class, $edit_str, $where_clause, $where_clause_html
            );

            $ret .= $this->_getCopyLink(
                $copy_url, $copy_str, $where_clause, $where_clause_html, ''
            );

            $ret .= $this->_getDeleteLink($del_url, $del_str, $js_conf, '');

        } elseif ($position == self::POSITION_RIGHT) {

            $ret .= $this->_getDeleteLink($del_url, $del_str, $js_conf, '');

            $ret .= $this->_getCopyLink(
                $copy_url, $copy_str, $where_clause, $where_clause_html, ''
            );

            $ret .= $this->_getEditLink(
                $edit_url, $class, $edit_str, $where_clause, $where_clause_html
            );

            $ret .= $this->_getCheckboxForMultiRowSubmissions(
                $del_url, $is_display, $row_no, $where_clause_html, $condition_array,
                $del_query, $id_suffix = '_right', ''
            );

        } else { // $position == self::POSITION_NONE

            $ret .= $this->_getCheckboxForMultiRowSubmissions(
                $del_url, $is_display, $row_no, $where_clause_html, $condition_array,
                $del_query, $id_suffix = '_left', ''
            );
        }

        return $ret;

    } // end of the '_getCheckboxAndLinks()' function


    /**
     * Replace some html-unfriendly stuff
     *
     * @param string $buffer String to process
     *
     * @return String Escaped and cleaned up text suitable for html.
     *
     * @access private
     *
     * @see    _getDataCellForBlobField(), _getRowData(),
     *         _handleNonPrintableContents()
     */
    private function _mimeDefaultFunction($buffer)
    {
        $buffer = htmlspecialchars($buffer);
        $buffer = str_replace('  ', ' &nbsp;', $buffer);
        $buffer = preg_replace("@((\015\012)|(\015)|(\012))@", '<br />', $buffer);

        return $buffer;
    }

    /**
     * Display binary columns as hex string if requested
     * otherwise escape the contents using the best possible way
     *
     * @param string $content        String to parse
     * @param string $binary_or_blob binary' or 'blob'
     * @param int    $hexlength      optional, get substring
     *
     * @return String Displayable version of the binary string
     *
     * @access private
     *
     * @see    _getDataCellForGeometryColumns
     *         _getDataCellForNonNumericAndNonBlobColumns
     *         _handleNonPrintableContents
     */
    private function _displayBinaryAsPrintable(
        $content, $binary_or_blob, $hexlength = null
    ) {
        if ($binary_or_blob === 'binary'
            && $_SESSION['tmpval']['display_binary_as_hex']
        ) {
            $content = bin2hex($content);
            if ($hexlength !== null) {
                $content = $GLOBALS['PMA_String']->substr($content, $hexlength);
            }
        } elseif (PMA_Util::containsNonPrintableAscii($content)) {
            if (PMA_PHP_INT_VERSION < 50400) {
                $content = htmlspecialchars(
                    PMA_Util::replaceBinaryContents(
                        $content
                    )
                );
            } else {
                // The ENT_SUBSTITUTE option is available for PHP >= 5.4.0
                $content = htmlspecialchars(
                    PMA_Util::replaceBinaryContents(
                        $content
                    ),
                    ENT_SUBSTITUTE
                );
            }
        }
        return $content;
    }
}

?>
