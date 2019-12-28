<?php
/**
 * Holds the PhpMyAdmin\Controllers\Table\SearchController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * @package PhpMyAdmin\Controllers
 */
class SearchController extends AbstractController
{
    /**
     * Normal search or Zoom search
     *
     * @access private
     * @var string
     */
    private $_searchType;
    /**
     * Names of columns
     *
     * @access private
     * @var array
     */
    private $_columnNames;
    /**
     * Types of columns
     *
     * @access private
     * @var array
     */
    private $_columnTypes;
    /**
     * Collations of columns
     *
     * @access private
     * @var array
     */
    private $_columnCollations;
    /**
     * Null Flags of columns
     *
     * @access private
     * @var array
     */
    private $_columnNullFlags;
    /**
     * Whether a geometry column is present
     *
     * @access private
     * @var boolean
     */
    private $_geomColumnFlag;
    /**
     * Foreign Keys
     *
     * @access private
     * @var array
     */
    private $_foreigners;
    /**
     * Connection charset
     *
     * @access private
     * @var string
     */
    private $_connectionCharSet;

    protected $url_query;

    /** @var Search */
    private $search;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * Constructor
     *
     * @param Response          $response   Response object
     * @param DatabaseInterface $dbi        DatabaseInterface object
     * @param Template          $template   Template object
     * @param string            $db         Database name
     * @param string            $table      Table name
     * @param string            $searchType Search type
     * @param string            $url_query  URL query
     * @param Search            $search     A Search instance.
     * @param Relation          $relation   Relation instance
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table,
        $searchType,
        $url_query,
        Search $search,
        Relation $relation
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);

        $this->url_query = $url_query;
        $this->_searchType = $searchType;
        $this->_columnNames = [];
        $this->_columnNullFlags = [];
        $this->_columnTypes = [];
        $this->_columnCollations = [];
        $this->_geomColumnFlag = false;
        $this->_foreigners = [];
        $this->search = $search;
        $this->relation = $relation;
        // Loads table's information
        $this->_loadTableInfo();
        $this->_connectionCharSet = $this->dbi->fetchValue(
            'SELECT @@character_set_connection'
        );
    }

    /**
     * Gets all the columns of a table along with their types, collations
     * and whether null or not.
     *
     * @return void
     */
    private function _loadTableInfo()
    {
        // Gets the list and number of columns
        $columns = $this->dbi->getColumns(
            $this->db,
            $this->table,
            null,
            true
        );
        // Get details about the geometry functions
        $geom_types = Util::getGISDatatypes();

        foreach ($columns as $row) {
            // set column name
            $this->_columnNames[] = $row['Field'];

            $type = $row['Type'];
            // check whether table contains geometric columns
            if (in_array($type, $geom_types)) {
                $this->_geomColumnFlag = true;
            }
            // reformat mysql query output
            if (strncasecmp($type, 'set', 3) == 0
                || strncasecmp($type, 'enum', 4) == 0
            ) {
                $type = str_replace(',', ', ', $type);
            } else {
                // strip the "BINARY" attribute, except if we find "BINARY(" because
                // this would be a BINARY or VARBINARY column type
                if (! preg_match('@BINARY[\(]@i', $type)) {
                    $type = str_ireplace('BINARY', '', $type);
                }
                $type = str_ireplace('ZEROFILL', '', $type);
                $type = str_ireplace('UNSIGNED', '', $type);
                $type = mb_strtolower($type);
            }
            if (empty($type)) {
                $type = '&nbsp;';
            }
            $this->_columnTypes[] = $type;
            $this->_columnNullFlags[] = $row['Null'];
            $this->_columnCollations[]
                = ! empty($row['Collation']) && $row['Collation'] != 'NULL'
                ? $row['Collation']
                : '';
        } // end for

        // Retrieve foreign keys
        $this->_foreigners = $this->relation->getForeigners($this->db, $this->table);
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        global $goto;
        switch ($this->_searchType) {
            case 'normal':
                $this->response->getHeader()
                ->getScripts()
                ->addFiles(
                    [
                        'makegrid.js',
                        'sql.js',
                        'table/select.js',
                        'table/change.js',
                        'vendor/jquery/jquery.uitablefilter.js',
                        'gis_data_editor.js',
                    ]
                );

                if (isset($_POST['range_search'])) {
                    $this->rangeSearchAction();

                    return;
                }

                /**
                 * No selection criteria received -> display the selection form
                 */
                if (! isset($_POST['columnsToDisplay'])
                && ! isset($_POST['displayAllColumns'])
                ) {
                    $this->displaySelectionFormAction();
                } else {
                    $this->doSelectionAction();
                }
                break;

            case 'zoom':
                $this->response->getHeader()
                ->getScripts()
                ->addFiles(
                    [
                        'makegrid.js',
                        'sql.js',
                        'vendor/jqplot/jquery.jqplot.js',
                        'vendor/jqplot/plugins/jqplot.canvasTextRenderer.js',
                        'vendor/jqplot/plugins/jqplot.canvasAxisLabelRenderer.js',
                        'vendor/jqplot/plugins/jqplot.dateAxisRenderer.js',
                        'vendor/jqplot/plugins/jqplot.highlighter.js',
                        'vendor/jqplot/plugins/jqplot.cursor.js',
                        'table/zoom_plot_jqplot.js',
                        'table/change.js',
                    ]
                );

                /**
                 * Handle AJAX request for data row on point select
                 */
                if (isset($_POST['get_data_row'])
                    && $_POST['get_data_row'] == true
                ) {
                    $this->getDataRowAction();

                    return;
                }
                /**
                 * Handle AJAX request for changing field information
                 * (value,collation,operators,field values) in input form
                 */
                if (isset($_POST['change_tbl_info'])
                && $_POST['change_tbl_info'] == true
                ) {
                    $this->changeTableInfoAction();

                    return;
                }

                //Set default datalabel if not selected
                if (! isset($_POST['zoom_submit']) || $_POST['dataLabel'] == '') {
                    $dataLabel = $this->relation->getDisplayField($this->db, $this->table);
                } else {
                    $dataLabel = $_POST['dataLabel'];
                }

                // Displays the zoom search form
                $this->displaySelectionFormAction($dataLabel);

                /*
                 * Handle the input criteria and generate the query result
                 * Form for displaying query results
                 */
                if (isset($_POST['zoom_submit'])
                && $_POST['criteriaColumnNames'][0] != 'pma_null'
                && $_POST['criteriaColumnNames'][1] != 'pma_null'
                && $_POST['criteriaColumnNames'][0] != $_POST['criteriaColumnNames'][1]
                ) {
                    if (! isset($goto)) {
                        $goto = Util::getScriptNameForOption(
                            $GLOBALS['cfg']['DefaultTabTable'],
                            'table'
                        );
                    }
                    $this->zoomSubmitAction($dataLabel, $goto);
                }
                break;
        }
    }

    /**
     * Zoom submit action
     *
     * @param string $dataLabel Data label
     * @param string $goto      Goto
     *
     * @return void
     */
    public function zoomSubmitAction($dataLabel, $goto)
    {
        //Query generation part
        $sql_query = $this->search->buildSqlQuery();
        $sql_query .= ' LIMIT ' . $_POST['maxPlotLimit'];

        //Query execution part
        $result = $this->dbi->query(
            $sql_query . ';',
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        $fields_meta = $this->dbi->getFieldsMeta($result);
        $data = [];
        while ($row = $this->dbi->fetchAssoc($result)) {
            //Need a row with indexes as 0,1,2 for the getUniqueCondition
            // hence using a temporary array
            $tmpRow = [];
            foreach ($row as $val) {
                $tmpRow[] = $val;
            }
            //Get unique condition on each row (will be needed for row update)
            $uniqueCondition = Util::getUniqueCondition(
                $result, // handle
                count($this->_columnNames), // fields_cnt
                $fields_meta, // fields_meta
                $tmpRow, // row
                true, // force_unique
                false, // restrict_to_table
                null // analyzed_sql_results
            );
            //Append it to row array as where_clause
            $row['where_clause'] = $uniqueCondition[0];

            $tmpData = [
                $_POST['criteriaColumnNames'][0] =>
                    $row[$_POST['criteriaColumnNames'][0]],
                $_POST['criteriaColumnNames'][1] =>
                    $row[$_POST['criteriaColumnNames'][1]],
                'where_clause' => $uniqueCondition[0],
            ];
            $tmpData[$dataLabel] = $dataLabel ? $row[$dataLabel] : '';
            $data[] = $tmpData;
        }
        unset($tmpData);

        //Displays form for point data and scatter plot
        $titles = [
            'Browse' => Generator::getIcon(
                'b_browse',
                __('Browse foreign values')
            ),
        ];
        $column_names_hashes = [];

        foreach ($this->_columnNames as $columnName) {
            $column_names_hashes[$columnName] = md5($columnName);
        }

        $this->response->addHTML(
            $this->template->render('table/search/zoom_result_form', [
                'db' => $this->db,
                'table' => $this->table,
                'column_names' => $this->_columnNames,
                'column_names_hashes' => $column_names_hashes,
                'foreigners' => $this->_foreigners,
                'column_null_flags' => $this->_columnNullFlags,
                'column_types' => $this->_columnTypes,
                'titles' => $titles,
                'goto' => $goto,
                'data' => $data,
                'data_json' => json_encode($data),
                'zoom_submit' => isset($_POST['zoom_submit']),
                'foreign_max_limit' => $GLOBALS['cfg']['ForeignKeyMaxLimit'],
            ])
        );
    }

    /**
     * Change table info action
     *
     * @return void
     */
    public function changeTableInfoAction()
    {
        $field = $_POST['field'];
        if ($field == 'pma_null') {
            $this->response->addJSON('field_type', '');
            $this->response->addJSON('field_collation', '');
            $this->response->addJSON('field_operators', '');
            $this->response->addJSON('field_value', '');
            return;
        }
        $key = array_search($field, $this->_columnNames);
        $search_index
            = (isset($_POST['it']) && is_numeric($_POST['it'])
                ? intval($_POST['it']) : 0);

        $properties = $this->getColumnProperties($search_index, $key);
        $this->response->addJSON(
            'field_type',
            htmlspecialchars($properties['type'])
        );
        $this->response->addJSON('field_collation', $properties['collation']);
        $this->response->addJSON('field_operators', $properties['func']);
        $this->response->addJSON('field_value', $properties['value']);
    }

    /**
     * Get data row action
     *
     * @return void
     */
    public function getDataRowAction()
    {
        $extra_data = [];
        $row_info_query = 'SELECT * FROM `' . $_POST['db'] . '`.`'
            . $_POST['table'] . '` WHERE ' . $_POST['where_clause'];
        $result = $this->dbi->query(
            $row_info_query . ';',
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        $fields_meta = $this->dbi->getFieldsMeta($result);
        while ($row = $this->dbi->fetchAssoc($result)) {
            // for bit fields we need to convert them to printable form
            $i = 0;
            foreach ($row as $col => $val) {
                if ($fields_meta[$i]->type == 'bit') {
                    $row[$col] = Util::printableBitValue(
                        (int) $val,
                        (int) $fields_meta[$i]->length
                    );
                }
                $i++;
            }
            $extra_data['row_info'] = $row;
        }
        $this->response->addJSON($extra_data);
    }

    /**
     * Do selection action
     *
     * @return void
     */
    public function doSelectionAction()
    {
        /**
         * Selection criteria have been submitted -> do the work
         */
        $sql_query = $this->search->buildSqlQuery();

        /**
         * Add this to ensure following procedures included running correctly.
         */
        $sql = new Sql();
        $sql->executeQueryAndSendQueryResponse(
            null, // analyzed_sql_results
            false, // is_gotofile
            $this->db, // db
            $this->table, // table
            null, // find_real_end
            null, // sql_query_for_bookmark
            null, // extra_data
            null, // message_to_show
            null, // message
            null, // sql_data
            $GLOBALS['goto'], // goto
            $GLOBALS['pmaThemeImage'], // pmaThemeImage
            null, // disp_query
            null, // disp_message
            null, // query_type
            $sql_query, // sql_query
            null, // selectedTables
            null // complete_query
        );
    }

    /**
     * Display selection form action
     *
     * @param string $dataLabel Data label
     *
     * @return void
     */
    public function displaySelectionFormAction($dataLabel = null)
    {
        global $goto;
        $this->url_query .= Url::getCommon([
            'back' => Url::getFromRoute('/table/search'),
            'goto' => Url::getFromRoute('/table/search'),
        ], '&');
        if (! isset($goto)) {
            $goto = Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabTable'],
                'table'
            );
        }
        // Displays the table search form
        $this->response->addHTML(
            $this->template->render('secondary_tabs', [
                'url_params' => [
                    'db'    => $this->db,
                    'table' => $this->table,
                ],
                'sub_tabs'   => $this->_getSubTabs(),
            ])
        );

        $column_names = $this->_columnNames;
        $column_types = $this->_columnTypes;

        $criteria_column_names = $_POST['criteriaColumnNames'] ?? null;
        $keys = [];
        for ($i = 0; $i < 4; $i++) {
            if (isset($criteria_column_names[$i])) {
                if ($criteria_column_names[$i] != 'pma_null') {
                    $keys[$criteria_column_names[$i]] = array_search($criteria_column_names[$i], $column_names);
                }
            }
        }

        $this->response->addHTML(
            $this->template->render('table/search/selection_form', [
                'search_type' => $this->_searchType,
                'db' => $this->db,
                'table' => $this->table,
                'goto' => $goto,
                'self' => $this,
                'geom_column_flag' => $this->_geomColumnFlag,
                'column_names' => $column_names,
                'column_types' => $column_types,
                'column_collations' => $this->_columnCollations,
                'data_label' => $dataLabel,
                'keys' => $keys,
                'criteria_column_names' => $criteria_column_names,
                'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
                'criteria_column_types' => $_POST['criteriaColumnTypes'] ?? null,
                'max_rows' => intval($GLOBALS['cfg']['MaxRows']),
                'max_plot_limit' => ! empty($_POST['maxPlotLimit'])
                    ? intval($_POST['maxPlotLimit'])
                    : intval($GLOBALS['cfg']['maxRowPlotLimit']),
            ])
        );
    }

    /**
     * Range search action
     *
     * @return void
     */
    public function rangeSearchAction()
    {
        $min_max = $this->getColumnMinMax($_POST['column']);
        $this->response->addJSON('column_data', $min_max);
    }

    /**
     * Finds minimum and maximum value of a given column.
     *
     * @param string $column Column name
     *
     * @return array
     */
    public function getColumnMinMax($column)
    {
        $sql_query = 'SELECT MIN(' . Util::backquote($column) . ') AS `min`, '
            . 'MAX(' . Util::backquote($column) . ') AS `max` '
            . 'FROM ' . Util::backquote($this->db) . '.'
            . Util::backquote($this->table);

        return $this->dbi->fetchSingleRow($sql_query);
    }

    /**
     * Returns an array with necessary configurations to create
     * sub-tabs in the table_select page.
     *
     * @return array Array containing configuration (icon, text, link, id, args)
     * of sub-tabs
     */
    private function _getSubTabs()
    {
        $subtabs = [];
        $subtabs['search']['icon'] = 'b_search';
        $subtabs['search']['text'] = __('Table search');
        $subtabs['search']['link'] = Url::getFromRoute('/table/search');
        $subtabs['search']['id'] = 'tbl_search_id';
        $subtabs['search']['args']['pos'] = 0;
        $subtabs['search']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/table/search';

        $subtabs['zoom']['icon'] = 'b_select';
        $subtabs['zoom']['link'] = Url::getFromRoute('/table/zoom_select');
        $subtabs['zoom']['text'] = __('Zoom search');
        $subtabs['zoom']['id'] = 'zoom_search_id';
        $subtabs['zoom']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/table/zoom_select';

        $subtabs['replace']['icon'] = 'b_find_replace';
        $subtabs['replace']['link'] = Url::getFromRoute('/table/find-replace');
        $subtabs['replace']['text'] = __('Find and replace');
        $subtabs['replace']['id'] = 'find_replace_id';
        $subtabs['replace']['active'] = isset($_REQUEST['route']) && $_REQUEST['route'] === '/table/find-replace';

        return $subtabs;
    }

    /**
     * Provides a column's type, collation, operators list, and criteria value
     * to display in table search form
     *
     * @param integer $search_index Row number in table search form
     * @param integer $column_index Column index in ColumnNames array
     *
     * @return array Array containing column's properties
     */
    public function getColumnProperties($search_index, $column_index)
    {
        $selected_operator = ($_POST['criteriaColumnOperators'][$search_index] ?? '');
        $entered_value = ($_POST['criteriaValues'] ?? '');
        $titles = [
            'Browse' => Generator::getIcon(
                'b_browse',
                __('Browse foreign values')
            ),
        ];
        //Gets column's type and collation
        $type = $this->_columnTypes[$column_index];
        $collation = $this->_columnCollations[$column_index];
        //Gets column's comparison operators depending on column type
        $typeOperators = $this->dbi->types->getTypeOperatorsHtml(
            preg_replace('@\(.*@s', '', $this->_columnTypes[$column_index]),
            $this->_columnNullFlags[$column_index],
            $selected_operator
        );
        $func = $this->template->render('table/search/column_comparison_operators', [
            'search_index' => $search_index,
            'type_operators' => $typeOperators,
        ]);
        //Gets link to browse foreign data(if any) and criteria inputbox
        $foreignData = $this->relation->getForeignData(
            $this->_foreigners,
            $this->_columnNames[$column_index],
            false,
            '',
            ''
        );
        $value = $this->template->render('table/search/input_box', [
            'str' => '',
            'column_type' => (string) $type,
            'column_id' => 'fieldID_',
            'in_zoom_search_edit' => false,
            'foreigners' => $this->_foreigners,
            'column_name' => $this->_columnNames[$column_index],
            'column_name_hash' => md5($this->_columnNames[$column_index]),
            'foreign_data' => $foreignData,
            'table' => $this->table,
            'column_index' => $search_index,
            'foreign_max_limit' => $GLOBALS['cfg']['ForeignKeyMaxLimit'],
            'criteria_values' => $entered_value,
            'db' => $this->db,
            'titles' => $titles,
            'in_fbs' => true,
        ]);
        return [
            'type' => $type,
            'collation' => $collation,
            'func' => $func,
            'value' => $value,
        ];
    }
}
