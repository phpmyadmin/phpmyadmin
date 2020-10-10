<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Hold the PhpMyAdmin\Display\Results class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Display;

use PhpMyAdmin\Config\SpecialSchemaLinks;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Octetstream_Sql;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Json;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_Sql;
use PhpMyAdmin\Plugins\Transformations\Text_Plain_Link;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Sql;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use stdClass;

/**
 * Handle all the functionalities related to displaying results
 * of sql queries, stored procedure, browsing sql processes or
 * displaying binary log.
 *
 * @package PhpMyAdmin
 */
class Results
{
    // Define constants
    public const NO_EDIT_OR_DELETE = 'nn';
    public const UPDATE_ROW = 'ur';
    public const DELETE_ROW = 'dr';
    public const KILL_PROCESS = 'kp';

    public const POSITION_LEFT = 'left';
    public const POSITION_RIGHT = 'right';
    public const POSITION_BOTH = 'both';
    public const POSITION_NONE = 'none';

    public const DISPLAY_FULL_TEXT = 'F';
    public const DISPLAY_PARTIAL_TEXT = 'P';

    public const HEADER_FLIP_TYPE_AUTO = 'auto';
    public const HEADER_FLIP_TYPE_CSS = 'css';
    public const HEADER_FLIP_TYPE_FAKE = 'fake';

    public const DATE_FIELD = 'date';
    public const DATETIME_FIELD = 'datetime';
    public const TIMESTAMP_FIELD = 'timestamp';
    public const TIME_FIELD = 'time';
    public const STRING_FIELD = 'string';
    public const GEOMETRY_FIELD = 'geometry';
    public const BLOB_FIELD = 'BLOB';
    public const BINARY_FIELD = 'BINARY';

    public const RELATIONAL_KEY = 'K';
    public const RELATIONAL_DISPLAY_COLUMN = 'D';

    public const GEOMETRY_DISP_GEOM = 'GEOM';
    public const GEOMETRY_DISP_WKT = 'WKT';
    public const GEOMETRY_DISP_WKB = 'WKB';

    public const SMART_SORT_ORDER = 'SMART';
    public const ASCENDING_SORT_DIR = 'ASC';
    public const DESCENDING_SORT_DIR = 'DESC';

    public const TABLE_TYPE_INNO_DB = 'InnoDB';
    public const ALL_ROWS = 'all';
    public const QUERY_TYPE_SELECT = 'SELECT';

    public const ROUTINE_PROCEDURE = 'procedure';
    public const ROUTINE_FUNCTION = 'function';

    public const ACTION_LINK_CONTENT_ICONS = 'icons';
    public const ACTION_LINK_CONTENT_TEXT = 'text';

    // Declare global fields

    /** array with properties of the class */
    private $_property_array = [

        /** integer server id */
        'server' => null,

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

        /** boolean */
        'is_browse_distinct' => null,

        /** array table definitions */
        'showtable' => null,

        /** string */
        'printview' => null,

        /** string URL query */
        'url_query' => null,

        /** array column names to highlight */
        'highlight_columns' => null,

        /** array holding various display information */
        'display_params' => null,

        /** array mime types information of fields */
        'mime_map' => null,

        /** boolean */
        'editable' => null,

        /** random unique ID to distinguish result set */
        'unique_id' => null,

        /** where clauses for each row, each table in the row */
        'whereClauseMap' => [],
    ];

    /**
     * This variable contains the column transformation information
     * for some of the system databases.
     * One element of this array represent all relevant columns in all tables in
     * one specific database
     */
    public $transformation_info;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * @var Transformations
     */
    private $transformations;

    /**
     * @var Template
     */
    public $template;

    /**
     * Constructor for PhpMyAdmin\Display\Results class
     *
     * @param string $db        the database name
     * @param string $table     the table name
     * @param int    $server    the server id
     * @param string $goto      the URL to go back in case of errors
     * @param string $sql_query the SQL query
     *
     * @access  public
     */
    public function __construct($db, $table, $server, $goto, $sql_query)
    {
        $this->relation = new Relation($GLOBALS['dbi']);
        $this->transformations = new Transformations();
        $this->template = new Template();

        $this->_setDefaultTransformations();

        $this->__set('db', $db);
        $this->__set('table', $table);
        $this->__set('server', $server);
        $this->__set('goto', $goto);
        $this->__set('sql_query', $sql_query);
        $this->__set('unique_id', mt_rand());
    }

    /**
     * Get any property of this class
     *
     * @param string $property name of the property
     *
     * @return mixed|void if property exist, value of the relevant property
     */
    public function __get($property)
    {
        return $this->_property_array[$property] ?? null;
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
     * Sets default transformations for some columns
     *
     * @return void
     */
    private function _setDefaultTransformations()
    {
        $json_highlighting_data = [
            'libraries/classes/Plugins/Transformations/Output/Text_Plain_Json.php',
            Text_Plain_Json::class,
            'Text_Plain',
        ];
        $sql_highlighting_data = [
            'libraries/classes/Plugins/Transformations/Output/Text_Plain_Sql.php',
            Text_Plain_Sql::class,
            'Text_Plain',
        ];
        $blob_sql_highlighting_data = [
            'libraries/classes/Plugins/Transformations/Output/Text_Octetstream_Sql.php',
            Text_Octetstream_Sql::class,
            'Text_Octetstream',
        ];
        $link_data = [
            'libraries/classes/Plugins/Transformations/Text_Plain_Link.php',
            Text_Plain_Link::class,
            'Text_Plain',
        ];
        $this->transformation_info = [
            'information_schema' => [
                'events' => [
                    'event_definition' => $sql_highlighting_data,
                ],
                'processlist' => [
                    'info' => $sql_highlighting_data,
                ],
                'routines' => [
                    'routine_definition' => $sql_highlighting_data,
                ],
                'triggers' => [
                    'action_statement' => $sql_highlighting_data,
                ],
                'views' => [
                    'view_definition' => $sql_highlighting_data,
                ],
            ],
            'mysql' => [
                'event' => [
                    'body' => $blob_sql_highlighting_data,
                    'body_utf8' => $blob_sql_highlighting_data,
                ],
                'general_log' => [
                    'argument' => $sql_highlighting_data,
                ],
                'help_category' => [
                    'url' => $link_data,
                ],
                'help_topic' => [
                    'example' => $sql_highlighting_data,
                    'url' => $link_data,
                ],
                'proc' => [
                    'param_list' => $blob_sql_highlighting_data,
                    'returns' => $blob_sql_highlighting_data,
                    'body' => $blob_sql_highlighting_data,
                    'body_utf8' => $blob_sql_highlighting_data,
                ],
                'slow_log' => [
                    'sql_text' => $sql_highlighting_data,
                ],
            ],
        ];

        $cfgRelation = $this->relation->getRelationsParam();
        if ($cfgRelation['db']) {
            $this->transformation_info[$cfgRelation['db']] = [];
            $relDb = &$this->transformation_info[$cfgRelation['db']];
            if (! empty($cfgRelation['history'])) {
                $relDb[$cfgRelation['history']] = [
                    'sqlquery' => $sql_highlighting_data,
                ];
            }
            if (! empty($cfgRelation['bookmark'])) {
                $relDb[$cfgRelation['bookmark']] = [
                    'query' => $sql_highlighting_data,
                ];
            }
            if (! empty($cfgRelation['tracking'])) {
                $relDb[$cfgRelation['tracking']] = [
                    'schema_sql' => $sql_highlighting_data,
                    'data_sql' => $sql_highlighting_data,
                ];
            }
            if (! empty($cfgRelation['favorite'])) {
                $relDb[$cfgRelation['favorite']] = [
                    'tables' => $json_highlighting_data,
                ];
            }
            if (! empty($cfgRelation['recent'])) {
                $relDb[$cfgRelation['recent']] = [
                    'tables' => $json_highlighting_data,
                ];
            }
            if (! empty($cfgRelation['savedsearches'])) {
                $relDb[$cfgRelation['savedsearches']] = [
                    'search_data' => $json_highlighting_data,
                ];
            }
            if (! empty($cfgRelation['designer_settings'])) {
                $relDb[$cfgRelation['designer_settings']] = [
                    'settings_data' => $json_highlighting_data,
                ];
            }
            if (! empty($cfgRelation['table_uiprefs'])) {
                $relDb[$cfgRelation['table_uiprefs']] = [
                    'prefs' => $json_highlighting_data,
                ];
            }
            if (! empty($cfgRelation['userconfig'])) {
                $relDb[$cfgRelation['userconfig']] = [
                    'config_data' => $json_highlighting_data,
                ];
            }
            if (! empty($cfgRelation['export_templates'])) {
                $relDb[$cfgRelation['export_templates']] = [
                    'template_data' => $json_highlighting_data,
                ];
            }
        }
    }

    /**
     * Set properties which were not initialized at the constructor
     *
     * @param integer  $unlim_num_rows the total number of rows returned by
     *                                 the SQL query without any appended
     *                                 "LIMIT" clause programmatically
     * @param stdClass $fields_meta    meta information about fields
     * @param boolean  $is_count       statement is SELECT COUNT
     * @param integer  $is_export      statement contains INTO OUTFILE
     * @param boolean  $is_func        statement contains a function like SUM()
     * @param integer  $is_analyse     statement contains PROCEDURE ANALYSE
     * @param integer  $num_rows       total no. of rows returned by SQL query
     * @param integer  $fields_cnt     total no.of fields returned by SQL query
     * @param double   $querytime      time taken for execute the SQL query
     * @param string   $pmaThemeImage  path for theme images directory
     * @param string   $text_dir       text direction
     * @param boolean  $is_maint       statement contains a maintenance command
     * @param boolean  $is_explain     statement contains EXPLAIN
     * @param boolean  $is_show        statement contains SHOW
     * @param array    $showtable      table definitions
     * @param string   $printview      print view was requested
     * @param string   $url_query      URL query
     * @param boolean  $editable       whether the results set is editable
     * @param boolean  $is_browse_dist whether browsing distinct values
     *
     * @return void
     *
     * @see     sql.php
     */
    public function setProperties(
        $unlim_num_rows,
        $fields_meta,
        $is_count,
        $is_export,
        $is_func,
        $is_analyse,
        $num_rows,
        $fields_cnt,
        $querytime,
        $pmaThemeImage,
        $text_dir,
        $is_maint,
        $is_explain,
        $is_show,
        $showtable,
        $printview,
        $url_query,
        $editable,
        $is_browse_dist
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
        $this->__set('is_browse_distinct', $is_browse_dist);
    }

    /**
     * Defines the parts to display for a print view
     *
     * @param array $displayParts the parts to display
     *
     * @return array the modified display parts
     *
     * @access  private
     *
     */
    private function _setDisplayPartsForPrintView(array $displayParts)
    {
        // set all elements to false!
        $displayParts['edit_lnk']  = self::NO_EDIT_OR_DELETE; // no edit link
        $displayParts['del_lnk']   = self::NO_EDIT_OR_DELETE; // no delete link
        $displayParts['sort_lnk']  = (string) '0';
        $displayParts['nav_bar']   = (string) '0';
        $displayParts['bkm_form']  = (string) '0';
        $displayParts['text_btn']  = (string) '0';
        $displayParts['pview_lnk'] = (string) '0';

        return $displayParts;
    }

    /**
     * Defines the parts to display for a SHOW statement
     *
     * @param array $displayParts the parts to display
     *
     * @return array the modified display parts
     *
     * @access  private
     *
     */
    private function _setDisplayPartsForShow(array $displayParts)
    {
        preg_match(
            '@^SHOW[[:space:]]+(VARIABLES|(FULL[[:space:]]+)?'
            . 'PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS|DATABASES|FIELDS'
            . ')@i',
            $this->__get('sql_query'),
            $which
        );

        $bIsProcessList = isset($which[1]);
        if ($bIsProcessList) {
            $str = ' ' . strtoupper($which[1]);
            $bIsProcessList = $bIsProcessList
                && strpos($str, 'PROCESSLIST') > 0;
        }

        if ($bIsProcessList) {
            // no edit link
            $displayParts['edit_lnk'] = self::NO_EDIT_OR_DELETE;
            // "kill process" type edit link
            $displayParts['del_lnk']  = self::KILL_PROCESS;
        } else {
            // Default case -> no links
            // no edit link
            $displayParts['edit_lnk'] = self::NO_EDIT_OR_DELETE;
            // no delete link
            $displayParts['del_lnk']  = self::NO_EDIT_OR_DELETE;
        }
        // Other settings
        $displayParts['sort_lnk']  = (string) '0';
        $displayParts['nav_bar']   = (string) '0';
        $displayParts['bkm_form']  = (string) '1';
        $displayParts['text_btn']  = (string) '1';
        $displayParts['pview_lnk'] = (string) '1';

        return $displayParts;
    }

    /**
     * Defines the parts to display for statements not related to data
     *
     * @param array $displayParts the parts to display
     *
     * @return array the modified display parts
     *
     * @access  private
     *
     */
    private function _setDisplayPartsForNonData(array $displayParts)
    {
        // Statement is a "SELECT COUNT", a
        // "CHECK/ANALYZE/REPAIR/OPTIMIZE/CHECKSUM", an "EXPLAIN" one or
        // contains a "PROC ANALYSE" part
        $displayParts['edit_lnk']  = self::NO_EDIT_OR_DELETE; // no edit link
        $displayParts['del_lnk']   = self::NO_EDIT_OR_DELETE; // no delete link
        $displayParts['sort_lnk']  = (string) '0';
        $displayParts['nav_bar']   = (string) '0';
        $displayParts['bkm_form']  = (string) '1';

        if ($this->__get('is_maint')) {
            $displayParts['text_btn']  = (string) '1';
        } else {
            $displayParts['text_btn']  = (string) '0';
        }
        $displayParts['pview_lnk'] = (string) '1';

        return $displayParts;
    }

    /**
     * Defines the parts to display for other statements (probably SELECT)
     *
     * @param array $displayParts the parts to display
     *
     * @return array the modified display parts
     *
     * @access  private
     *
     */
    private function _setDisplayPartsForSelect(array $displayParts)
    {
        // Other statements (ie "SELECT" ones) -> updates
        // $displayParts['edit_lnk'], $displayParts['del_lnk'] and
        // $displayParts['text_btn'] (keeps other default values)

        $fields_meta = $this->__get('fields_meta');
        $prev_table = '';
        $displayParts['text_btn']  = (string) '1';
        $number_of_columns = $this->__get('fields_cnt');

        for ($i = 0; $i < $number_of_columns; $i++) {
            $is_link = ($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                || ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE)
                || ($displayParts['sort_lnk'] != '0');

            // Displays edit/delete/sort/insert links?
            if ($is_link
                && $prev_table != ''
                && $fields_meta[$i]->table != ''
                && $fields_meta[$i]->table != $prev_table
            ) {
                // don't display links
                $displayParts['edit_lnk'] = self::NO_EDIT_OR_DELETE;
                $displayParts['del_lnk']  = self::NO_EDIT_OR_DELETE;
                /**
                 * @todo May be problematic with same field names
                 * in two joined table.
                 */
                // $displayParts['sort_lnk'] = (string) '0';
                if ($displayParts['text_btn'] == '1') {
                    break;
                }
            } // end if

            // Always display print view link
            $displayParts['pview_lnk'] = (string) '1';
            if ($fields_meta[$i]->table != '') {
                $prev_table = $fields_meta[$i]->table;
            }
        } // end for

        if ($prev_table == '') { // no table for any of the columns
            // don't display links
            $displayParts['edit_lnk'] = self::NO_EDIT_OR_DELETE;
            $displayParts['del_lnk']  = self::NO_EDIT_OR_DELETE;
        }

        return $displayParts;
    }

    /**
     * Defines the parts to display for the results of a SQL query
     * and the total number of rows
     *
     * @param array $displayParts the parts to display (see a few
     *                            lines above for explanations)
     *
     * @return array the first element is an array with explicit indexes
     *               for all the display elements
     *               the second element is the total number of rows returned
     *               by the SQL query without any programmatically appended
     *               LIMIT clause (just a copy of $unlim_num_rows if it exists,
     *               else computed inside this function)
     *
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _setDisplayPartsAndTotal(array $displayParts)
    {
        $the_total = 0;

        // 1. Following variables are needed for use in isset/empty or
        //    use with array indexes or safe use in foreach
        $db = $this->__get('db');
        $table = $this->__get('table');
        $unlim_num_rows = $this->__get('unlim_num_rows');
        $num_rows = $this->__get('num_rows');
        $printview = $this->__get('printview');

        // 2. Updates the display parts
        if ($printview == '1') {
            $displayParts = $this->_setDisplayPartsForPrintView($displayParts);
        } elseif ($this->__get('is_count') || $this->__get('is_analyse')
            || $this->__get('is_maint') || $this->__get('is_explain')
        ) {
            $displayParts = $this->_setDisplayPartsForNonData($displayParts);
        } elseif ($this->__get('is_show')) {
            $displayParts = $this->_setDisplayPartsForShow($displayParts);
        } else {
            $displayParts = $this->_setDisplayPartsForSelect($displayParts);
        } // end if..elseif...else

        // 3. Gets the total number of rows if it is unknown
        if (isset($unlim_num_rows) && $unlim_num_rows != '') {
            $the_total = $unlim_num_rows;
        } elseif (($displayParts['nav_bar'] == '1')
            || ($displayParts['sort_lnk'] == '1')
            && (strlen($db) > 0 && strlen($table) > 0)
        ) {
            $the_total = $GLOBALS['dbi']->getTable($db, $table)->countRecords();
        }

        // if for COUNT query, number of rows returned more than 1
        // (may be being used GROUP BY)
        if ($this->__get('is_count') && isset($num_rows) && $num_rows > 1) {
            $displayParts['nav_bar']   = (string) '1';
            $displayParts['sort_lnk']  = (string) '1';
        }
        // 4. If navigation bar or sorting fields names URLs should be
        //    displayed but there is only one row, change these settings to
        //    false
        if ($displayParts['nav_bar'] == '1' || $displayParts['sort_lnk'] == '1') {
            // - Do not display sort links if less than 2 rows.
            // - For a VIEW we (probably) did not count the number of rows
            //   so don't test this number here, it would remove the possibility
            //   of sorting VIEW results.
            $_table = new Table($table, $db);
            if (isset($unlim_num_rows)
                && ($unlim_num_rows < 2)
                && ! $_table->isView()
            ) {
                $displayParts['sort_lnk'] = (string) '0';
            }
        } // end if (3)

        return [
            $displayParts,
            $the_total,
        ];
    }

    /**
     * Return true if we are executing a query in the form of
     * "SELECT * FROM <a table> ..."
     *
     * @param array $analyzed_sql_results analyzed sql results
     *
     * @return boolean
     *
     * @access  private
     *
     * @see     _getTableHeaders(), _getColumnParams()
     */
    private function _isSelect(array $analyzed_sql_results)
    {
        return ! ($this->__get('is_count')
                || $this->__get('is_export')
                || $this->__get('is_func')
                || $this->__get('is_analyse'))
            && ! empty($analyzed_sql_results['select_from'])
            && ! empty($analyzed_sql_results['statement']->from)
            && (count($analyzed_sql_results['statement']->from) === 1)
            && ! empty($analyzed_sql_results['statement']->from[0]->table);
    }

    /**
     * Get a navigation button
     *
     * @param string  $caption            iconic caption for button
     * @param string  $title              text for button
     * @param integer $pos                position for next query
     * @param string  $html_sql_query     query ready for display
     * @param boolean $back               whether 'begin' or 'previous'
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
        $caption,
        $title,
        $pos,
        $html_sql_query,
        $back,
        $onsubmit = '',
        $input_for_real_end = '',
        $onclick = ''
    ) {
        $caption_output = '';
        if ($back) {
            if (Util::showIcons('TableNavigationLinksMode')) {
                $caption_output .= $caption;
            }
            if (Util::showText('TableNavigationLinksMode')) {
                $caption_output .= '&nbsp;' . $title;
            }
        } else {
            if (Util::showText('TableNavigationLinksMode')) {
                $caption_output .= $title;
            }
            if (Util::showIcons('TableNavigationLinksMode')) {
                $caption_output .= '&nbsp;' . $caption;
            }
        }

        return $this->template->render('display/results/table_navigation_button', [
            'db' => $this->__get('db'),
            'table' => $this->__get('table'),
            'sql_query' => $html_sql_query,
            'pos' => $pos,
            'is_browse_distinct' => $this->__get('is_browse_distinct'),
            'goto' => $this->__get('goto'),
            'input_for_real_end' => $input_for_real_end,
            'caption_output' => $caption_output,
            'title' => $title,
            'onsubmit' => $onsubmit,
            'onclick' => $onclick,
        ]);
    }

    /**
     * Possibly return a page selector for table navigation
     *
     * @return array ($output, $nbTotalPage)
     *
     * @access private
     */
    private function _getHtmlPageSelector(): array
    {
        $pageNow = @floor(
            $_SESSION['tmpval']['pos']
            / $_SESSION['tmpval']['max_rows']
        ) + 1;

        $nbTotalPage = @ceil(
            $this->__get('unlim_num_rows')
            / $_SESSION['tmpval']['max_rows']
        );

        $output = '';
        if ($nbTotalPage > 1) {
            $_url_params = [
                'db'                 => $this->__get('db'),
                'table'              => $this->__get('table'),
                'sql_query'          => $this->__get('sql_query'),
                'goto'               => $this->__get('goto'),
                'is_browse_distinct' => $this->__get('is_browse_distinct'),
            ];

            $output = $this->template->render('display/results/page_selector', [
                'url_params' => $_url_params,
                'page_selector' => Util::pageselector(
                    'pos',
                    $_SESSION['tmpval']['max_rows'],
                    $pageNow,
                    $nbTotalPage,
                    200,
                    5,
                    5,
                    20,
                    10
                ),
            ]);
        }
        return [
            $output,
            $nbTotalPage,
        ];
    }

    /**
     * Get a navigation bar to browse among the results of a SQL query
     *
     * @param integer $posNext       the offset for the "next" page
     * @param integer $posPrevious   the offset for the "previous" page
     * @param boolean $isInnodb      whether its InnoDB or not
     * @param string  $sortByKeyHtml the sort by key dialog
     *
     * @return string html content
     *
     * @access private
     *
     * @see getTable()
     */
    private function _getTableNavigation(
        $posNext,
        $posPrevious,
        $isInnodb,
        $sortByKeyHtml
    ): string {
        $isShowingAll = $_SESSION['tmpval']['max_rows'] === self::ALL_ROWS;

        // Move to the beginning or to the previous page
        $moveBackwardButtons = '';
        if ($_SESSION['tmpval']['pos'] && ! $isShowingAll) {
            $moveBackwardButtons = $this->_getMoveBackwardButtonsForTableNavigation(
                htmlspecialchars($this->__get('sql_query')),
                $posPrevious
            );
        }

        $pageSelector = '';
        $numberTotalPage = 1;
        if (! $isShowingAll) {
            list(
                $pageSelector,
                $numberTotalPage
            ) = $this->_getHtmlPageSelector();
        }

        // Move to the next page or to the last one
        $moveForwardButtons = '';
        if ($this->__get('unlim_num_rows') === false // view with unknown number of rows
            || (! $isShowingAll
            && $_SESSION['tmpval']['pos'] + $_SESSION['tmpval']['max_rows'] < $this->__get('unlim_num_rows')
            && $this->__get('num_rows') >= $_SESSION['tmpval']['max_rows'])
        ) {
            $moveForwardButtons = $this->_getMoveForwardButtonsForTableNavigation(
                htmlspecialchars($this->__get('sql_query')),
                $posNext,
                $isInnodb
            );
        }

        $hiddenFields = [
            'db' => $this->__get('db'),
            'table' => $this->__get('table'),
            'server' => $this->__get('server'),
            'sql_query' => $this->__get('sql_query'),
            'is_browse_distinct' => $this->__get('is_browse_distinct'),
            'goto' => $this->__get('goto'),
        ];

        return $this->template->render('display/results/table_navigation', [
            'move_backward_buttons' => $moveBackwardButtons,
            'page_selector' => $pageSelector,
            'move_forward_buttons' => $moveForwardButtons,
            'number_total_page' => $numberTotalPage,
            'has_show_all' => $GLOBALS['cfg']['ShowAll'] || ($this->__get('unlim_num_rows') <= 500),
            'hidden_fields' => $hiddenFields,
            'session_max_rows' => $isShowingAll ? $GLOBALS['cfg']['MaxRows'] : 'all',
            'unique_id' => $this->__get('unique_id'),
            'is_showing_all' => $isShowingAll,
            'unlim_num_rows' => $this->__get('unlim_num_rows'),
            'max_rows' => $_SESSION['tmpval']['max_rows'],
            'pos' => $_SESSION['tmpval']['pos'],
            'sort_by_key' => $sortByKeyHtml,
        ]);
    }

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
        $html_sql_query,
        $pos_prev
    ) {
        return $this->_getTableNavigationButton(
            '&lt;&lt;',
            _pgettext('First page', 'Begin'),
            0,
            $html_sql_query,
            true
        )
        . $this->_getTableNavigationButton(
            '&lt;',
            _pgettext('Previous page', 'Previous'),
            $pos_prev,
            $html_sql_query,
            true
        );
    }

    /**
     * Prepare move forward buttons - next and last
     *
     * @param string  $html_sql_query the sql encoded by htmlspecialchars()
     * @param integer $pos_next       the offset for the "next" page
     * @param boolean $is_innodb      whether it's InnoDB or not
     *
     * @return  string   html content
     *
     * @access  private
     *
     * @see     _getTableNavigation()
     */
    private function _getMoveForwardButtonsForTableNavigation(
        $html_sql_query,
        $pos_next,
        $is_innodb
    ) {
        // display the Next button
        $buttons_html = $this->_getTableNavigationButton(
            '&gt;',
            _pgettext('Next page', 'Next'),
            $pos_next,
            $html_sql_query,
            false
        );

        // prepare some options for the End button
        if ($is_innodb
            && $this->__get('unlim_num_rows') > $GLOBALS['cfg']['MaxExactCount']
        ) {
            $input_for_real_end = '<input id="real_end_input" type="hidden" '
                . 'name="find_real_end" value="1">';
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
                && $this->__get('num_rows') >= $maxRows
            ? 'true'
            : 'false') . '"';

        // display the End button
        $buttons_html .= $this->_getTableNavigationButton(
            '&gt;&gt;',
            _pgettext('Last page', 'End'),
            @((ceil(
                $this->__get('unlim_num_rows')
                / $_SESSION['tmpval']['max_rows']
            ) - 1) * $maxRows),
            $html_sql_query,
            false,
            $onsubmit,
            $input_for_real_end,
            $onclick
        );

        return $buttons_html;
    }

    /**
     * Get the headers of the results table, for all of the columns
     *
     * @param array   $displayParts                which elements to display
     * @param array   $analyzed_sql_results        analyzed sql results
     * @param array   $sort_expression             sort expression
     * @param array   $sort_expression_nodirection sort expression
     *                                             without direction
     * @param array   $sort_direction              sort direction
     * @param boolean $is_limited_display          with limited operations
     *                                             or not
     * @param string  $unsorted_sql_query          query without the sort part
     *
     * @return string html content
     *
     * @access private
     *
     * @see    getTableHeaders()
     */
    private function _getTableHeadersForColumns(
        array $displayParts,
        array $analyzed_sql_results,
        array $sort_expression,
        array $sort_expression_nodirection,
        array $sort_direction,
        $is_limited_display,
        $unsorted_sql_query
    ) {
        $html = '';

        // required to generate sort links that will remember whether the
        // "Show all" button has been clicked
        $sql_md5 = md5($this->__get('sql_query'));
        $session_max_rows = $is_limited_display
            ? 0
            : $_SESSION['tmpval']['query'][$sql_md5]['max_rows'];

        // Following variable are needed for use in isset/empty or
        // use with array indexes/safe use in the for loop
        $highlight_columns = $this->__get('highlight_columns');
        $fields_meta = $this->__get('fields_meta');

        // Prepare Display column comments if enabled
        // ($GLOBALS['cfg']['ShowBrowseComments']).
        $comments_map = $this->_getTableCommentsArray($analyzed_sql_results);

        list($col_order, $col_visib) = $this->_getColumnParams(
            $analyzed_sql_results
        );

        // optimize: avoid calling a method on each iteration
        $number_of_columns = $this->__get('fields_cnt');

        for ($j = 0; $j < $number_of_columns; $j++) {
            // PHP 7.4 fix for accessing array offset on bool
            $col_visib_current = is_array($col_visib) && isset($col_visib[$j]) ? $col_visib[$j] : null;

            // assign $i with the appropriate column order
            $i = $col_order ? $col_order[$j] : $j;

            //  See if this column should get highlight because it's used in the
            //  where-query.
            $name = $fields_meta[$i]->name;
            $condition_field = isset($highlight_columns[$name])
                || isset($highlight_columns[Util::backquote($name)])
                ? true
                : false;

            // Prepare comment-HTML-wrappers for each row, if defined/enabled.
            $comments = $this->_getCommentForRow($comments_map, $fields_meta[$i]);
            $display_params = $this->__get('display_params');

            if (($displayParts['sort_lnk'] == '1') && ! $is_limited_display) {
                list($order_link, $sorted_header_html)
                    = $this->_getOrderLinkAndSortedHeaderHtml(
                        $fields_meta[$i],
                        $sort_expression,
                        $sort_expression_nodirection,
                        $i,
                        $unsorted_sql_query,
                        $session_max_rows,
                        $comments,
                        $sort_direction,
                        $col_visib,
                        $col_visib_current
                    );

                $html .= $sorted_header_html;

                $display_params['desc'][] = '    <th '
                    . 'class="draggable'
                    . ($condition_field ? ' condition' : '')
                    . '" data-column="' . htmlspecialchars($fields_meta[$i]->name)
                    . '">' . "\n" . $order_link . $comments . '    </th>' . "\n";
            } else {
                // Results can't be sorted
                $html
                    .= $this->_getDraggableClassForNonSortableColumns(
                        $col_visib,
                        $col_visib_current,
                        $condition_field,
                        $fields_meta[$i],
                        $comments
                    );

                $display_params['desc'][] = '    <th '
                    . 'class="draggable'
                    . ($condition_field ? ' condition"' : '')
                    . '" data-column="' . htmlspecialchars((string) $fields_meta[$i]->name)
                    . '">        '
                    . htmlspecialchars((string) $fields_meta[$i]->name)
                    . $comments . '    </th>';
            } // end else

            $this->__set('display_params', $display_params);
        } // end for
        return $html;
    }

    /**
     * Get the headers of the results table
     *
     * @param array        $displayParts              which elements to display
     * @param array        $analyzedSqlResults        analyzed sql results
     * @param string       $unsortedSqlQuery          the unsorted sql query
     * @param array        $sortExpression            sort expression
     * @param array|string $sortExpressionNoDirection sort expression without direction
     * @param array        $sortDirection             sort direction
     * @param boolean      $isLimitedDisplay          with limited operations or not
     *
     * @return string html content
     *
     * @access private
     *
     * @see getTable()
     */
    private function _getTableHeaders(
        array &$displayParts,
        array $analyzedSqlResults,
        $unsortedSqlQuery,
        array $sortExpression = [],
        $sortExpressionNoDirection = '',
        array $sortDirection = [],
        $isLimitedDisplay = false
    ): string {
        // Needed for use in isset/empty or
        // use with array indexes/safe use in foreach
        $printView = $this->__get('printview');
        $displayParams = $this->__get('display_params');

        // Output data needed for column reordering and show/hide column
        $dataForResettingColumnOrder = $this->_getDataForResettingColumnOrder($analyzedSqlResults);

        $displayParams['emptypre'] = 0;
        $displayParams['emptyafter'] = 0;
        $displayParams['textbtn'] = '';
        $fullOrPartialTextLink = '';

        $this->__set('display_params', $displayParams);

        // Display options (if we are not in print view)
        $optionsBlock = '';
        if (! (isset($printView) && ($printView == '1')) && ! $isLimitedDisplay) {
            $optionsBlock = $this->_getOptionsBlock();

            // prepare full/partial text button or link
            $fullOrPartialTextLink = $this->_getFullOrPartialTextButtonOrLink();
        }

        // 1. Set $colspan and generate html with full/partial
        // text button or link
        list($colspan, $buttonHtml) = $this->_getFieldVisibilityParams(
            $displayParts,
            $fullOrPartialTextLink
        );

        // 2. Displays the fields' name
        // 2.0 If sorting links should be used, checks if the query is a "JOIN"
        //     statement (see 2.1.3)

        // See if we have to highlight any header fields of a WHERE query.
        // Uses SQL-Parser results.
        $this->_setHighlightedColumnGlobalField($analyzedSqlResults);

        // Get the headers for all of the columns
        $tableHeadersForColumns = $this->_getTableHeadersForColumns(
            $displayParts,
            $analyzedSqlResults,
            $sortExpression,
            $sortExpressionNoDirection,
            $sortDirection,
            $isLimitedDisplay,
            $unsortedSqlQuery
        );

        // Display column at rightside - checkboxes or empty column
        $columnAtRightSide = '';
        if (! $printView) {
            $columnAtRightSide = $this->_getColumnAtRightSide(
                $displayParts,
                $fullOrPartialTextLink,
                $colspan
            );
        }

        return $this->template->render('display/results/table_headers', [
            'db' => $this->__get('db'),
            'table' => $this->__get('table'),
            'unique_id' => $this->__get('unique_id'),
            'save_cells_at_once' => $GLOBALS['cfg']['SaveCellsAtOnce'],
            'data_for_resetting_column_order' => $dataForResettingColumnOrder,
            'options_block' => $optionsBlock,
            'delete_link' => $displayParts['del_lnk'],
            'delete_row' => self::DELETE_ROW,
            'kill_process' => self::KILL_PROCESS,
            'button' => $buttonHtml,
            'table_headers_for_columns' => $tableHeadersForColumns,
            'column_at_right_side' => $columnAtRightSide,
        ]);
    }

    /**
     * Prepare unsorted sql query and sort by key drop down
     *
     * @param array      $analyzed_sql_results analyzed sql results
     * @param array|null $sort_expression      sort expression
     *
     * @return  array   two element array - $unsorted_sql_query, $drop_down_html
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getUnsortedSqlAndSortByKeyDropDown(
        array $analyzed_sql_results,
        ?array $sort_expression
    ) {
        $drop_down_html = '';

        $unsorted_sql_query = Query::replaceClause(
            $analyzed_sql_results['statement'],
            $analyzed_sql_results['parser']->list,
            'ORDER BY',
            ''
        );

        // Data is sorted by indexes only if it there is only one table.
        if ($this->_isSelect($analyzed_sql_results)) {
            // grab indexes data:
            $indexes = Index::getFromTable(
                $this->__get('table'),
                $this->__get('db')
            );

            // do we have any index?
            if (! empty($indexes)) {
                $drop_down_html = $this->_getSortByKeyDropDown(
                    $indexes,
                    $sort_expression,
                    $unsorted_sql_query
                );
            }
        }

        return [
            $unsorted_sql_query,
            $drop_down_html,
        ];
    }

    /**
     * Prepare sort by key dropdown - html code segment
     *
     * @param Index[]    $indexes          the indexes of the table for sort criteria
     * @param array|null $sortExpression   the sort expression
     * @param string     $unsortedSqlQuery the unsorted sql query
     *
     * @return string html content
     *
     * @access private
     *
     * @see _getTableHeaders()
     */
    private function _getSortByKeyDropDown(
        $indexes,
        ?array $sortExpression,
        $unsortedSqlQuery
    ): string {
        $hiddenFields = [
            'db' => $this->__get('db'),
            'table' => $this->__get('table'),
            'server' => $this->__get('server'),
            'sort_by_key' => '1',
        ];

        $isIndexUsed = false;
        $localOrder = is_array($sortExpression) ? implode(', ', $sortExpression) : '';

        $options = [];
        foreach ($indexes as $index) {
            $ascSort = '`'
                . implode('` ASC, `', array_keys($index->getColumns()))
                . '` ASC';

            $descSort = '`'
                . implode('` DESC, `', array_keys($index->getColumns()))
                . '` DESC';

            $isIndexUsed = $isIndexUsed
                || $localOrder === $ascSort
                || $localOrder === $descSort;

            $unsortedSqlQueryFirstPart = $unsortedSqlQuery;
            $unsortedSqlQuerySecondPart = '';
            if (preg_match(
                '@(.*)([[:space:]](LIMIT (.*)|PROCEDURE (.*)|'
                . 'FOR UPDATE|LOCK IN SHARE MODE))@is',
                $unsortedSqlQuery,
                $myReg
            )) {
                $unsortedSqlQueryFirstPart = $myReg[1];
                $unsortedSqlQuerySecondPart = $myReg[2];
            }

            $options[] = [
                'value' => $unsortedSqlQueryFirstPart . ' ORDER BY '
                    . $ascSort . $unsortedSqlQuerySecondPart,
                'content' => $index->getName() . ' (ASC)',
                'is_selected' => $localOrder === $ascSort,
            ];
            $options[] = [
                'value' => $unsortedSqlQueryFirstPart . ' ORDER BY '
                    . $descSort . $unsortedSqlQuerySecondPart,
                'content' => $index->getName() . ' (DESC)',
                'is_selected' => $localOrder === $descSort,
            ];
        }
        $options[] = [
            'value' => $unsortedSqlQuery,
            'content' => __('None'),
            'is_selected' => ! $isIndexUsed,
        ];

        return $this->template->render('display/results/sort_by_key', [
            'hidden_fields' => $hiddenFields,
            'options' => $options,
        ]);
    }

    /**
     * Set column span, row span and prepare html with full/partial
     * text button or link
     *
     * @param array  $displayParts              which elements to display
     * @param string $full_or_partial_text_link full/partial link or text button
     *
     * @return  array   2 element array - $colspan, $button_html
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getFieldVisibilityParams(
        array &$displayParts,
        $full_or_partial_text_link
    ) {

        $button_html = '';
        $display_params = $this->__get('display_params');

        // 1. Displays the full/partial text button (part 1)...
        $button_html .= '<thead><tr>' . "\n";

        $emptyPreCondition = $displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE
                           && $displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE;

        $colspan = $emptyPreCondition ? ' colspan="4"'
            : '';

        $leftOrBoth = $GLOBALS['cfg']['RowActionLinks'] === self::POSITION_LEFT
                   || $GLOBALS['cfg']['RowActionLinks'] === self::POSITION_BOTH;

        //     ... before the result table
        if (($displayParts['edit_lnk'] == self::NO_EDIT_OR_DELETE)
            && ($displayParts['del_lnk'] == self::NO_EDIT_OR_DELETE)
            && ($displayParts['text_btn'] == '1')
        ) {
            $display_params['emptypre'] = $emptyPreCondition ? 4 : 0;
        } elseif ($leftOrBoth && ($displayParts['text_btn'] == '1')
        ) {
            //     ... at the left column of the result table header if possible
            //     and required

            $display_params['emptypre'] = $emptyPreCondition ? 4 : 0;

            $button_html .= '<th class="column_action sticky print_ignore" ' . $colspan
                . '>' . $full_or_partial_text_link . '</th>';
        } elseif ($leftOrBoth
            && (($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
            || ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE))
        ) {
            //     ... elseif no button, displays empty(ies) col(s) if required

            $display_params['emptypre'] = $emptyPreCondition ? 4 : 0;

            $button_html .= '<td ' . $colspan . '></td>';
        } elseif ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_NONE) {
            // ... elseif display an empty column if the actions links are
            //  disabled to match the rest of the table
            $button_html .= '<th class="column_action sticky"></th>';
        }

        $this->__set('display_params', $display_params);

        return [
            $colspan,
            $button_html,
        ];
    }

    /**
     * Get table comments as array
     *
     * @param array $analyzed_sql_results analyzed sql results
     *
     * @return array table comments
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getTableCommentsArray(array $analyzed_sql_results)
    {
        if (! $GLOBALS['cfg']['ShowBrowseComments']
            || empty($analyzed_sql_results['statement']->from)
        ) {
            return [];
        }

        $ret = [];
        foreach ($analyzed_sql_results['statement']->from as $field) {
            if (empty($field->table)) {
                continue;
            }
            $ret[$field->table] = $this->relation->getComments(
                empty($field->database) ? $this->__get('db') : $field->database,
                $field->table
            );
        }

        return $ret;
    }

    /**
     * Set global array for store highlighted header fields
     *
     * @param array $analyzed_sql_results analyzed sql results
     *
     * @return  void
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _setHighlightedColumnGlobalField(array $analyzed_sql_results)
    {
        $highlight_columns = [];

        if (! empty($analyzed_sql_results['statement']->where)) {
            foreach ($analyzed_sql_results['statement']->where as $expr) {
                foreach ($expr->identifiers as $identifier) {
                    $highlight_columns[$identifier] = 'true';
                }
            }
        }

        $this->__set('highlight_columns', $highlight_columns);
    }

    /**
     * Prepare data for column restoring and show/hide
     *
     * @param array $analyzedSqlResults analyzed sql results
     *
     * @return string html content
     *
     * @access private
     *
     * @see _getTableHeaders()
     */
    private function _getDataForResettingColumnOrder(array $analyzedSqlResults): string
    {
        if (! $this->_isSelect($analyzedSqlResults)) {
            return '';
        }

        list($columnOrder, $columnVisibility) = $this->_getColumnParams(
            $analyzedSqlResults
        );

        $tableCreateTime = '';
        $table = new Table($this->__get('table'), $this->__get('db'));
        if (! $table->isView()) {
            $tableCreateTime = $GLOBALS['dbi']->getTable(
                $this->__get('db'),
                $this->__get('table')
            )->getStatusInfo('Create_time');
        }

        return $this->template->render('display/results/data_for_resetting_column_order', [
            'column_order' => $columnOrder,
            'column_visibility' => $columnVisibility,
            'is_view' => $table->isView(),
            'table_create_time' => $tableCreateTime,
        ]);
    }

    /**
     * Prepare option fields block
     *
     * @return string html content
     *
     * @access private
     *
     * @see _getTableHeaders()
     */
    private function _getOptionsBlock()
    {
        if (isset($_SESSION['tmpval']['possible_as_geometry']) && $_SESSION['tmpval']['possible_as_geometry'] == false) {
            if ($_SESSION['tmpval']['geoOption'] == self::GEOMETRY_DISP_GEOM) {
                $_SESSION['tmpval']['geoOption'] = self::GEOMETRY_DISP_WKT;
            }
        }
        return $this->template->render('display/results/options_block', [
            'unique_id' => $this->__get('unique_id'),
            'geo_option' => $_SESSION['tmpval']['geoOption'],
            'hide_transformation' => $_SESSION['tmpval']['hide_transformation'],
            'display_blob' => $_SESSION['tmpval']['display_blob'],
            'display_binary' => $_SESSION['tmpval']['display_binary'],
            'relational_display' => $_SESSION['tmpval']['relational_display'],
            'displaywork' => $GLOBALS['cfgRelation']['displaywork'],
            'relwork' => $GLOBALS['cfgRelation']['relwork'],
            'possible_as_geometry' => $_SESSION['tmpval']['possible_as_geometry'],
            'pftext' => $_SESSION['tmpval']['pftext'],
            'db' => $this->__get('db'),
            'table' => $this->__get('table'),
            'sql_query' => $this->__get('sql_query'),
            'goto' => $this->__get('goto'),
            'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
        ]);
    }

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

        $url_params_full_text = [
            'db' => $this->__get('db'),
            'table' => $this->__get('table'),
            'sql_query' => $this->__get('sql_query'),
            'goto' => $this->__get('goto'),
            'full_text_button' => 1,
        ];

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
                     . $tmp_txt . '" title="' . $tmp_txt . '">';
        $tmp_url = 'sql.php' . Url::getCommon($url_params_full_text);

        return Util::linkOrButton($tmp_url, $tmp_image);
    }

    /**
     * Get comment for row
     *
     * @param array $commentsMap comments array
     * @param array $fieldsMeta  set of field properties
     *
     * @return string html content
     *
     * @access private
     *
     * @see _getTableHeaders()
     */
    private function _getCommentForRow(array $commentsMap, $fieldsMeta)
    {
        return $this->template->render('display/results/comment_for_row', [
            'comments_map' => $commentsMap,
            'fields_meta' => $fieldsMeta,
            'limit_chars' => $GLOBALS['cfg']['LimitChars'],
        ]);
    }

    /**
     * Prepare parameters and html for sorted table header fields
     *
     * @param stdClass $fields_meta                 set of field properties
     * @param array    $sort_expression             sort expression
     * @param array    $sort_expression_nodirection sort expression without direction
     * @param integer  $column_index                the index of the column
     * @param string   $unsorted_sql_query          the unsorted sql query
     * @param integer  $session_max_rows            maximum rows resulted by sql
     * @param string   $comments                    comment for row
     * @param array    $sort_direction              sort direction
     * @param boolean  $col_visib                   column is visible(false)
     *                                              array                                column isn't visible(string array)
     * @param string   $col_visib_j                 element of $col_visib array
     *
     * @return  array   2 element array - $order_link, $sorted_header_html
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getOrderLinkAndSortedHeaderHtml(
        $fields_meta,
        array $sort_expression,
        array $sort_expression_nodirection,
        $column_index,
        $unsorted_sql_query,
        $session_max_rows,
        $comments,
        array $sort_direction,
        $col_visib,
        $col_visib_j
    ) {

        $sorted_header_html = '';

        // Checks if the table name is required; it's the case
        // for a query with a "JOIN" statement and if the column
        // isn't aliased, or in queries like
        // SELECT `1`.`master_field` , `2`.`master_field`
        // FROM `PMA_relation` AS `1` , `PMA_relation` AS `2`

        $sort_tbl = isset($fields_meta->table)
            && strlen($fields_meta->table) > 0
            && $fields_meta->orgname == $fields_meta->name
            ? Util::backquote(
                $fields_meta->table
            ) . '.'
            : '';

        $name_to_use_in_sort = $fields_meta->name;

        // Generates the orderby clause part of the query which is part
        // of URL
        list($single_sort_order, $multi_sort_order, $order_img)
            = $this->_getSingleAndMultiSortUrls(
                $sort_expression,
                $sort_expression_nodirection,
                $sort_tbl,
                $name_to_use_in_sort,
                $sort_direction,
                $fields_meta
            );

        if (preg_match(
            '@(.*)([[:space:]](LIMIT (.*)|PROCEDURE (.*)|FOR UPDATE|'
            . 'LOCK IN SHARE MODE))@is',
            $unsorted_sql_query,
            $regs3
        )) {
            $single_sorted_sql_query = $regs3[1] . $single_sort_order . $regs3[2];
            $multi_sorted_sql_query = $regs3[1] . $multi_sort_order . $regs3[2];
        } else {
            $single_sorted_sql_query = $unsorted_sql_query . $single_sort_order;
            $multi_sorted_sql_query = $unsorted_sql_query . $multi_sort_order;
        }

        $_single_url_params = [
            'db'                 => $this->__get('db'),
            'table'              => $this->__get('table'),
            'sql_query'          => $single_sorted_sql_query,
            'sql_signature'      => Core::signSqlQuery($single_sorted_sql_query),
            'session_max_rows'   => $session_max_rows,
            'is_browse_distinct' => $this->__get('is_browse_distinct'),
        ];

        $_multi_url_params = [
            'db'                 => $this->__get('db'),
            'table'              => $this->__get('table'),
            'sql_query'          => $multi_sorted_sql_query,
            'sql_signature'      => Core::signSqlQuery($multi_sorted_sql_query),
            'session_max_rows'   => $session_max_rows,
            'is_browse_distinct' => $this->__get('is_browse_distinct'),
        ];
        $single_order_url  = 'sql.php' . Url::getCommon($_single_url_params);
        $multi_order_url = 'sql.php' . Url::getCommon($_multi_url_params);

        // Displays the sorting URL
        // enable sort order swapping for image
        $order_link = $this->_getSortOrderLink(
            $order_img,
            $fields_meta,
            $single_order_url,
            $multi_order_url
        );

        $sorted_header_html .= $this->_getDraggableClassForSortableColumns(
            $col_visib,
            $col_visib_j,
            $fields_meta,
            $order_link,
            $comments
        );

        return [
            $order_link,
            $sorted_header_html,
        ];
    }

    /**
     * Prepare parameters and html for sorted table header fields
     *
     * @param array    $sort_expression             sort expression
     * @param array    $sort_expression_nodirection sort expression without direction
     * @param string   $sort_tbl                    The name of the table to which
     *                                              the current column belongs to
     * @param string   $name_to_use_in_sort         The current column under
     *                                              consideration
     * @param array    $sort_direction              sort direction
     * @param stdClass $fields_meta                 set of field properties
     *
     * @return  array   3 element array - $single_sort_order, $sort_order, $order_img
     *
     * @access  private
     *
     * @see     _getOrderLinkAndSortedHeaderHtml()
     */
    private function _getSingleAndMultiSortUrls(
        array $sort_expression,
        array $sort_expression_nodirection,
        $sort_tbl,
        $name_to_use_in_sort,
        array $sort_direction,
        $fields_meta
    ) {
        $sort_order = "";
        // Check if the current column is in the order by clause
        $is_in_sort = $this->_isInSorted(
            $sort_expression,
            $sort_expression_nodirection,
            $sort_tbl,
            $name_to_use_in_sort
        );
        $current_name = $name_to_use_in_sort;
        if ($sort_expression_nodirection[0] == '' || ! $is_in_sort) {
            $special_index = $sort_expression_nodirection[0] == ''
                ? 0
                : count($sort_expression_nodirection);
            $sort_expression_nodirection[$special_index]
                = Util::backquote(
                    $current_name
                );
            $sort_direction[$special_index] = preg_match(
                '@time|date@i',
                $fields_meta->type ?? ''
            ) ? self::DESCENDING_SORT_DIR : self::ASCENDING_SORT_DIR;
        }

        $sort_expression_nodirection = array_filter($sort_expression_nodirection);
        $single_sort_order = null;
        foreach ($sort_expression_nodirection as $index => $expression) {
            // check if this is the first clause,
            // if it is then we have to add "order by"
            $is_first_clause = ($index == 0);
            $name_to_use_in_sort = $expression;
            $sort_tbl_new = $sort_tbl;
            // Test to detect if the column name is a standard name
            // Standard name has the table name prefixed to the column name
            if (mb_strpos($name_to_use_in_sort, '.') !== false) {
                $matches = explode('.', $name_to_use_in_sort);
                // Matches[0] has the table name
                // Matches[1] has the column name
                $name_to_use_in_sort = $matches[1];
                $sort_tbl_new = $matches[0];
            }

            // $name_to_use_in_sort might contain a space due to
            // formatting of function expressions like "COUNT(name )"
            // so we remove the space in this situation
            $name_to_use_in_sort = str_replace([' )', '``'], [')', '`'], $name_to_use_in_sort);
            $name_to_use_in_sort = trim($name_to_use_in_sort, '`');

            // If this the first column name in the order by clause add
            // order by clause to the  column name
            $query_head = $is_first_clause ? "\nORDER BY " : "";
            // Again a check to see if the given column is a aggregate column
            if (mb_strpos($name_to_use_in_sort, '(') !== false) {
                $sort_order .=  $query_head . $name_to_use_in_sort . ' ' ;
            } else {
                if (strlen($sort_tbl_new) > 0) {
                    $sort_tbl_new .= ".";
                }
                $sort_order .=  $query_head . $sort_tbl_new
                  . Util::backquote(
                      $name_to_use_in_sort
                  ) . ' ' ;
            }

            // For a special case where the code generates two dots between
            // column name and table name.
            $sort_order = preg_replace("/\.\./", ".", $sort_order);
            // Incase this is the current column save $single_sort_order
            if ($current_name == $name_to_use_in_sort) {
                if (mb_strpos($current_name, '(') !== false) {
                    $single_sort_order = "\n" . 'ORDER BY ' . Util::backquote($current_name) . ' ';
                } else {
                    $single_sort_order = "\n" . 'ORDER BY ' . $sort_tbl
                        . Util::backquote(
                            $current_name
                        ) . ' ';
                }
                if ($is_in_sort) {
                    list($single_sort_order, $order_img)
                        = $this->_getSortingUrlParams(
                            $sort_direction,
                            $single_sort_order,
                            $index
                        );
                } else {
                    $single_sort_order .= strtoupper($sort_direction[$index]);
                }
            }
            if ($current_name == $name_to_use_in_sort && $is_in_sort) {
                // We need to generate the arrow button and related html
                list($sort_order, $order_img) = $this->_getSortingUrlParams(
                    $sort_direction,
                    $sort_order,
                    $index
                );
                $order_img .= " <small>" . ($index + 1) . "</small>";
            } else {
                $sort_order .= strtoupper($sort_direction[$index]);
            }
            // Separate columns by a comma
            $sort_order .= ", ";
        }
        // remove the comma from the last column name in the newly
        // constructed clause
        $sort_order = mb_substr(
            $sort_order,
            0,
            mb_strlen($sort_order) - 2
        );
        if (empty($order_img)) {
            $order_img = '';
        }
        return [
            $single_sort_order,
            $sort_order,
            $order_img,
        ];
    }

    /**
     * Check whether the column is sorted
     *
     * @param array  $sort_expression             sort expression
     * @param array  $sort_expression_nodirection sort expression without direction
     * @param string $sort_tbl                    the table name
     * @param string $name_to_use_in_sort         the sorting column name
     *
     * @return boolean                   the column sorted or not
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _isInSorted(
        array $sort_expression,
        array $sort_expression_nodirection,
        $sort_tbl,
        $name_to_use_in_sort
    ) {

        $index_in_expression = 0;

        foreach ($sort_expression_nodirection as $index => $clause) {
            if (mb_strpos($clause, '.') !== false) {
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
            $noSortTable = empty($sort_tbl) || mb_strpos(
                $sort_expression_nodirection[$index_in_expression],
                $sort_tbl
            ) === false;
            $noOpenParenthesis = mb_strpos(
                $sort_expression_nodirection[$index_in_expression],
                '('
            ) === false;
            if (! empty($sort_tbl) && $noSortTable && $noOpenParenthesis) {
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
    }

    /**
     * Get sort url parameters - sort order and order image
     *
     * @param array   $sort_direction the sort direction
     * @param string  $sort_order     the sorting order
     * @param integer $index          the index of sort direction array.
     *
     * @return  array                       2 element array - $sort_order, $order_img
     *
     * @access  private
     *
     * @see     _getSingleAndMultiSortUrls()
     */
    private function _getSortingUrlParams(array $sort_direction, $sort_order, $index)
    {
        if (strtoupper(trim($sort_direction[$index])) == self::DESCENDING_SORT_DIR) {
            $sort_order .= ' ASC';
            $order_img   = ' ' . Util::getImage(
                's_desc',
                __('Descending'),
                [
                    'class' => "soimg",
                    'title' => '',
                ]
            );
            $order_img  .= ' ' . Util::getImage(
                's_asc',
                __('Ascending'),
                [
                    'class' => "soimg hide",
                    'title' => '',
                ]
            );
        } else {
            $sort_order .= ' DESC';
            $order_img   = ' ' . Util::getImage(
                's_asc',
                __('Ascending'),
                [
                    'class' => "soimg",
                    'title' => '',
                ]
            );
            $order_img  .=  ' ' . Util::getImage(
                's_desc',
                __('Descending'),
                [
                    'class' => "soimg hide",
                    'title' => '',
                ]
            );
        }
        return [
            $sort_order,
            $order_img,
        ];
    }

    /**
     * Get sort order link
     *
     * @param string   $order_img       the sort order image
     * @param stdClass $fields_meta     set of field properties
     * @param string   $order_url       the url for sort
     * @param string   $multi_order_url the url for sort
     *
     * @return  string                      the sort order link
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getSortOrderLink(
        $order_img,
        $fields_meta,
        $order_url,
        $multi_order_url
    ) {
        $order_link_params = [
            'class' => 'sortlink',
        ];

        $order_link_content = htmlspecialchars($fields_meta->name);
        $inner_link_content = $order_link_content . $order_img
            . '<input type="hidden" value="' . $multi_order_url . '">';

        return Util::linkOrButton(
            $order_url,
            $inner_link_content,
            $order_link_params
        );
    }

    /**
     * Check if the column contains numeric data. If yes, then set the
     * column header's alignment right
     *
     * @param stdClass $fields_meta set of field properties
     * @param array    $th_class    array containing classes
     *
     * @return void
     *
     * @see  _getDraggableClassForSortableColumns()
     */
    private function _getClassForNumericColumnType($fields_meta, array &$th_class)
    {
        if (preg_match(
            '@int|decimal|float|double|real|bit|boolean|serial@i',
            (string) $fields_meta->type
        )) {
            $th_class[] = 'right';
        }
    }

    /**
     * Prepare columns to draggable effect for sortable columns
     *
     * @param boolean  $col_visib   the column is visible (false)
     *                              array                the column is not visible (string array)
     * @param string   $col_visib_j element of $col_visib array
     * @param stdClass $fields_meta set of field properties
     * @param string   $order_link  the order link
     * @param string   $comments    the comment for the column
     *
     * @return  string     html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getDraggableClassForSortableColumns(
        $col_visib,
        $col_visib_j,
        $fields_meta,
        $order_link,
        $comments
    ) {

        $draggable_html = '<th';
        $th_class = [];
        $th_class[] = 'draggable';
        $this->_getClassForNumericColumnType($fields_meta, $th_class);
        if ($col_visib && ! $col_visib_j) {
            $th_class[] = 'hide';
        }

        $th_class[] = 'column_heading';
        $th_class[] = 'sticky';
        if ($GLOBALS['cfg']['BrowsePointerEnable'] == true) {
            $th_class[] = 'pointer';
        }

        if ($GLOBALS['cfg']['BrowseMarkerEnable'] == true) {
            $th_class[] = 'marker';
        }

        $draggable_html .= ' class="' . implode(' ', $th_class) . '"';

        $draggable_html .= ' data-column="' . htmlspecialchars($fields_meta->name)
            . '">' . $order_link . $comments . '</th>';

        return $draggable_html;
    }

    /**
     * Prepare columns to draggable effect for non sortable columns
     *
     * @param boolean  $col_visib       the column is visible (false)
     *                                  array                    the column is not visible (string array)
     * @param string   $col_visib_j     element of $col_visib array
     * @param boolean  $condition_field whether to add CSS class condition
     * @param stdClass $fields_meta     set of field properties
     * @param string   $comments        the comment for the column
     *
     * @return  string         html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getDraggableClassForNonSortableColumns(
        $col_visib,
        $col_visib_j,
        $condition_field,
        $fields_meta,
        $comments
    ) {

        $draggable_html = '<th';
        $th_class = [];
        $th_class[] = 'draggable';
        $th_class[] = 'sticky';
        $this->_getClassForNumericColumnType($fields_meta, $th_class);
        if ($col_visib && ! $col_visib_j) {
            $th_class[] = 'hide';
        }

        if ($condition_field) {
            $th_class[] = 'condition';
        }

        $draggable_html .= ' class="' . implode(' ', $th_class) . '"';

        $draggable_html .= ' data-column="'
            . htmlspecialchars((string) $fields_meta->name) . '">';

        $draggable_html .= htmlspecialchars((string) $fields_meta->name);

        $draggable_html .= "\n" . $comments . '</th>';

        return $draggable_html;
    }

    /**
     * Prepare column to show at right side - check boxes or empty column
     *
     * @param array  $displayParts              which elements to display
     * @param string $full_or_partial_text_link full/partial link or text button
     * @param string $colspan                   column span of table header
     *
     * @return  string  html content
     *
     * @access  private
     *
     * @see     _getTableHeaders()
     */
    private function _getColumnAtRightSide(
        array &$displayParts,
        $full_or_partial_text_link,
        $colspan
    ) {

        $right_column_html = '';
        $display_params = $this->__get('display_params');

        // Displays the needed checkboxes at the right
        // column of the result table header if possible and required...
        if (($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_RIGHT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH)
            && (($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
            || ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE))
            && ($displayParts['text_btn'] == '1')
        ) {
            $display_params['emptyafter']
                = ($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE) ? 4 : 1;

            $right_column_html .= "\n"
                . '<th class="column_action print_ignore" ' . $colspan . '>'
                . $full_or_partial_text_link
                . '</th>';
        } elseif (($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_LEFT)
            || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH)
            && (($displayParts['edit_lnk'] == self::NO_EDIT_OR_DELETE)
            && ($displayParts['del_lnk'] == self::NO_EDIT_OR_DELETE))
            && (! isset($GLOBALS['is_header_sent']) || ! $GLOBALS['is_header_sent'])
        ) {
            //     ... elseif no button, displays empty columns if required
            // (unless coming from Browse mode print view)

            $display_params['emptyafter']
                = ($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                && ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE) ? 4 : 1;

            $right_column_html .= "\n" . '<td class="print_ignore" ' . $colspan
                . '></td>';
        }

        $this->__set('display_params', $display_params);

        return $right_column_html;
    }

    /**
     * Prepares the display for a value
     *
     * @param string $class          class of table cell
     * @param bool   $conditionField whether to add CSS class condition
     * @param string $value          value to display
     *
     * @return string  the td
     *
     * @access  private
     *
     * @see     _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericColumns()
     */
    private function _buildValueDisplay($class, $conditionField, $value)
    {
        return $this->template->render('display/results/value_display', [
            'class' => $class,
            'condition_field' => $conditionField,
            'value' => $value,
        ]);
    }

    /**
     * Prepares the display for a null value
     *
     * @param string   $class          class of table cell
     * @param bool     $conditionField whether to add CSS class condition
     * @param stdClass $meta           the meta-information about this field
     * @param string   $align          cell alignment
     *
     * @return string  the td
     *
     * @access  private
     *
     * @see     _getDataCellForNumericColumns(),
     *          _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericColumns()
     */
    private function _buildNullDisplay($class, $conditionField, $meta, $align = '')
    {
        $classes = $this->_addClass($class, $conditionField, $meta, '');

        return $this->template->render('display/results/null_display', [
            'align' => $align,
            'meta' => $meta,
            'classes' => $classes,
        ]);
    }

    /**
     * Prepares the display for an empty value
     *
     * @param string   $class          class of table cell
     * @param bool     $conditionField whether to add CSS class condition
     * @param stdClass $meta           the meta-information about this field
     * @param string   $align          cell alignment
     *
     * @return string  the td
     *
     * @access  private
     *
     * @see     _getDataCellForNumericColumns(),
     *          _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericColumns()
     */
    private function _buildEmptyDisplay($class, $conditionField, $meta, $align = '')
    {
        $classes = $this->_addClass($class, $conditionField, $meta, 'nowrap');

        return $this->template->render('display/results/empty_display', [
            'align' => $align,
            'classes' => $classes,
        ]);
    }

    /**
     * Adds the relevant classes.
     *
     * @param string                       $class                 class of table cell
     * @param bool                         $condition_field       whether to add CSS class
     *                                                            condition
     * @param stdClass                     $meta                  the meta-information about the
     *                                                            field
     * @param string                       $nowrap                avoid wrapping
     * @param bool                         $is_field_truncated    is field truncated (display ...)
     * @param TransformationsPlugin|string $transformation_plugin transformation plugin.
     *                                                            Can also be the default function:
     *                                                            Core::mimeDefaultFunction
     * @param string                       $default_function      default transformation function
     *
     * @return string the list of classes
     *
     * @access private
     *
     * @see _buildNullDisplay(), _getRowData()
     */
    private function _addClass(
        $class,
        $condition_field,
        $meta,
        $nowrap,
        $is_field_truncated = false,
        $transformation_plugin = '',
        $default_function = ''
    ) {
        $classes = [
            $class,
            $nowrap,
        ];

        if (isset($meta->mimetype)) {
            $classes[] = preg_replace('/\//', '_', $meta->mimetype);
        }

        if ($condition_field) {
            $classes[] = 'condition';
        }

        if ($is_field_truncated) {
            $classes[] = 'truncated';
        }

        $mime_map = $this->__get('mime_map');
        $orgFullColName = $this->__get('db') . '.' . $meta->orgtable
            . '.' . $meta->orgname;
        if ($transformation_plugin != $default_function
            || ! empty($mime_map[$orgFullColName]['input_transformation'])
        ) {
            $classes[] = 'transformed';
        }

        // Define classes to be added to this data field based on the type of data
        $matches = [
            'enum' => 'enum',
            'set' => 'set',
            'binary' => 'hex',
        ];

        foreach ($matches as $key => $value) {
            if (mb_strpos($meta->flags, $key) !== false) {
                $classes[] = $value;
            }
        }

        if (mb_strpos($meta->type, 'bit') !== false) {
            $classes[] = 'bit';
        }

        return implode(' ', $classes);
    }

    /**
     * Prepare the body of the results table
     *
     * @param integer $dt_result            the link id associated to the query
     *                                      which results have to be displayed
     * @param array   $displayParts         which elements to display
     * @param array   $map                  the list of relations
     * @param array   $analyzed_sql_results analyzed sql results
     * @param boolean $is_limited_display   with limited operations or not
     *
     * @return string  html content
     *
     * @global array  $row                  current row data
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _getTableBody(
        &$dt_result,
        array &$displayParts,
        array $map,
        array $analyzed_sql_results,
        $is_limited_display = false
    ) {
        global $row; // mostly because of browser transformations,
                     // to make the row-data accessible in a plugin

        $table_body_html = '';

        // query without conditions to shorten URLs when needed, 200 is just
        // guess, it should depend on remaining URL length
        $url_sql_query = $this->_getUrlSqlQuery($analyzed_sql_results);

        $display_params = $this->__get('display_params');

        if (! is_array($map)) {
            $map = [];
        }

        $row_no                       = 0;
        $display_params['edit']       = [];
        $display_params['copy']       = [];
        $display_params['delete']     = [];
        $display_params['data']       = [];
        $display_params['row_delete'] = [];
        $this->__set('display_params', $display_params);

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
                default: // 'disabled'
                    $grid_edit_class = '';
                    break;
            }
        }

        // prepare to get the column order, if available
        list($col_order, $col_visib) = $this->_getColumnParams(
            $analyzed_sql_results
        );

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

        $whereClauseMap = $this->__get('whereClauseMap');
        while ($row = $GLOBALS['dbi']->fetchRow($dt_result)) {
            // add repeating headers
            if (($row_no != 0) && ($_SESSION['tmpval']['repeat_cells'] != 0)
                && ! ($row_no % $_SESSION['tmpval']['repeat_cells'])
            ) {
                $table_body_html .= $this->_getRepeatingHeaders(
                    $display_params
                );
            }

            $tr_class = [];
            if ($GLOBALS['cfg']['BrowsePointerEnable'] != true) {
                $tr_class[] = 'nopointer';
            }
            if ($GLOBALS['cfg']['BrowseMarkerEnable'] != true) {
                $tr_class[] = 'nomarker';
            }

            // pointer code part
            $classes = (empty($tr_class) ? ' ' : 'class="' . implode(' ', $tr_class) . '"');
            $table_body_html .= '<tr ' . $classes . ' >';

            // 1. Prepares the row

            // In print view these variable needs to be initialized
            $del_url = $del_str = $edit_anchor_class
                = $edit_str = $js_conf = $copy_url = $copy_str = $edit_url = null;

            // 1.2 Defines the URLs for the modify/delete link(s)

            if (($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                || ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE)
            ) {
                // Results from a "SELECT" statement -> builds the
                // WHERE clause to use in links (a unique key if possible)
                /**
                 * @todo $where_clause could be empty, for example a table
                 *       with only one field and it's a BLOB; in this case,
                 *       avoid to display the delete and edit links
                 */
                list($where_clause, $clause_is_unique, $condition_array)
                    = Util::getUniqueCondition(
                        $dt_result, // handle
                        $this->__get('fields_cnt'), // fields_cnt
                        $this->__get('fields_meta'), // fields_meta
                        $row, // row
                        false, // force_unique
                        $this->__get('table'), // restrict_to_table
                        $analyzed_sql_results // analyzed_sql_results
                    );
                $whereClauseMap[$row_no][$this->__get('table')] = $where_clause;
                $this->__set('whereClauseMap', $whereClauseMap);

                $where_clause_html = htmlspecialchars($where_clause);

                // 1.2.1 Modify link(s) - update row case
                if ($displayParts['edit_lnk'] == self::UPDATE_ROW) {
                    list($edit_url, $copy_url, $edit_str, $copy_str,
                        $edit_anchor_class)
                            = $this->_getModifiedLinks(
                                $where_clause,
                                $clause_is_unique,
                                $url_sql_query
                            );
                } // end if (1.2.1)

                // 1.2.2 Delete/Kill link(s)
                list($del_url, $del_str, $js_conf)
                    = $this->_getDeleteAndKillLinks(
                        $where_clause,
                        $clause_is_unique,
                        $url_sql_query,
                        $displayParts['del_lnk'],
                        $row
                    );

                // 1.3 Displays the links at left if required
                if (($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_LEFT)
                    || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH)
                ) {
                    $table_body_html .= $this->_getPlacedLinks(
                        self::POSITION_LEFT,
                        $del_url,
                        $displayParts,
                        $row_no,
                        $where_clause,
                        $where_clause_html,
                        $condition_array,
                        $edit_url,
                        $copy_url,
                        $edit_anchor_class,
                        $edit_str,
                        $copy_str,
                        $del_str,
                        $js_conf
                    );
                } elseif ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_NONE) {
                    $table_body_html .= $this->_getPlacedLinks(
                        self::POSITION_NONE,
                        $del_url,
                        $displayParts,
                        $row_no,
                        $where_clause,
                        $where_clause_html,
                        $condition_array,
                        $edit_url,
                        $copy_url,
                        $edit_anchor_class,
                        $edit_str,
                        $copy_str,
                        $del_str,
                        $js_conf
                    );
                } // end if (1.3)
            } // end if (1)

            // 2. Displays the rows' values
            if ($this->__get('mime_map') === null) {
                $this->_setMimeMap();
            }
            $table_body_html .= $this->_getRowValues(
                $dt_result,
                $row,
                $row_no,
                $col_order,
                $map,
                $grid_edit_class,
                $col_visib,
                $url_sql_query,
                $analyzed_sql_results
            );

            // 3. Displays the modify/delete links on the right if required
            if (($displayParts['edit_lnk'] != self::NO_EDIT_OR_DELETE)
                || ($displayParts['del_lnk'] != self::NO_EDIT_OR_DELETE)
            ) {
                if (($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_RIGHT)
                    || ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_BOTH)
                ) {
                    $table_body_html .= $this->_getPlacedLinks(
                        self::POSITION_RIGHT,
                        $del_url,
                        $displayParts,
                        $row_no,
                        $where_clause,
                        $where_clause_html,
                        $condition_array,
                        $edit_url,
                        $copy_url,
                        $edit_anchor_class,
                        $edit_str,
                        $copy_str,
                        $del_str,
                        $js_conf
                    );
                }
            } // end if (3)

            $table_body_html .= '</tr>';
            $table_body_html .= "\n";
            $row_no++;
        } // end while

        return $table_body_html;
    }

    /**
     * Sets the MIME details of the columns in the results set
     *
     * @return void
     */
    private function _setMimeMap()
    {
        $fields_meta = $this->__get('fields_meta');
        $mimeMap = [];
        $added = [];

        for ($currentColumn = 0; $currentColumn < $this->__get('fields_cnt'); ++$currentColumn) {
            $meta = $fields_meta[$currentColumn];
            $orgFullTableName = $this->__get('db') . '.' . $meta->orgtable;

            if ($GLOBALS['cfgRelation']['commwork']
                && $GLOBALS['cfgRelation']['mimework']
                && $GLOBALS['cfg']['BrowseMIME']
                && ! $_SESSION['tmpval']['hide_transformation']
                && empty($added[$orgFullTableName])
            ) {
                $mimeMap = array_merge(
                    $mimeMap,
                    $this->transformations->getMime($this->__get('db'), $meta->orgtable, false, true)
                );
                $added[$orgFullTableName] = true;
            }
        }

        // special browser transformation for some SHOW statements
        if ($this->__get('is_show')
            && ! $_SESSION['tmpval']['hide_transformation']
        ) {
            preg_match(
                '@^SHOW[[:space:]]+(VARIABLES|(FULL[[:space:]]+)?'
                . 'PROCESSLIST|STATUS|TABLE|GRANTS|CREATE|LOGS|DATABASES|FIELDS'
                . ')@i',
                $this->__get('sql_query'),
                $which
            );

            if (isset($which[1])) {
                $str = ' ' . strtoupper($which[1]);
                $isShowProcessList = strpos($str, 'PROCESSLIST') > 0;
                if ($isShowProcessList) {
                    $mimeMap['..Info'] = [
                        'mimetype' => 'Text_Plain',
                        'transformation' => 'output/Text_Plain_Sql.php',
                    ];
                }

                $isShowCreateTable = preg_match(
                    '@CREATE[[:space:]]+TABLE@i',
                    $this->__get('sql_query')
                );
                if ($isShowCreateTable) {
                    $mimeMap['..Create Table'] = [
                        'mimetype' => 'Text_Plain',
                        'transformation' => 'output/Text_Plain_Sql.php',
                    ];
                }
            }
        }

        $this->__set('mime_map', $mimeMap);
    }

    /**
     * Get the values for one data row
     *
     * @param integer              $dt_result            the link id associated to
     *                                                   the query which results
     *                                                   have to be displayed
     * @param array                $row                  current row data
     * @param integer              $row_no               the index of current row
     * @param array|boolean        $col_order            the column order false when
     *                                                   a property not found false
     *                                                   when a property not found
     * @param array                $map                  the list of relations
     * @param string               $grid_edit_class      the class for all editable
     *                                                   columns
     * @param boolean|array|string $col_visib            column is visible(false);
     *                                                   column isn't visible(string
     *                                                   array)
     * @param string               $url_sql_query        the analyzed sql query
     * @param array                $analyzed_sql_results analyzed sql results
     *
     * @return  string  html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getRowValues(
        &$dt_result,
        array $row,
        $row_no,
        $col_order,
        array $map,
        $grid_edit_class,
        $col_visib,
        $url_sql_query,
        array $analyzed_sql_results
    ) {
        $row_values_html = '';

        // Following variable are needed for use in isset/empty or
        // use with array indexes/safe use in foreach
        $sql_query = $this->__get('sql_query');
        $fields_meta = $this->__get('fields_meta');
        $highlight_columns = $this->__get('highlight_columns');
        $mime_map = $this->__get('mime_map');

        $row_info = $this->_getRowInfoForSpecialLinks($row, $col_order);

        $whereClauseMap = $this->__get('whereClauseMap');

        $columnCount = $this->__get('fields_cnt');
        for ($currentColumn = 0; $currentColumn < $columnCount; ++$currentColumn) {
            // assign $i with appropriate column order
            $i = $col_order ? $col_order[$currentColumn] : $currentColumn;

            $meta    = $fields_meta[$i];
            $orgFullColName
                = $this->__get('db') . '.' . $meta->orgtable . '.' . $meta->orgname;

            $not_null_class = $meta->not_null ? 'not_null' : '';
            $relation_class = isset($map[$meta->name]) ? 'relation' : '';
            $hide_class = $col_visib && isset($col_visib[$currentColumn]) && ! $col_visib[$currentColumn]
                ? 'hide'
                : '';
            $grid_edit = $meta->orgtable != '' ? $grid_edit_class : '';

            // handle datetime-related class, for grid editing
            $field_type_class
                = $this->_getClassForDateTimeRelatedFields($meta->type);

            $is_field_truncated = false;
            // combine all the classes applicable to this column's value
            $class = $this->_getClassesForColumn(
                $grid_edit,
                $not_null_class,
                $relation_class,
                $hide_class,
                $field_type_class
            );

            //  See if this column should get highlight because it's used in the
            //  where-query.
            $condition_field = isset($highlight_columns)
                && (isset($highlight_columns[$meta->name])
                || isset($highlight_columns[Util::backquote($meta->name)]))
                ? true
                : false;

            // Wrap MIME-transformations. [MIME]
            $default_function = [
                Core::class,
                'mimeDefaultFunction',
            ]; // default_function
            $transformation_plugin = $default_function;
            $transform_options = [];

            if ($GLOBALS['cfgRelation']['mimework']
                && $GLOBALS['cfg']['BrowseMIME']
            ) {
                if (isset($mime_map[$orgFullColName]['mimetype'])
                    && ! empty($mime_map[$orgFullColName]['transformation'])
                ) {
                    $file = $mime_map[$orgFullColName]['transformation'];
                    $include_file = 'libraries/classes/Plugins/Transformations/' . $file;

                    if (@file_exists($include_file)) {
                        $class_name = $this->transformations->getClassName($include_file);
                        if (class_exists($class_name)) {
                            // todo add $plugin_manager
                            $plugin_manager = null;
                            $transformation_plugin = new $class_name(
                                $plugin_manager
                            );

                            $transform_options = $this->transformations->getOptions(
                                isset(
                                    $mime_map[$orgFullColName]['transformation_options']
                                )
                                ? $mime_map[$orgFullColName]['transformation_options']
                                : ''
                            );

                            $meta->mimetype = str_replace(
                                '_',
                                '/',
                                $mime_map[$orgFullColName]['mimetype']
                            );
                        }
                    } // end if file_exists
                } // end if transformation is set
            } // end if mime/transformation works.

            // Check whether the field needs to display with syntax highlighting

            $dbLower = mb_strtolower($this->__get('db'));
            $tblLower = mb_strtolower($meta->orgtable);
            $nameLower = mb_strtolower($meta->orgname);
            if (! empty($this->transformation_info[$dbLower][$tblLower][$nameLower])
                && isset($row[$i])
                && (trim($row[$i]) != '')
                && ! $_SESSION['tmpval']['hide_transformation']
            ) {
                include_once $this->transformation_info[$dbLower][$tblLower][$nameLower][0];
                $transformation_plugin = new $this->transformation_info[$dbLower][$tblLower][$nameLower][1](null);

                $transform_options = $this->transformations->getOptions(
                    isset($mime_map[$orgFullColName]['transformation_options'])
                    ? $mime_map[$orgFullColName]['transformation_options']
                    : ''
                );

                $meta->mimetype = str_replace(
                    '_',
                    '/',
                    $this->transformation_info[$dbLower][mb_strtolower($meta->orgtable)][mb_strtolower($meta->orgname)][2]
                );
            }

            // Check for the predefined fields need to show as link in schemas
            $specialSchemaLinks = SpecialSchemaLinks::get();

            if (! empty($specialSchemaLinks[$dbLower][$tblLower][$nameLower])) {
                $linking_url = $this->_getSpecialLinkUrl(
                    $specialSchemaLinks,
                    $row[$i],
                    $row_info,
                    mb_strtolower($meta->orgname)
                );
                $transformation_plugin = new Text_Plain_Link();

                $transform_options  = [
                    0 => $linking_url,
                    2 => true,
                ];

                $meta->mimetype = str_replace(
                    '_',
                    '/',
                    'Text/Plain'
                );
            }

            /*
             * The result set can have columns from more than one table,
             * this is why we have to check for the unique conditions
             * related to this table; however getUniqueCondition() is
             * costly and does not need to be called if we already know
             * the conditions for the current table.
             */
            if (! isset($whereClauseMap[$row_no][$meta->orgtable])) {
                $unique_conditions = Util::getUniqueCondition(
                    $dt_result, // handle
                    $this->__get('fields_cnt'), // fields_cnt
                    $this->__get('fields_meta'), // fields_meta
                    $row, // row
                    false, // force_unique
                    $meta->orgtable, // restrict_to_table
                    $analyzed_sql_results // analyzed_sql_results
                );
                $whereClauseMap[$row_no][$meta->orgtable] = $unique_conditions[0];
            }

            $_url_params = [
                'db'            => $this->__get('db'),
                'table'         => $meta->orgtable,
                'where_clause_sign' => Core::signSqlQuery($whereClauseMap[$row_no][$meta->orgtable]),
                'where_clause'  => $whereClauseMap[$row_no][$meta->orgtable],
                'transform_key' => $meta->orgname,
            ];

            if (! empty($sql_query)) {
                $_url_params['sql_query'] = $url_sql_query;
            }

            $transform_options['wrapper_link'] = Url::getCommon($_url_params);

            $display_params = $this->__get('display_params');

            // in some situations (issue 11406), numeric returns 1
            // even for a string type
            // for decimal numeric is returning 1
            // have to improve logic
            if (($meta->numeric == 1 && $meta->type != 'string') || $meta->type == 'real') {
                // n u m e r i c

                $display_params['data'][$row_no][$i]
                    = $this->_getDataCellForNumericColumns(
                        $row[$i] === null ? null : (string) $row[$i],
                        $class,
                        $condition_field,
                        $meta,
                        $map,
                        $is_field_truncated,
                        $analyzed_sql_results,
                        $transformation_plugin,
                        $default_function,
                        $transform_options
                    );
            } elseif ($meta->type == self::GEOMETRY_FIELD) {
                // g e o m e t r y

                // Remove 'grid_edit' from $class as we do not allow to
                // inline-edit geometry data.
                $class = str_replace('grid_edit', '', $class);

                $display_params['data'][$row_no][$i]
                    = $this->_getDataCellForGeometryColumns(
                        $row[$i],
                        $class,
                        $meta,
                        $map,
                        $_url_params,
                        $condition_field,
                        $transformation_plugin,
                        $default_function,
                        $transform_options,
                        $analyzed_sql_results
                    );
            } else {
                // n o t   n u m e r i c

                $display_params['data'][$row_no][$i]
                    = $this->_getDataCellForNonNumericColumns(
                        $row[$i],
                        $class,
                        $meta,
                        $map,
                        $_url_params,
                        $condition_field,
                        $transformation_plugin,
                        $default_function,
                        $transform_options,
                        $is_field_truncated,
                        $analyzed_sql_results,
                        $dt_result,
                        $i
                    );
            }

            // output stored cell
            $row_values_html .= $display_params['data'][$row_no][$i];

            if (isset($display_params['rowdata'][$i][$row_no])) {
                $display_params['rowdata'][$i][$row_no]
                    .= $display_params['data'][$row_no][$i];
            } else {
                $display_params['rowdata'][$i][$row_no]
                    = $display_params['data'][$row_no][$i];
            }

            $this->__set('display_params', $display_params);
        } // end for

        return $row_values_html;
    }

    /**
     * Get link for display special schema links
     *
     * @param array  $specialSchemaLinks special schema links
     * @param string $column_value       column value
     * @param array  $row_info           information about row
     * @param string $field_name         column name
     *
     * @return string generated link
     */
    private function _getSpecialLinkUrl(
        array $specialSchemaLinks,
        $column_value,
        array $row_info,
        $field_name
    ) {
        $linking_url_params = [];
        $link_relations = $specialSchemaLinks[mb_strtolower($this->__get('db'))][mb_strtolower($this->__get('table'))][$field_name];

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

        $divider = strpos($link_relations['default_page'], '?') ? '&' : '?';
        if (empty($link_relations['link_dependancy_params'])) {
            return $link_relations['default_page']
                . Url::getCommonRaw($linking_url_params, $divider);
        }

        foreach ($link_relations['link_dependancy_params'] as $new_param) {
            // If param_info is an array, set the key and value
            // from that array
            if (is_array($new_param['param_info'])) {
                $linking_url_params[$new_param['param_info'][0]]
                    = $new_param['param_info'][1];
                continue;
            }

            $linking_url_params[$new_param['param_info']]
                = $row_info[mb_strtolower($new_param['column_name'])];

            // Special case 1 - when executing routines, according
            // to the type of the routine, url param changes
            if (empty($row_info['routine_type'])) {
                continue;
            }
        }

        return $link_relations['default_page']
            . Url::getCommonRaw($linking_url_params, $divider);
    }

    /**
     * Prepare row information for display special links
     *
     * @param array         $row       current row data
     * @param array|boolean $col_order the column order
     *
     * @return array associative array with column nama -> value
     */
    private function _getRowInfoForSpecialLinks(array $row, $col_order)
    {

        $row_info = [];
        $fields_meta = $this->__get('fields_meta');

        for ($n = 0; $n < $this->__get('fields_cnt'); ++$n) {
            $m = $col_order ? $col_order[$n] : $n;
            $row_info[mb_strtolower($fields_meta[$m]->orgname)]
                = $row[$m];
        }

        return $row_info;
    }

    /**
     * Get url sql query without conditions to shorten URLs
     *
     * @param array $analyzed_sql_results analyzed sql results
     *
     * @return  string        analyzed sql query
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getUrlSqlQuery(array $analyzed_sql_results)
    {
        if (($analyzed_sql_results['querytype'] != 'SELECT')
            || (mb_strlen($this->__get('sql_query')) < 200)
        ) {
            return $this->__get('sql_query');
        }

        $query = 'SELECT ' . Query::getClause(
            $analyzed_sql_results['statement'],
            $analyzed_sql_results['parser']->list,
            'SELECT'
        );

        $from_clause = Query::getClause(
            $analyzed_sql_results['statement'],
            $analyzed_sql_results['parser']->list,
            'FROM'
        );

        if (! empty($from_clause)) {
            $query .= ' FROM ' . $from_clause;
        }

        return $query;
    }

    /**
     * Get column order and column visibility
     *
     * @param array $analyzed_sql_results analyzed sql results
     *
     * @return  array           2 element array - $col_order, $col_visib
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getColumnParams(array $analyzed_sql_results)
    {
        if ($this->_isSelect($analyzed_sql_results)) {
            $pmatable = new Table($this->__get('table'), $this->__get('db'));
            $col_order = $pmatable->getUiProp(Table::PROP_COLUMN_ORDER);
            /* Validate the value */
            if ($col_order !== false) {
                $fields_cnt = $this->__get('fields_cnt');
                foreach ($col_order as $value) {
                    if ($value >= $fields_cnt) {
                        $pmatable->removeUiProp(Table::PROP_COLUMN_ORDER);
                        $fields_cnt = false;
                    }
                }
            }
            $col_visib = $pmatable->getUiProp(Table::PROP_COLUMN_VISIB);
        } else {
            $col_order = false;
            $col_visib = false;
        }

        return [
            $col_order,
            $col_visib,
        ];
    }

    /**
     * Get HTML for repeating headers
     *
     * @param array $display_params holds various display info
     *
     * @return  string    html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getRepeatingHeaders(
        array $display_params
    ) {
        $header_html = '<tr>' . "\n";

        if ($display_params['emptypre'] > 0) {
            $header_html .= '    <th colspan="'
                . $display_params['emptypre'] . '">'
                . "\n" . '        &nbsp;</th>' . "\n";
        } elseif ($GLOBALS['cfg']['RowActionLinks'] == self::POSITION_NONE) {
            $header_html .= '    <th></th>' . "\n";
        }

        foreach ($display_params['desc'] as $val) {
            $header_html .= $val;
        }

        if ($display_params['emptyafter'] > 0) {
            $header_html
                .= '    <th colspan="' . $display_params['emptyafter']
                . '">'
                . "\n" . '        &nbsp;</th>' . "\n";
        }
        $header_html .= '</tr>' . "\n";

        return $header_html;
    }

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
        $where_clause,
        $clause_is_unique,
        $url_sql_query
    ) {

        $_url_params = [
            'db'               => $this->__get('db'),
            'table'            => $this->__get('table'),
            'where_clause'     => $where_clause,
            'clause_is_unique' => $clause_is_unique,
            'sql_query'        => $url_sql_query,
            'goto'             => 'sql.php',
        ];

        $edit_url = 'tbl_change.php'
            . Url::getCommon(
                $_url_params + ['default_action' => 'update']
            );

        $copy_url = 'tbl_change.php'
            . Url::getCommon(
                $_url_params + ['default_action' => 'insert']
            );

        $edit_str = $this->_getActionLinkContent(
            'b_edit',
            __('Edit')
        );
        $copy_str = $this->_getActionLinkContent(
            'b_insrow',
            __('Copy')
        );

        // Class definitions required for grid editing jQuery scripts
        $edit_anchor_class = "edit_row_anchor";
        if ($clause_is_unique == 0) {
            $edit_anchor_class .= ' nonunique';
        }

        return [
            $edit_url,
            $copy_url,
            $edit_str,
            $copy_str,
            $edit_anchor_class,
        ];
    }

    /**
     * Get delete and kill links
     *
     * @param string  $where_clause     the where clause of the sql
     * @param boolean $clause_is_unique the unique condition of clause
     * @param string  $url_sql_query    the analyzed sql query
     * @param string  $del_lnk          the delete link of current row
     * @param array   $row              the current row
     *
     * @return  array                       3 element array
     *                                      $del_url, $del_str, $js_conf
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getDeleteAndKillLinks(
        $where_clause,
        $clause_is_unique,
        $url_sql_query,
        $del_lnk,
        array $row
    ) {

        $goto = $this->__get('goto');

        if ($del_lnk == self::DELETE_ROW) { // delete row case
            $_url_params = [
                'db'        => $this->__get('db'),
                'table'     => $this->__get('table'),
                'sql_query' => $url_sql_query,
                'message_to_show' => __('The row has been deleted.'),
                'goto'      => empty($goto) ? 'tbl_sql.php' : $goto,
            ];

            $lnk_goto = 'sql.php' . Url::getCommonRaw($_url_params);

            $del_query = 'DELETE FROM '
                . Util::backquote($this->__get('table'))
                . ' WHERE ' . $where_clause .
                ($clause_is_unique ? '' : ' LIMIT 1');

            $_url_params = [
                'db'        => $this->__get('db'),
                'table'     => $this->__get('table'),
                'sql_query' => $del_query,
                'message_to_show' => __('The row has been deleted.'),
                'goto'      => $lnk_goto,
            ];
            $del_url  = 'sql.php' . Url::getCommon($_url_params);

            $js_conf  = 'DELETE FROM ' . Sanitize::jsFormat($this->__get('table'))
                . ' WHERE ' . Sanitize::jsFormat($where_clause, false)
                . ($clause_is_unique ? '' : ' LIMIT 1');

            $del_str = $this->_getActionLinkContent('b_drop', __('Delete'));
        } elseif ($del_lnk == self::KILL_PROCESS) { // kill process case
            $_url_params = [
                'db'        => $this->__get('db'),
                'table'     => $this->__get('table'),
                'sql_query' => $url_sql_query,
                'goto'      => 'index.php',
            ];

            $lnk_goto = 'sql.php' . Url::getCommonRaw($_url_params);

            $kill = $GLOBALS['dbi']->getKillQuery((int) $row[0]);

            $_url_params = [
                'db'        => 'mysql',
                'sql_query' => $kill,
                'goto'      => $lnk_goto,
            ];

            $del_url  = 'sql.php' . Url::getCommon($_url_params);
            $js_conf  = $kill;
            $del_str = Util::getIcon(
                'b_drop',
                __('Kill')
            );
        } else {
            $del_url = $del_str = $js_conf = null;
        }

        return [
            $del_url,
            $del_str,
            $js_conf,
        ];
    }

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
                . Util::getImage(
                    $icon,
                    $display_text
                )
                . '</span>';
        } elseif (isset($GLOBALS['cfg']['RowActionType'])
            && $GLOBALS['cfg']['RowActionType'] == self::ACTION_LINK_CONTENT_TEXT
        ) {
            $linkContent .= '<span class="nowrap">' . $display_text . '</span>';
        } else {
            $linkContent .= Util::getIcon(
                $icon,
                $display_text
            );
        }

        return $linkContent;
    }

    /**
     * Prepare placed links
     *
     * @param string      $dir               the direction of links should place
     * @param string      $del_url           the url for delete row
     * @param array       $displayParts      which elements to display
     * @param integer     $row_no            the index of current row
     * @param string      $where_clause      the where clause of the sql
     * @param string      $where_clause_html the html encoded where clause
     * @param array       $condition_array   array of keys (primary, unique, condition)
     * @param string      $edit_url          the url for edit row
     * @param string      $copy_url          the url for copy row
     * @param string      $edit_anchor_class the class for html element for edit
     * @param string      $edit_str          the label for edit row
     * @param string      $copy_str          the label for copy row
     * @param string      $del_str           the label for delete row
     * @param string|null $js_conf           text for the JS confirmation
     *
     * @return  string                      html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getPlacedLinks(
        $dir,
        $del_url,
        array $displayParts,
        $row_no,
        $where_clause,
        $where_clause_html,
        array $condition_array,
        $edit_url,
        $copy_url,
        $edit_anchor_class,
        $edit_str,
        $copy_str,
        $del_str,
        ?string $js_conf
    ) {

        if (! isset($js_conf)) {
            $js_conf = '';
        }

        return $this->_getCheckboxAndLinks(
            $dir,
            $del_url,
            $displayParts,
            $row_no,
            $where_clause,
            $where_clause_html,
            $condition_array,
            $edit_url,
            $copy_url,
            $edit_anchor_class,
            $edit_str,
            $copy_str,
            $del_str,
            $js_conf
        );
    }

    /**
     * Get the combined classes for a column
     *
     * @param string $grid_edit_class  the class for all editable columns
     * @param string $not_null_class   the class for not null columns
     * @param string $relation_class   the class for relations in a column
     * @param string $hide_class       the class for visibility of a column
     * @param string $field_type_class the class related to type of the field
     *
     * @return string the combined classes
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getClassesForColumn(
        $grid_edit_class,
        $not_null_class,
        $relation_class,
        $hide_class,
        $field_type_class
    ) {
        return 'data ' . $grid_edit_class . ' ' . $not_null_class . ' '
            . $relation_class . ' ' . $hide_class . ' ' . $field_type_class;
    }

    /**
     * Get class for datetime related fields
     *
     * @param string $type the type of the column field
     *
     * @return  string   the class for the column
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
        } elseif ($type == self::STRING_FIELD) {
            $field_type_class = 'text';
        } else {
            $field_type_class = '';
        }
        return $field_type_class;
    }

    /**
     * Prepare data cell for numeric type fields
     *
     * @param string|null           $column                the column's value
     * @param string                $class                 the html class for column
     * @param boolean               $condition_field       the column should highlighted
     *                                                     or not
     * @param stdClass              $meta                  the meta-information about this
     *                                                     field
     * @param array                 $map                   the list of relations
     * @param boolean               $is_field_truncated    the condition for blob data
     *                                                     replacements
     * @param array                 $analyzed_sql_results  the analyzed query
     * @param TransformationsPlugin $transformation_plugin the name of transformation plugin
     * @param string                $default_function      the default transformation
     *                                                     function
     * @param array                 $transform_options     the transformation parameters
     *
     * @return  string the prepared cell, html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getDataCellForNumericColumns(
        ?string $column,
        $class,
        $condition_field,
        $meta,
        array $map,
        $is_field_truncated,
        array $analyzed_sql_results,
        $transformation_plugin,
        $default_function,
        array $transform_options
    ) {

        if (! isset($column) || $column === null) {
            $cell = $this->_buildNullDisplay(
                'right ' . $class,
                $condition_field,
                $meta,
                ''
            );
        } elseif ($column != '') {
            $nowrap = ' nowrap';
            $where_comparison = ' = ' . $column;

            $cell = $this->_getRowData(
                'right ' . $class,
                $condition_field,
                $analyzed_sql_results,
                $meta,
                $map,
                $column,
                $column,
                $transformation_plugin,
                $default_function,
                $nowrap,
                $where_comparison,
                $transform_options,
                $is_field_truncated,
                ''
            );
        } else {
            $cell = $this->_buildEmptyDisplay(
                'right ' . $class,
                $condition_field,
                $meta,
                ''
            );
        }

        return $cell;
    }

    /**
     * Get data cell for geometry type fields
     *
     * @param string|null           $column                the relevant column in data row
     * @param string                $class                 the html class for column
     * @param stdClass              $meta                  the meta-information about
     *                                                     this field
     * @param array                 $map                   the list of relations
     * @param array                 $_url_params           the parameters for generate url
     * @param boolean               $condition_field       the column should highlighted
     *                                                     or not
     * @param TransformationsPlugin $transformation_plugin the name of transformation
     *                                                     function
     * @param string                $default_function      the default transformation
     *                                                     function
     * @param string                $transform_options     the transformation parameters
     * @param array                 $analyzed_sql_results  the analyzed query
     *
     * @return string the prepared data cell, html content
     *
     * @access private
     *
     * @see     _getTableBody()
     */
    private function _getDataCellForGeometryColumns(
        ?string $column,
        $class,
        $meta,
        array $map,
        array $_url_params,
        $condition_field,
        $transformation_plugin,
        $default_function,
        $transform_options,
        array $analyzed_sql_results
    ) {
        if (! isset($column) || $column === null) {
            $cell = $this->_buildNullDisplay($class, $condition_field, $meta);
            return $cell;
        }

        if ($column == '') {
            $cell = $this->_buildEmptyDisplay($class, $condition_field, $meta);
            return $cell;
        }

        // Display as [GEOMETRY - (size)]
        if ($_SESSION['tmpval']['geoOption'] == self::GEOMETRY_DISP_GEOM) {
            $geometry_text = $this->_handleNonPrintableContents(
                strtoupper(self::GEOMETRY_FIELD),
                $column,
                $transformation_plugin,
                $transform_options,
                $default_function,
                $meta,
                $_url_params
            );

            $cell = $this->_buildValueDisplay(
                $class,
                $condition_field,
                $geometry_text
            );
            return $cell;
        }

        if ($_SESSION['tmpval']['geoOption'] == self::GEOMETRY_DISP_WKT) {
            // Prepare in Well Known Text(WKT) format.
            $where_comparison = ' = ' . $column;

            // Convert to WKT format
            $wktval = Util::asWKT($column);
            list(
                $is_field_truncated,
                $displayedColumn,
                // skip 3rd param
            ) = $this->_getPartialText($wktval);

            $cell = $this->_getRowData(
                $class,
                $condition_field,
                $analyzed_sql_results,
                $meta,
                $map,
                $wktval,
                $displayedColumn,
                $transformation_plugin,
                $default_function,
                '',
                $where_comparison,
                $transform_options,
                $is_field_truncated,
                ''
            );
            return $cell;
        }

        // Prepare in  Well Known Binary (WKB) format.

        if ($_SESSION['tmpval']['display_binary']) {
            $where_comparison = ' = ' . $column;

            $wkbval = substr(bin2hex($column), 8);
            list(
                $is_field_truncated,
                $displayedColumn,
                // skip 3rd param
            ) = $this->_getPartialText($wkbval);

            $cell = $this->_getRowData(
                $class,
                $condition_field,
                $analyzed_sql_results,
                $meta,
                $map,
                $wkbval,
                $displayedColumn,
                $transformation_plugin,
                $default_function,
                '',
                $where_comparison,
                $transform_options,
                $is_field_truncated,
                ''
            );
            return $cell;
        }

        $wkbval = $this->_handleNonPrintableContents(
            self::BINARY_FIELD,
            $column,
            $transformation_plugin,
            $transform_options,
            $default_function,
            $meta,
            $_url_params
        );

        $cell = $this->_buildValueDisplay(
            $class,
            $condition_field,
            $wkbval
        );

        return $cell;
    }

    /**
     * Get data cell for non numeric type fields
     *
     * @param string|null           $column                the relevant column in data row
     * @param string                $class                 the html class for column
     * @param stdClass              $meta                  the meta-information about
     *                                                     the field
     * @param array                 $map                   the list of relations
     * @param array                 $_url_params           the parameters for generate
     *                                                     url
     * @param boolean               $condition_field       the column should highlighted
     *                                                     or not
     * @param TransformationsPlugin $transformation_plugin the name of transformation
     *                                                     function
     * @param string                $default_function      the default transformation
     *                                                     function
     * @param string                $transform_options     the transformation parameters
     * @param boolean               $is_field_truncated    is data truncated due to
     *                                                     LimitChars
     * @param array                 $analyzed_sql_results  the analyzed query
     * @param integer               $dt_result             the link id associated to
     *                                                     the query which results
     *                                                     have to be displayed
     * @param integer               $col_index             the column index
     *
     * @return  string the prepared data cell, html content
     *
     * @access  private
     *
     * @see     _getTableBody()
     */
    private function _getDataCellForNonNumericColumns(
        ?string $column,
        $class,
        $meta,
        array $map,
        array $_url_params,
        $condition_field,
        $transformation_plugin,
        $default_function,
        $transform_options,
        $is_field_truncated,
        array $analyzed_sql_results,
        &$dt_result,
        $col_index
    ) {
        $original_length = 0;

        $is_analyse = $this->__get('is_analyse');
        $field_flags = $GLOBALS['dbi']->fieldFlags($dt_result, $col_index);

        $bIsText = gettype($transformation_plugin) === 'object'
            && strpos($transformation_plugin->getMIMEType(), 'Text')
            === false;

        // disable inline grid editing
        // if binary fields are protected
        // or transformation plugin is of non text type
        // such as image
        if ((false !== stripos($field_flags, self::BINARY_FIELD)
            && ($GLOBALS['cfg']['ProtectBinary'] === 'all'
            || ($GLOBALS['cfg']['ProtectBinary'] === 'noblob'
            && false === stripos($meta->type, self::BLOB_FIELD))
            || ($GLOBALS['cfg']['ProtectBinary'] === 'blob'
            && false !== stripos($meta->type, self::BLOB_FIELD))))
            || $bIsText
        ) {
            $class = str_replace('grid_edit', '', $class);
        }

        if (! isset($column) || $column === null) {
            $cell = $this->_buildNullDisplay($class, $condition_field, $meta);
            return $cell;
        }

        if ($column == '') {
            $cell = $this->_buildEmptyDisplay($class, $condition_field, $meta);
            return $cell;
        }

        // Cut all fields to $GLOBALS['cfg']['LimitChars']
        // (unless it's a link-type transformation or binary)
        $displayedColumn = $column;
        if (! (gettype($transformation_plugin) === "object"
            && strpos($transformation_plugin->getName(), 'Link') !== false)
            && false === stripos($field_flags, self::BINARY_FIELD)
        ) {
            list(
                $is_field_truncated,
                $column,
                $original_length
            ) = $this->_getPartialText($column);
        }

        $formatted = false;
        if (isset($meta->_type) && $meta->_type === MYSQLI_TYPE_BIT) {
            $displayedColumn = Util::printableBitValue(
                (int) $displayedColumn,
                (int) $meta->length
            );

            // some results of PROCEDURE ANALYSE() are reported as
            // being BINARY but they are quite readable,
            // so don't treat them as BINARY
        } elseif (false !== stripos($field_flags, self::BINARY_FIELD)
            && ! (isset($is_analyse) && $is_analyse)
        ) {
            // we show the BINARY or BLOB message and field's size
            // (or maybe use a transformation)
            $binary_or_blob = self::BLOB_FIELD;
            if ($meta->type === self::STRING_FIELD) {
                $binary_or_blob = self::BINARY_FIELD;
            }
            $displayedColumn = $this->_handleNonPrintableContents(
                $binary_or_blob,
                $displayedColumn,
                $transformation_plugin,
                $transform_options,
                $default_function,
                $meta,
                $_url_params,
                $is_field_truncated
            );
            $class = $this->_addClass(
                $class,
                $condition_field,
                $meta,
                '',
                $is_field_truncated,
                $transformation_plugin,
                $default_function
            );
            $result = strip_tags($column);
            // disable inline grid editing
            // if binary or blob data is not shown
            if (false !== stripos($result, $binary_or_blob)) {
                $class = str_replace('grid_edit', '', $class);
            }
            $formatted = true;
        }

        if ($formatted) {
            $cell = $this->_buildValueDisplay(
                $class,
                $condition_field,
                $displayedColumn
            );
            return $cell;
        }

        // transform functions may enable no-wrapping:
        $function_nowrap = 'applyTransformationNoWrap';

        $bool_nowrap = ($default_function != $transformation_plugin)
            && function_exists((string) $transformation_plugin->$function_nowrap())
            ? $transformation_plugin->$function_nowrap($transform_options)
            : false;

        // do not wrap if date field type
        $nowrap = preg_match('@DATE|TIME@i', $meta->type)
            || $bool_nowrap ? ' nowrap' : '';

        $where_comparison = ' = \''
            . $GLOBALS['dbi']->escapeString($column)
            . '\'';

        $cell = $this->_getRowData(
            $class,
            $condition_field,
            $analyzed_sql_results,
            $meta,
            $map,
            $column,
            $displayedColumn,
            $transformation_plugin,
            $default_function,
            $nowrap,
            $where_comparison,
            $transform_options,
            $is_field_truncated,
            $original_length
        );

        return $cell;
    }

    /**
     * Checks the posted options for viewing query results
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
        $query = [];
        if (isset($_SESSION['tmpval']['query'][$sql_md5])) {
            $query = $_SESSION['tmpval']['query'][$sql_md5];
        }

        $query['sql'] = $this->__get('sql_query');

        if (empty($query['repeat_cells'])) {
            $query['repeat_cells'] = $GLOBALS['cfg']['RepeatCells'];
        }

        // The value can also be from _GET as described on issue #16146 when sorting results
        $sessionMaxRows = $_GET['session_max_rows'] ?? $_POST['session_max_rows'] ?? '';

        // as this is a form value, the type is always string so we cannot
        // use Core::isValid($_POST['session_max_rows'], 'integer')
        if (Core::isValid($sessionMaxRows, 'numeric')) {
            $query['max_rows'] = (int) $sessionMaxRows;
            unset($_GET['session_max_rows'], $_POST['session_max_rows']);
        } elseif ($sessionMaxRows === self::ALL_ROWS) {
            $query['max_rows'] = self::ALL_ROWS;
            unset($_GET['session_max_rows'], $_POST['session_max_rows']);
        } elseif (empty($query['max_rows'])) {
            $query['max_rows'] = intval($GLOBALS['cfg']['MaxRows']);
        }

        if (Core::isValid($_REQUEST['pos'], 'numeric')) {
            $query['pos'] = $_REQUEST['pos'];
            unset($_REQUEST['pos']);
        } elseif (empty($query['pos'])) {
            $query['pos'] = 0;
        }

        if (Core::isValid(
            $_REQUEST['pftext'],
            [
                self::DISPLAY_PARTIAL_TEXT,
                self::DISPLAY_FULL_TEXT,
            ]
        )
        ) {
            $query['pftext'] = $_REQUEST['pftext'];
            unset($_REQUEST['pftext']);
        } elseif (empty($query['pftext'])) {
            $query['pftext'] = self::DISPLAY_PARTIAL_TEXT;
        }

        if (Core::isValid(
            $_REQUEST['relational_display'],
            [
                self::RELATIONAL_KEY,
                self::RELATIONAL_DISPLAY_COLUMN,
            ]
        )
        ) {
            $query['relational_display'] = $_REQUEST['relational_display'];
            unset($_REQUEST['relational_display']);
        } elseif (empty($query['relational_display'])) {
            // The current session value has priority over a
            // change via Settings; this change will be apparent
            // starting from the next session
            $query['relational_display'] = $GLOBALS['cfg']['RelationalDisplay'];
        }

        if (Core::isValid(
            $_REQUEST['geoOption'],
            [
                self::GEOMETRY_DISP_WKT,
                self::GEOMETRY_DISP_WKB,
                self::GEOMETRY_DISP_GEOM,
            ]
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
        } elseif (! isset($_REQUEST['full_text_button'])) {
            // selected by default because some operations like OPTIMIZE TABLE
            // and all queries involving functions return "binary" contents,
            // according to low-level field flags
            $query['display_binary'] = true;
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
    }

    /**
     * Prepare a table of results returned by a SQL query.
     *
     * @param integer $dt_result            the link id associated to the query
     *                                      which results have to be displayed
     * @param array   $displayParts         the parts to display
     * @param array   $analyzed_sql_results analyzed sql results
     * @param boolean $is_limited_display   With limited operations or not
     *
     * @return  string   Generated HTML content for resulted table
     *
     * @access  public
     *
     * @see     sql.php file
     */
    public function getTable(
        &$dt_result,
        array &$displayParts,
        array $analyzed_sql_results,
        $is_limited_display = false
    ) {
        /**
         * The statement this table is built for.
         * @var SelectStatement
         */
        if (isset($analyzed_sql_results['statement'])) {
            $statement = $analyzed_sql_results['statement'];
        } else {
            $statement = null;
        }

        // Following variable are needed for use in isset/empty or
        // use with array indexes/safe use in foreach
        $fields_meta = $this->__get('fields_meta');
        $showtable = $this->__get('showtable');
        $printview = $this->__get('printview');

        /**
         * @todo move this to a central place
         * @todo for other future table types
         */
        $is_innodb = (isset($showtable['Type'])
            && $showtable['Type'] == self::TABLE_TYPE_INNO_DB);

        $sql = new Sql();
        if ($is_innodb && $sql->isJustBrowsing($analyzed_sql_results, true)) {
            $pre_count = '~';
            $after_count = Util::showHint(
                Sanitize::sanitizeMessage(
                    __('May be approximate. See [doc@faq3-11]FAQ 3.11[/doc].')
                )
            );
        } else {
            $pre_count = '';
            $after_count = '';
        }

        // 1. ----- Prepares the work -----

        // 1.1 Gets the information about which functionalities should be
        //     displayed

        list(
            $displayParts,
            $total
        )  = $this->_setDisplayPartsAndTotal($displayParts);

        // 1.2 Defines offsets for the next and previous pages
        if ($displayParts['nav_bar'] == '1') {
            list($pos_next, $pos_prev) = $this->_getOffsets();
        } // end if

        // 1.3 Extract sorting expressions.
        //     we need $sort_expression and $sort_expression_nodirection
        //     even if there are many table references
        $sort_expression = [];
        $sort_expression_nodirection = [];
        $sort_direction = [];

        if ($statement !== null && ! empty($statement->order)) {
            foreach ($statement->order as $o) {
                $sort_expression[] = $o->expr->expr . ' ' . $o->type;
                $sort_expression_nodirection[] = $o->expr->expr;
                $sort_direction[] = $o->type;
            }
        } else {
            $sort_expression[] = '';
            $sort_expression_nodirection[] = '';
            $sort_direction[] = '';
        }

        $number_of_columns = count($sort_expression_nodirection);

        // 1.4 Prepares display of first and last value of the sorted column
        $sorted_column_message = '';
        for ($i = 0; $i < $number_of_columns; $i++) {
            $sorted_column_message .= $this->_getSortedColumnMessage(
                $dt_result,
                $sort_expression_nodirection[$i]
            );
        }

        // 2. ----- Prepare to display the top of the page -----

        // 2.1 Prepares a messages with position information
        $sqlQueryMessage = '';
        if (($displayParts['nav_bar'] == '1') && isset($pos_next)) {
            $message = $this->_setMessageInformation(
                $sorted_column_message,
                $analyzed_sql_results,
                $total,
                $pos_next,
                $pre_count,
                $after_count
            );

            $sqlQueryMessage = Util::getMessage(
                $message,
                $this->__get('sql_query'),
                'success'
            );
        } elseif ((! isset($printview) || ($printview != '1')) && ! $is_limited_display) {
            $sqlQueryMessage = Util::getMessage(
                __('Your SQL query has been executed successfully.'),
                $this->__get('sql_query'),
                'success'
            );
        }

        // 2.3 Prepare the navigation bars
        if (strlen($this->__get('table')) === 0) {
            if ($analyzed_sql_results['querytype'] == 'SELECT') {
                // table does not always contain a real table name,
                // for example in MySQL 5.0.x, the query SHOW STATUS
                // returns STATUS as a table name
                $this->__set('table', $fields_meta[0]->table);
            } else {
                $this->__set('table', '');
            }
        }

        // can the result be sorted?
        if ($displayParts['sort_lnk'] == '1' && $analyzed_sql_results['statement'] !== null) {
            // At this point, $sort_expression is an array
            list($unsorted_sql_query, $sort_by_key_html)
                = $this->_getUnsortedSqlAndSortByKeyDropDown(
                    $analyzed_sql_results,
                    $sort_expression
                );
        } else {
            $sort_by_key_html = $unsorted_sql_query = '';
        }

        $navigation = '';
        if ($displayParts['nav_bar'] == '1' && $statement !== null && empty($statement->limit)) {
            $navigation = $this->_getTableNavigation(
                $pos_next,
                $pos_prev,
                $is_innodb,
                $sort_by_key_html
            );
        }

        // 2b ----- Get field references from Database -----
        // (see the 'relation' configuration variable)

        // initialize map
        $map = [];

        if (strlen($this->__get('table')) > 0) {
            // This method set the values for $map array
            $this->_setParamForLinkForeignKeyRelatedTables($map);

            // Coming from 'Distinct values' action of structure page
            // We manipulate relations mechanism to show a link to related rows.
            if ($this->__get('is_browse_distinct')) {
                $map[$fields_meta[1]->name] = [
                    $this->__get('table'),
                    $fields_meta[1]->name,
                    '',
                    $this->__get('db'),
                ];
            }
        } // end if
        // end 2b

        // 3. ----- Prepare the results table -----
        $headers = $this->_getTableHeaders(
            $displayParts,
            $analyzed_sql_results,
            $unsorted_sql_query,
            $sort_expression,
            $sort_expression_nodirection,
            $sort_direction,
            $is_limited_display
        );

        $body = $this->_getTableBody(
            $dt_result,
            $displayParts,
            $map,
            $analyzed_sql_results,
            $is_limited_display
        );

        $this->__set('display_params', null);

        // 4. ----- Prepares the link for multi-fields edit and delete
        $multiRowOperationLinks = '';
        if ($displayParts['del_lnk'] == self::DELETE_ROW
            && $displayParts['del_lnk'] != self::KILL_PROCESS
        ) {
            $multiRowOperationLinks = $this->_getMultiRowOperationLinks(
                $dt_result,
                $analyzed_sql_results,
                $displayParts['del_lnk']
            );
        }

        // 5. ----- Prepare "Query results operations"
        $operations = '';
        if ((! isset($printview) || ($printview != '1')) && ! $is_limited_display) {
            $operations = $this->_getResultsOperations(
                $displayParts,
                $analyzed_sql_results
            );
        }

        return $this->template->render('display/results/table', [
            'sql_query_message' => $sqlQueryMessage,
            'navigation' => $navigation,
            'headers' => $headers,
            'body' => $body,
            'multi_row_operation_links' => $multiRowOperationLinks,
            'operations' => $operations,
        ]);
    }

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

        return [
            $pos_next,
            $pos_prev,
        ];
    }

    /**
     * Prepare sorted column message
     *
     * @param integer $dt_result                   the link id associated to the
     *                                             query which results have to
     *                                             be displayed
     * @param string  $sort_expression_nodirection sort expression without direction
     *
     * @return string|null html content, null if not found sorted column
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _getSortedColumnMessage(
        &$dt_result,
        $sort_expression_nodirection
    ) {
        $fields_meta = $this->__get('fields_meta'); // To use array indexes

        if (empty($sort_expression_nodirection)) {
            return null;
        }

        if (mb_strpos($sort_expression_nodirection, '.') === false) {
            $sort_table = $this->__get('table');
            $sort_column = $sort_expression_nodirection;
        } else {
            list($sort_table, $sort_column)
                = explode('.', $sort_expression_nodirection);
        }

        $sort_table = Util::unQuote($sort_table);
        $sort_column = Util::unQuote($sort_column);

        // find the sorted column index in row result
        // (this might be a multi-table query)
        $sorted_column_index = false;

        foreach ($fields_meta as $key => $meta) {
            if (($meta->table == $sort_table) && ($meta->name == $sort_column)) {
                $sorted_column_index = $key;
                break;
            }
        }

        if ($sorted_column_index === false) {
            return null;
        }

        // fetch first row of the result set
        $row = $GLOBALS['dbi']->fetchRow($dt_result);

        // initializing default arguments
        $default_function = [
            Core::class,
            'mimeDefaultFunction',
        ];
        $transformation_plugin = $default_function;
        $transform_options = [];

        // check for non printable sorted row data
        $meta = $fields_meta[$sorted_column_index];

        if (false !== stripos($meta->type, self::BLOB_FIELD)
            || ($meta->type == self::GEOMETRY_FIELD)
        ) {
            $column_for_first_row = $this->_handleNonPrintableContents(
                $meta->type,
                $row[$sorted_column_index],
                $transformation_plugin,
                $transform_options,
                $default_function,
                $meta
            );
        } else {
            $column_for_first_row = ($row !== null) ? $row[$sorted_column_index] : '';
        }

        $column_for_first_row = mb_strtoupper(
            mb_substr(
                (string) $column_for_first_row,
                0,
                (int) $GLOBALS['cfg']['LimitChars']
            ) . '...'
        );

        // fetch last row of the result set
        $GLOBALS['dbi']->dataSeek($dt_result, $this->__get('num_rows') - 1);
        $row = $GLOBALS['dbi']->fetchRow($dt_result);

        // check for non printable sorted row data
        $meta = $fields_meta[$sorted_column_index];
        if (false !== stripos($meta->type, self::BLOB_FIELD)
            || ($meta->type == self::GEOMETRY_FIELD)
        ) {
            $column_for_last_row = $this->_handleNonPrintableContents(
                $meta->type,
                $row[$sorted_column_index],
                $transformation_plugin,
                $transform_options,
                $default_function,
                $meta
            );
        } else {
            $column_for_last_row = ($row !== null) ? $row[$sorted_column_index] : '';
        }

        $column_for_last_row = mb_strtoupper(
            mb_substr(
                (string) $column_for_last_row,
                0,
                (int) $GLOBALS['cfg']['LimitChars']
            ) . '...'
        );

        // reset to first row for the loop in _getTableBody()
        $GLOBALS['dbi']->dataSeek($dt_result, 0);

        // we could also use here $sort_expression_nodirection
        return ' [' . htmlspecialchars($sort_column)
            . ': <strong>' . htmlspecialchars($column_for_first_row) . ' - '
            . htmlspecialchars($column_for_last_row) . '</strong>]';
    }

    /**
     * Set the content that needs to be shown in message
     *
     * @param string  $sorted_column_message the message for sorted column
     * @param array   $analyzed_sql_results  the analyzed query
     * @param integer $total                 the total number of rows returned by
     *                                       the SQL query without any
     *                                       programmatically appended LIMIT clause
     * @param integer $pos_next              the offset for next page
     * @param string  $pre_count             the string renders before row count
     * @param string  $after_count           the string renders after row count
     *
     * @return Message an object of Message
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _setMessageInformation(
        $sorted_column_message,
        array $analyzed_sql_results,
        $total,
        $pos_next,
        $pre_count,
        $after_count
    ) {

        $unlim_num_rows = $this->__get('unlim_num_rows'); // To use in isset()

        if (! empty($analyzed_sql_results['statement']->limit)) {
            $first_shown_rec = $analyzed_sql_results['statement']->limit->offset;
            $row_count = $analyzed_sql_results['statement']->limit->rowCount;

            if ($row_count < $total) {
                $last_shown_rec = $first_shown_rec + $row_count - 1;
            } else {
                $last_shown_rec = $first_shown_rec + $total - 1;
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

        $table = new Table($this->__get('table'), $this->__get('db'));
        if ($table->isView()
            && ($total == $GLOBALS['cfg']['MaxExactCountViews'])
        ) {
            $message = Message::notice(
                __(
                    'This view has at least this number of rows. '
                    . 'Please refer to %sdocumentation%s.'
                )
            );

            $message->addParam('[doc@cfg_MaxExactCount]');
            $message->addParam('[/doc]');
            $message_view_warning = Util::showHint($message);
        } else {
            $message_view_warning = false;
        }

        $message = Message::success(__('Showing rows %1s - %2s'));
        $message->addParam($first_shown_rec);

        if ($message_view_warning !== false) {
            $message->addParamHtml('... ' . $message_view_warning);
        } else {
            $message->addParam($last_shown_rec);
        }

        $message->addText('(');

        if ($message_view_warning === false) {
            if (isset($unlim_num_rows) && ($unlim_num_rows != $total)) {
                $message_total = Message::notice(
                    $pre_count . __('%1$d total, %2$d in query')
                );
                $message_total->addParam($total);
                $message_total->addParam($unlim_num_rows);
            } else {
                $message_total = Message::notice($pre_count . __('%d total'));
                $message_total->addParam($total);
            }

            if (! empty($after_count)) {
                $message_total->addHtml($after_count);
            }
            $message->addMessage($message_total, '');

            $message->addText(', ', '');
        }

        $message_qt = Message::notice(__('Query took %01.4f seconds.') . ')');
        $message_qt->addParam($this->__get('querytime'));

        $message->addMessage($message_qt, '');
        if ($sorted_column_message !== null) {
            $message->addHtml($sorted_column_message, '');
        }

        return $message;
    }

    /**
     * Set the value of $map array for linking foreign key related tables
     *
     * @param array $map the list of relations
     *
     * @return  void
     *
     * @access  private
     *
     * @see      getTable()
     */
    private function _setParamForLinkForeignKeyRelatedTables(array &$map)
    {
        // To be able to later display a link to the related table,
        // we verify both types of relations: either those that are
        // native foreign keys or those defined in the phpMyAdmin
        // configuration storage. If no PMA storage, we won't be able
        // to use the "column to display" notion (for example show
        // the name related to a numeric id).
        $exist_rel = $this->relation->getForeigners(
            $this->__get('db'),
            $this->__get('table'),
            '',
            self::POSITION_BOTH
        );

        if (! empty($exist_rel)) {
            foreach ($exist_rel as $master_field => $rel) {
                if ($master_field != 'foreign_keys_data') {
                    $display_field = $this->relation->getDisplayField(
                        $rel['foreign_db'],
                        $rel['foreign_table']
                    );
                    $map[$master_field] = [
                        $rel['foreign_table'],
                        $rel['foreign_field'],
                        $display_field,
                        $rel['foreign_db'],
                    ];
                } else {
                    foreach ($rel as $key => $one_key) {
                        foreach ($one_key['index_list'] as $index => $one_field) {
                            $display_field = $this->relation->getDisplayField(
                                isset($one_key['ref_db_name'])
                                ? $one_key['ref_db_name']
                                : $GLOBALS['db'],
                                $one_key['ref_table_name']
                            );

                            $map[$one_field] = [
                                $one_key['ref_table_name'],
                                $one_key['ref_index_list'][$index],
                                $display_field,
                                isset($one_key['ref_db_name'])
                                ? $one_key['ref_db_name']
                                : $GLOBALS['db'],
                            ];
                        }
                    }
                }
            }
        }
    }

    /**
     * Prepare multi field edit/delete links
     *
     * @param integer $dt_result            the link id associated to the query which
     *                                      results have to be displayed
     * @param array   $analyzed_sql_results analyzed sql results
     * @param string  $del_link             the display element - 'del_link'
     *
     * @return string html content
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _getMultiRowOperationLinks(
        &$dt_result,
        array $analyzed_sql_results,
        $del_link
    ) {
        $links_html = '<div class="print_ignore" >';
        $url_query = $this->__get('url_query');
        $delete_text = $del_link == self::DELETE_ROW ? __('Delete') : __('Kill');

        $links_html .= $this->template->render('select_all', [
            'pma_theme_image' => $this->__get('pma_theme_image'),
            'text_dir' => $this->__get('text_dir'),
            'form_name' => 'resultsForm_' . $this->__get('unique_id'),
        ]);

        $links_html .= Util::getButtonOrImage(
            'submit_mult',
            'mult_submit',
            __('Edit'),
            'b_edit',
            'edit'
        );

        $links_html .= Util::getButtonOrImage(
            'submit_mult',
            'mult_submit',
            __('Copy'),
            'b_insrow',
            'copy'
        );

        $links_html .= Util::getButtonOrImage(
            'submit_mult',
            'mult_submit',
            $delete_text,
            'b_drop',
            'delete'
        );

        if ($analyzed_sql_results['querytype'] == 'SELECT') {
            $links_html .= Util::getButtonOrImage(
                'submit_mult',
                'mult_submit',
                __('Export'),
                'b_tblexport',
                'export'
            );
        }

        $links_html .= "</div>\n";

        $links_html .= '<input type="hidden" name="sql_query"'
            . ' value="' . htmlspecialchars($this->__get('sql_query')) . '">'
            . "\n";

        if (! empty($url_query)) {
            $links_html .= '<input type="hidden" name="url_query"'
                . ' value="' . $url_query . '">' . "\n";
        }

        // fetch last row of the result set
        $GLOBALS['dbi']->dataSeek($dt_result, $this->__get('num_rows') - 1);
        $row = $GLOBALS['dbi']->fetchRow($dt_result);

        // @see DbiMysqi::fetchRow & DatabaseInterface::fetchRow
        if (! is_array($row)) {
            $row = [];
        }

        // $clause_is_unique is needed by getTable() to generate the proper param
        // in the multi-edit and multi-delete form
        list($where_clause, $clause_is_unique, $condition_array)
            = Util::getUniqueCondition(
                $dt_result, // handle
                $this->__get('fields_cnt'), // fields_cnt
                $this->__get('fields_meta'), // fields_meta
                $row, // row
                false, // force_unique
                false, // restrict_to_table
                $analyzed_sql_results // analyzed_sql_results
            );
        unset($where_clause, $condition_array);

        // reset to first row for the loop in _getTableBody()
        $GLOBALS['dbi']->dataSeek($dt_result, 0);

        $links_html .= '<input type="hidden" name="clause_is_unique"'
            . ' value="' . $clause_is_unique . '">' . "\n";

        $links_html .= '</form>' . "\n";

        return $links_html;
    }

    /**
     * Generates HTML to display the Create view in span tag
     *
     * @param array  $analyzed_sql_results analyzed sql results
     * @param string $url_query            String with URL Parameters
     *
     * @return string
     *
     * @access private
     *
     * @see _getResultsOperations()
     */
    private function _getLinkForCreateView(array $analyzed_sql_results, $url_query)
    {
        $results_operations_html = '';
        if (empty($analyzed_sql_results['procedure'])) {
            $results_operations_html .= '<span>'
                . Util::linkOrButton(
                    'view_create.php' . $url_query,
                    Util::getIcon(
                        'b_view_add',
                        __('Create view'),
                        true
                    ),
                    ['class' => 'create_view ajax btn']
                )
                . '</span>' . "\n";
        }
        return $results_operations_html;
    }

    /**
     * Calls the _getResultsOperations with $only_view as true
     *
     * @param array $analyzed_sql_results analyzed sql results
     *
     * @return string
     *
     * @access public
     *
     */
    public function getCreateViewQueryResultOp(array $analyzed_sql_results)
    {
        $results_operations_html = '';
        //calling to _getResultOperations with a fake $displayParts
        //and setting only_view parameter to be true to generate just view
        $results_operations_html .= $this->_getResultsOperations(
            [],
            $analyzed_sql_results,
            true
        );
        return $results_operations_html;
    }

    /**
     * Get copy to clipboard links for results operations
     *
     * @return string
     *
     * @access  private
     */
    private function _getCopytoclipboardLinks()
    {
        return Util::linkOrButton(
            '#',
            Util::getIcon(
                'b_insrow',
                __('Copy to clipboard'),
                true
            ),
            [
                'id' => 'copyToClipBoard' ,
                'class' => 'btn',
            ]
        );
    }

    /**
     * Get printview links for results operations
     *
     * @return string
     *
     * @access  private
     */
    private function _getPrintviewLinks()
    {
        return Util::linkOrButton(
            '#',
            Util::getIcon(
                'b_print',
                __('Print'),
                true
            ),
            [
                'id' => 'printView' ,
                'class' => 'btn',
            ],
            'print_view'
        );
    }

    /**
     * Get operations that are available on results.
     *
     * @param array   $displayParts         the parts to display
     * @param array   $analyzed_sql_results analyzed sql results
     * @param boolean $only_view            Whether to show only view
     *
     * @return string  html content
     *
     * @access  private
     *
     * @see     getTable()
     */
    private function _getResultsOperations(
        array $displayParts,
        array $analyzed_sql_results,
        $only_view = false
    ) {
        global $printview;

        $results_operations_html = '';
        $fields_meta = $this->__get('fields_meta'); // To safe use in foreach
        $header_shown = false;
        $header = '<fieldset class="print_ignore" ><legend>'
            . __('Query results operations') . '</legend>';

        $_url_params = [
            'db'        => $this->__get('db'),
            'table'     => $this->__get('table'),
            'printview' => '1',
            'sql_query' => $this->__get('sql_query'),
        ];
        $url_query = Url::getCommon($_url_params);

        if (! $header_shown) {
            $results_operations_html .= $header;
            $header_shown = true;
        }
        // if empty result set was produced we need to
        // show only view and not other options
        if ($only_view) {
            $results_operations_html .= $this->_getLinkForCreateView(
                $analyzed_sql_results,
                $url_query
            );

            if ($header_shown) {
                $results_operations_html .= '</fieldset><br>';
            }
            return $results_operations_html;
        }

        // Displays "printable view" link if required
        if ($displayParts['pview_lnk'] == '1') {
            $results_operations_html .= $this->_getPrintviewLinks();
            $results_operations_html .= $this->_getCopytoclipboardLinks();
        } // end displays "printable view"

        // Export link
        // (the url_query has extra parameters that won't be used to export)
        // (the single_table parameter is used in \PhpMyAdmin\Export->getDisplay()
        //  to hide the SQL and the structure export dialogs)
        // If the parser found a PROCEDURE clause
        // (most probably PROCEDURE ANALYSE()) it makes no sense to
        // display the Export link).
        if (($analyzed_sql_results['querytype'] == self::QUERY_TYPE_SELECT)
            && ! isset($printview)
            && empty($analyzed_sql_results['procedure'])
        ) {
            if (count($analyzed_sql_results['select_tables']) === 1) {
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

            $results_operations_html .= Util::linkOrButton(
                'tbl_export.php' . Url::getCommon($_url_params),
                Util::getIcon(
                    'b_tblexport',
                    __('Export'),
                    true
                ),
                ['class' => 'btn']
            );

            // prepare chart
            $results_operations_html .= Util::linkOrButton(
                'tbl_chart.php' . Url::getCommon($_url_params),
                Util::getIcon(
                    'b_chart',
                    __('Display chart'),
                    true
                ),
                ['class' => 'btn']
            );

            // prepare GIS chart
            $geometry_found = false;
            // If at least one geometry field is found
            foreach ($fields_meta as $meta) {
                if ($meta->type == self::GEOMETRY_FIELD) {
                    $geometry_found = true;
                    break;
                }
            }

            if ($geometry_found) {
                $results_operations_html
                    .= Util::linkOrButton(
                        'tbl_gis_visualization.php'
                        . Url::getCommon($_url_params),
                        Util::getIcon(
                            'b_globe',
                            __('Visualize GIS data'),
                            true
                        ),
                        ['class' => 'btn']
                    );
            }
        }

        // CREATE VIEW
        /**
         *
         * @todo detect privileges to create a view
         *       (but see 2006-01-19 note in PhpMyAdmin\Display\CreateTable,
         *        I think we cannot detect db-specific privileges reliably)
         * Note: we don't display a Create view link if we found a PROCEDURE clause
         */
        if (! $header_shown) {
            $results_operations_html .= $header;
            $header_shown = true;
        }

        $results_operations_html .= $this->_getLinkForCreateView(
            $analyzed_sql_results,
            $url_query
        );

        if ($header_shown) {
            $results_operations_html .= '</fieldset><br>';
        }

        return $results_operations_html;
    }

    /**
     * Verifies what to do with non-printable contents (binary or BLOB)
     * in Browse mode.
     *
     * @param string      $category              BLOB|BINARY|GEOMETRY
     * @param string|null $content               the binary content
     * @param mixed       $transformation_plugin transformation plugin.
     *                                           Can also be the
     *                                           default function:
     *                                           Core::mimeDefaultFunction
     * @param string      $transform_options     transformation parameters
     * @param string      $default_function      default transformation function
     * @param stdClass    $meta                  the meta-information about the field
     * @param array       $url_params            parameters that should go to the
     *                                           download link
     * @param boolean     $is_truncated          the result is truncated or not
     *
     * @return mixed  string or float
     *
     * @access  private
     *
     * @see     _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericColumns(),
     *          _getSortedColumnMessage()
     */
    private function _handleNonPrintableContents(
        $category,
        ?string $content,
        $transformation_plugin,
        $transform_options,
        $default_function,
        $meta,
        array $url_params = [],
        &$is_truncated = null
    ) {
        $is_truncated = false;
        $result = '[' . $category;

        if ($content !== null) {
            $size = strlen($content);
            $display_size = Util::formatByteDown($size, 3, 1);
            $result .= ' - ' . $display_size[0] . ' ' . $display_size[1];
        } else {
            $result .= ' - NULL';
            $size = 0;
            $content = '';
        }

        $result .= ']';

        // if we want to use a text transformation on a BLOB column
        if (gettype($transformation_plugin) === "object") {
            $posMimeOctetstream = strpos(
                $transformation_plugin->getMIMESubtype(),
                'Octetstream'
            );
            $posMimeText = strpos($transformation_plugin->getMIMEtype(), 'Text');
            if ($posMimeOctetstream
                || $posMimeText !== false
            ) {
                // Applying Transformations on hex string of binary data
                // seems more appropriate
                $result = pack("H*", bin2hex($content));
            }
        }

        if ($size <= 0) {
            return $result;
        }

        if ($default_function != $transformation_plugin) {
            $result = $transformation_plugin->applyTransformation(
                $result,
                $transform_options,
                $meta
            );
            return $result;
        }

        $result = $default_function($result, [], $meta);
        if (($_SESSION['tmpval']['display_binary']
            && $meta->type === self::STRING_FIELD)
            || ($_SESSION['tmpval']['display_blob']
            && false !== stripos($meta->type, self::BLOB_FIELD))
        ) {
            // in this case, restart from the original $content
            if (mb_check_encoding($content, 'utf-8')
                && ! preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', $content)
            ) {
                // show as text if it's valid utf-8
                $result = htmlspecialchars($content);
            } else {
                $result = '0x' . bin2hex($content);
            }
            list(
                $is_truncated,
                $result,
                // skip 3rd param
            ) = $this->_getPartialText($result);
        }

        /* Create link to download */

        // in PHP < 5.5, empty() only checks variables
        $tmpdb = $this->__get('db');
        if (count($url_params) > 0
            && (! empty($tmpdb) && ! empty($meta->orgtable))
        ) {
            $url_params['where_clause_sign'] = Core::signSqlQuery($url_params['where_clause']);

            $result = '<a href="tbl_get_field.php'
                . Url::getCommon($url_params)
                . '" class="disableAjax">'
                . $result . '</a>';
        }

        return $result;
    }

    /**
     * Retrieves the associated foreign key info for a data cell
     *
     * @param array    $map              the list of relations
     * @param stdClass $meta             the meta-information about the field
     * @param string   $where_comparison data for the where clause
     *
     * @return string|null  formatted data
     *
     * @access  private
     *
     */
    private function _getFromForeign(array $map, $meta, $where_comparison)
    {
        $dispsql = 'SELECT '
            . Util::backquote($map[$meta->name][2])
            . ' FROM '
            . Util::backquote($map[$meta->name][3])
            . '.'
            . Util::backquote($map[$meta->name][0])
            . ' WHERE '
            . Util::backquote($map[$meta->name][1])
            . $where_comparison;

        $dispresult = $GLOBALS['dbi']->tryQuery(
            $dispsql,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );

        if ($dispresult && $GLOBALS['dbi']->numRows($dispresult) > 0) {
            list($dispval) = $GLOBALS['dbi']->fetchRow($dispresult, 0);
        } else {
            $dispval = __('Link not found!');
        }

        $GLOBALS['dbi']->freeResult($dispresult);

        return $dispval;
    }

    /**
     * Prepares the displayable content of a data cell in Browse mode,
     * taking into account foreign key description field and transformations
     *
     * @param string                $class                 css classes for the td element
     * @param bool                  $condition_field       whether the column is a part of
     *                                                     the where clause
     * @param array                 $analyzed_sql_results  the analyzed query
     * @param stdClass              $meta                  the meta-information about the
     *                                                     field
     * @param array                 $map                   the list of relations
     * @param string                $data                  data
     * @param string                $displayedData         data that will be displayed (maybe be chunked)
     * @param TransformationsPlugin $transformation_plugin transformation plugin.
     *                                                     Can also be the default function:
     *                                                     Core::mimeDefaultFunction
     * @param string                $default_function      default function
     * @param string                $nowrap                'nowrap' if the content should
     *                                                     not be wrapped
     * @param string                $where_comparison      data for the where clause
     * @param array                 $transform_options     options for transformation
     * @param bool                  $is_field_truncated    whether the field is truncated
     * @param string                $original_length       of a truncated column, or ''
     *
     * @return string  formatted data
     *
     * @access  private
     *
     * @see     _getDataCellForNumericColumns(), _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericColumns(),
     *
     */
    private function _getRowData(
        $class,
        $condition_field,
        array $analyzed_sql_results,
        $meta,
        array $map,
        $data,
        $displayedData,
        $transformation_plugin,
        $default_function,
        $nowrap,
        $where_comparison,
        array $transform_options,
        $is_field_truncated,
        $original_length = ''
    ) {
        $relational_display = $_SESSION['tmpval']['relational_display'];
        $printview = $this->__get('printview');
        $decimals = isset($meta->decimals) ? $meta->decimals : '-1';
        $result = '<td data-decimals="' . $decimals . '"'
            . ' data-type="' . $meta->type . '"';

        if (! empty($original_length)) {
            // cannot use data-original-length
            $result .= ' data-originallength="' . $original_length . '"';
        }

        $result .= ' class="'
            . $this->_addClass(
                $class,
                $condition_field,
                $meta,
                $nowrap,
                $is_field_truncated,
                $transformation_plugin,
                $default_function
            )
            . '">';

        if (! empty($analyzed_sql_results['statement']->expr)) {
            foreach ($analyzed_sql_results['statement']->expr as $expr) {
                if (empty($expr->alias) || empty($expr->column)) {
                    continue;
                }
                if (strcasecmp($meta->name, $expr->alias) == 0) {
                    $meta->name = $expr->column;
                }
            }
        }

        if (isset($map[$meta->name])) {
            // Field to display from the foreign table?
            if (isset($map[$meta->name][2])
                && strlen((string) $map[$meta->name][2]) > 0
            ) {
                $dispval = $this->_getFromForeign(
                    $map,
                    $meta,
                    $where_comparison
                );
            } else {
                $dispval = '';
            } // end if... else...

            if (isset($printview) && ($printview == '1')) {
                $result .= ($transformation_plugin != $default_function
                    ? $transformation_plugin->applyTransformation(
                        $data,
                        $transform_options,
                        $meta
                    )
                    : $default_function($data)
                )
                . ' <code>[-&gt;' . $dispval . ']</code>';
            } else {
                if ($relational_display == self::RELATIONAL_KEY) {
                    // user chose "relational key" in the display options, so
                    // the title contains the display field
                    $title = ! empty($dispval)
                        ? htmlspecialchars($dispval)
                        : '';
                } else {
                    $title = htmlspecialchars($data);
                }

                $sqlQuery = 'SELECT * FROM '
                    . Util::backquote($map[$meta->name][3]) . '.'
                    . Util::backquote($map[$meta->name][0])
                    . ' WHERE '
                    . Util::backquote($map[$meta->name][1])
                    . $where_comparison;

                $_url_params = [
                    'db'    => $map[$meta->name][3],
                    'table' => $map[$meta->name][0],
                    'pos'   => '0',
                    'sql_signature' => Core::signSqlQuery($sqlQuery),
                    'sql_query' => $sqlQuery,
                ];

                if ($transformation_plugin != $default_function) {
                    // always apply a transformation on the real data,
                    // not on the display field
                    $displayedData = $transformation_plugin->applyTransformation(
                        $data,
                        $transform_options,
                        $meta
                    );
                } else {
                    if ($relational_display == self::RELATIONAL_DISPLAY_COLUMN
                        && ! empty($map[$meta->name][2])
                    ) {
                        // user chose "relational display field" in the
                        // display options, so show display field in the cell
                        $displayedData = $dispval === null ? '<em>NULL</em>' : $default_function($dispval);
                    } else {
                        // otherwise display data in the cell
                        $displayedData = $default_function($displayedData);
                    }
                }

                $tag_params = ['title' => $title];
                if (strpos($class, 'grid_edit') !== false) {
                    $tag_params['class'] = 'ajax';
                }
                $result .= Util::linkOrButton(
                    'sql.php' . Url::getCommon($_url_params),
                    $displayedData,
                    $tag_params
                );
            }
        } else {
            $result .= ($transformation_plugin != $default_function
                ? $transformation_plugin->applyTransformation(
                    $data,
                    $transform_options,
                    $meta
                )
                : $default_function($data)
            );
        }

        $result .= '</td>' . "\n";

        return $result;
    }

    /**
     * Prepares a checkbox for multi-row submits
     *
     * @param string $del_url           delete url
     * @param array  $displayParts      array with explicit indexes for all
     *                                  the display elements
     * @param string $row_no            the row number
     * @param string $where_clause_html url encoded where clause
     * @param array  $condition_array   array of conditions in the where clause
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
        $del_url,
        array $displayParts,
        $row_no,
        $where_clause_html,
        array $condition_array,
        $id_suffix,
        $class
    ) {
        $ret = '';

        if (! empty($del_url) && $displayParts['del_lnk'] != self::KILL_PROCESS) {
            $ret .= '<td ';
            if (! empty($class)) {
                $ret .= 'class="' . $class . '"';
            }

            $ret .= ' class="center print_ignore">'
                . '<input type="checkbox" id="id_rows_to_delete'
                . $row_no . $id_suffix
                . '" name="rows_to_delete[' . $row_no . ']"'
                . ' class="multi_checkbox checkall"'
                . ' value="' . $where_clause_html . '">'
                . '<input type="hidden" class="condition_array" value="'
                . htmlspecialchars(json_encode($condition_array)) . '">'
                . '    </td>';
        }

        return $ret;
    }

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
        $edit_url,
        $class,
        $edit_str,
        $where_clause,
        $where_clause_html
    ) {
        $ret = '';
        if (! empty($edit_url)) {
            $ret .= '<td class="' . $class . ' center print_ignore">'
                . '<span class="nowrap">'
                . Util::linkOrButton($edit_url, $edit_str);
            /*
             * Where clause for selecting this row uniquely is provided as
             * a hidden input. Used by jQuery scripts for handling grid editing
             */
            if (! empty($where_clause)) {
                $ret .= '<input type="hidden" class="where_clause" value ="'
                    . $where_clause_html . '">';
            }
            $ret .= '</span></td>';
        }

        return $ret;
    }

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
        $copy_url,
        $copy_str,
        $where_clause,
        $where_clause_html,
        $class
    ) {
        $ret = '';
        if (! empty($copy_url)) {
            $ret .= '<td class="';
            if (! empty($class)) {
                $ret .= $class . ' ';
            }

            $ret .= 'center print_ignore"><span class="nowrap">'
               . Util::linkOrButton($copy_url, $copy_str);

            /*
             * Where clause for selecting this row uniquely is provided as
             * a hidden input. Used by jQuery scripts for handling grid editing
             */
            if (! empty($where_clause)) {
                $ret .= '<input type="hidden" class="where_clause" value="'
                    . $where_clause_html . '">';
            }
            $ret .= '</span></td>';
        }

        return $ret;
    }

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
        if (empty($del_url)) {
            return $ret;
        }

        $ret .= '<td class="';
        if (! empty($class)) {
            $ret .= $class . ' ';
        }
        $ajax = Response::getInstance()->isAjax() ? ' ajax' : '';
        $ret .= 'center print_ignore">'
            . Util::linkOrButton(
                $del_url,
                $del_str,
                ['class' => 'delete_row requireConfirm' . $ajax]
            )
            . '<div class="hide">' . $js_conf . '</div>'
            . '</td>';

        return $ret;
    }

    /**
     * Prepare checkbox and links at some position (left or right)
     * (only called for horizontal mode)
     *
     * @param string $position          the position of the checkbox and links
     * @param string $del_url           delete url
     * @param array  $displayParts      array with explicit indexes for all the
     *                                  display elements
     * @param string $row_no            row number
     * @param string $where_clause      where clause
     * @param string $where_clause_html url encoded where clause
     * @param array  $condition_array   array of conditions in the where clause
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
        $position,
        $del_url,
        array $displayParts,
        $row_no,
        $where_clause,
        $where_clause_html,
        array $condition_array,
        $edit_url,
        $copy_url,
        $class,
        $edit_str,
        $copy_str,
        $del_str,
        $js_conf
    ) {
        $ret = '';

        if ($position == self::POSITION_LEFT) {
            $ret .= $this->_getCheckboxForMultiRowSubmissions(
                $del_url,
                $displayParts,
                $row_no,
                $where_clause_html,
                $condition_array,
                '_left',
                ''
            );

            $ret .= $this->_getEditLink(
                $edit_url,
                $class,
                $edit_str,
                $where_clause,
                $where_clause_html
            );

            $ret .= $this->_getCopyLink(
                $copy_url,
                $copy_str,
                $where_clause,
                $where_clause_html,
                ''
            );

            $ret .= $this->_getDeleteLink($del_url, $del_str, $js_conf, '');
        } elseif ($position == self::POSITION_RIGHT) {
            $ret .= $this->_getDeleteLink($del_url, $del_str, $js_conf, '');

            $ret .= $this->_getCopyLink(
                $copy_url,
                $copy_str,
                $where_clause,
                $where_clause_html,
                ''
            );

            $ret .= $this->_getEditLink(
                $edit_url,
                $class,
                $edit_str,
                $where_clause,
                $where_clause_html
            );

            $ret .= $this->_getCheckboxForMultiRowSubmissions(
                $del_url,
                $displayParts,
                $row_no,
                $where_clause_html,
                $condition_array,
                '_right',
                ''
            );
        } else { // $position == self::POSITION_NONE
            $ret .= $this->_getCheckboxForMultiRowSubmissions(
                $del_url,
                $displayParts,
                $row_no,
                $where_clause_html,
                $condition_array,
                '_left',
                ''
            );
        }

        return $ret;
    }

    /**
     * Truncates given string based on LimitChars configuration
     * and Session pftext variable
     * (string is truncated only if necessary)
     *
     * @param string $str string to be truncated
     *
     * @return mixed
     *
     * @access  private
     *
     * @see     _handleNonPrintableContents(), _getDataCellForGeometryColumns(),
     *          _getDataCellForNonNumericColumns
     */
    private function _getPartialText($str)
    {
        $original_length = mb_strlen($str);
        if ($original_length > $GLOBALS['cfg']['LimitChars']
            && $_SESSION['tmpval']['pftext'] === self::DISPLAY_PARTIAL_TEXT
        ) {
            $str = mb_substr(
                $str,
                0,
                (int) $GLOBALS['cfg']['LimitChars']
            ) . '...';
            $truncated = true;
        } else {
            $truncated = false;
        }

        return [
            $truncated,
            $str,
            $original_length,
        ];
    }
}
