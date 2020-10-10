<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Table\SearchController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Class SearchController
 *
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
     * Types of columns without any replacement
     *
     * @access private
     * @var array
     */
    private $_originalColumnTypes;
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
        Relation $relation
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);

        $this->url_query = $url_query;
        $this->_searchType = $searchType;
        $this->_columnNames = [];
        $this->_columnNullFlags = [];
        $this->_columnTypes = [];
        $this->_originalColumnTypes = [];
        $this->_columnCollations = [];
        $this->_geomColumnFlag = false;
        $this->_foreigners = [];
        $this->relation = $relation;
        // Loads table's information
        $this->_loadTableInfo();
        $this->_connectionCharSet = $this->dbi->fetchValue(
            "SELECT @@character_set_connection"
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
            // before any replacement
            $this->_originalColumnTypes[] = mb_strtolower($type);
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
                    $type = str_ireplace("BINARY", '', $type);
                }
                $type = str_ireplace("ZEROFILL", '', $type);
                $type = str_ireplace("UNSIGNED", '', $type);
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
            case 'replace':
                if (isset($_POST['find'])) {
                    $this->findAction();

                    return;
                }
                $this->response
                ->getHeader()
                ->getScripts()
                ->addFile('table/find_replace.js');

                if (isset($_POST['replace'])) {
                    $this->replaceAction();
                }

                // Displays the find and replace form
                $this->displaySelectionFormAction();
                break;

            case 'normal':
                $this->response->getHeader()
                ->getScripts()
                ->addFiles(
                    [
                        'makegrid.js',
                        'vendor/stickyfill.min.js',
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
                        'vendor/stickyfill.min.js',
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
             *
             * @var boolean Object containing parameters for the POST request
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
             *
             * @var boolean Object containing parameters for the POST request
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
        $sql_query = $this->_buildSqlQuery();
        $sql_query .= ' LIMIT ' . $_POST['maxPlotLimit'];

        //Query execution part
        $result = $this->dbi->query(
            $sql_query . ";",
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
            $row['where_clause_sign'] = Core::signSqlQuery($uniqueCondition[0]);

            $tmpData = [
                $_POST['criteriaColumnNames'][0] =>
                    $row[$_POST['criteriaColumnNames'][0]],
                $_POST['criteriaColumnNames'][1] =>
                    $row[$_POST['criteriaColumnNames'][1]],
                'where_clause' => $uniqueCondition[0],
                'where_clause_sign' => Core::signSqlQuery($uniqueCondition[0])
            ];
            $tmpData[$dataLabel] = ($dataLabel) ? $row[$dataLabel] : '';
            $data[] = $tmpData;
        }
        unset($tmpData);

        //Displays form for point data and scatter plot
        $titles = [
            'Browse' => Util::getIcon(
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
        if (! Core::checkSqlQuerySignature($_POST['where_clause'], $_POST['where_clause_sign'])) {
            return;
        }

        $extra_data = [];
        $row_info_query = 'SELECT * FROM ' . Util::backquote($_POST['db']) . '.'
            . Util::backquote($_POST['table']) . ' WHERE ' . $_POST['where_clause'];
        $result = $this->dbi->query(
            $row_info_query . ";",
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
        $sql_query = $this->_buildSqlQuery();

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
        $this->url_query .= '&amp;goto=tbl_select.php&amp;back=tbl_select.php';
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
        $types = [];
        if ($this->_searchType == 'replace') {
            $num_cols = count($column_names);
            for ($i = 0; $i < $num_cols; $i++) {
                $types[$column_names[$i]] = preg_replace('@\\(.*@s', '', $column_types[$i]);
            }
        }

        $criteria_column_names = isset($_POST['criteriaColumnNames']) ? $_POST['criteriaColumnNames'] : null;
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
                'types' => $types,
                'column_collations' => $this->_columnCollations,
                'data_label' => $dataLabel,
                'keys' => $keys,
                'criteria_column_names' => $criteria_column_names,
                'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
                'criteria_column_types' => isset($_POST['criteriaColumnTypes']) ? $_POST['criteriaColumnTypes'] : null,
                'sql_types' => $this->dbi->types,
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
     * Find action
     *
     * @return void
     */
    public function findAction()
    {
        $useRegex = array_key_exists('useRegex', $_POST)
            && $_POST['useRegex'] == 'on';

        $preview = $this->getReplacePreview(
            $_POST['columnIndex'],
            $_POST['find'],
            $_POST['replaceWith'],
            $useRegex,
            $this->_connectionCharSet
        );
        $this->response->addJSON('preview', $preview);
    }

    /**
     * Replace action
     *
     * @return void
     */
    public function replaceAction()
    {
        $this->replace(
            $_POST['columnIndex'],
            $_POST['findString'],
            $_POST['replaceWith'],
            $_POST['useRegex'],
            $this->_connectionCharSet
        );
        $this->response->addHTML(
            Util::getMessage(
                __('Your SQL query has been executed successfully.'),
                null,
                'success'
            )
        );
    }

    /**
     * Returns HTML for previewing strings found and their replacements
     *
     * @param int     $columnIndex index of the column
     * @param string  $find        string to find in the column
     * @param string  $replaceWith string to replace with
     * @param boolean $useRegex    to use Regex replace or not
     * @param string  $charSet     character set of the connection
     *
     * @return string HTML for previewing strings found and their replacements
     */
    public function getReplacePreview(
        $columnIndex,
        $find,
        $replaceWith,
        $useRegex,
        $charSet
    ) {
        $column = $this->_columnNames[$columnIndex];
        if ($useRegex) {
            $result = $this->_getRegexReplaceRows(
                $columnIndex,
                $find,
                $replaceWith,
                $charSet
            );
        } else {
            $sql_query = "SELECT "
                . Util::backquote($column) . ","
                . " REPLACE("
                . Util::backquote($column) . ", '" . $find . "', '"
                . $replaceWith
                . "'),"
                . " COUNT(*)"
                . " FROM " . Util::backquote($this->db)
                . "." . Util::backquote($this->table)
                . " WHERE " . Util::backquote($column)
                . " LIKE '%" . $find . "%' COLLATE " . $charSet . "_bin"; // here we
            // change the collation of the 2nd operand to a case sensitive
            // binary collation to make sure that the comparison
            // is case sensitive
            $sql_query .= " GROUP BY " . Util::backquote($column)
                . " ORDER BY " . Util::backquote($column) . " ASC";

            $result = $this->dbi->fetchResult($sql_query, 0);
        }

        return $this->template->render('table/search/replace_preview', [
            'db' => $this->db,
            'table' => $this->table,
            'column_index' => $columnIndex,
            'find' => $find,
            'replace_with' => $replaceWith,
            'use_regex' => $useRegex,
            'result' => $result,
        ]);
    }

    /**
     * Finds and returns Regex pattern and their replacements
     *
     * @param int    $columnIndex index of the column
     * @param string $find        string to find in the column
     * @param string $replaceWith string to replace with
     * @param string $charSet     character set of the connection
     *
     * @return array|bool Array containing original values, replaced values and count
     */
    private function _getRegexReplaceRows(
        $columnIndex,
        $find,
        $replaceWith,
        $charSet
    ) {
        $column = $this->_columnNames[$columnIndex];
        $sql_query = "SELECT "
            . Util::backquote($column) . ","
            . " 1," // to add an extra column that will have replaced value
            . " COUNT(*)"
            . " FROM " . Util::backquote($this->db)
            . "." . Util::backquote($this->table)
            . " WHERE " . Util::backquote($column)
            . " RLIKE '" . $this->dbi->escapeString($find) . "' COLLATE "
            . $charSet . "_bin"; // here we
        // change the collation of the 2nd operand to a case sensitive
        // binary collation to make sure that the comparison is case sensitive
        $sql_query .= " GROUP BY " . Util::backquote($column)
            . " ORDER BY " . Util::backquote($column) . " ASC";

        $result = $this->dbi->fetchResult($sql_query, 0);

        if (is_array($result)) {
            /* Iterate over possible delimiters to get one */
            $delimiters = [
                '/',
                '@',
                '#',
                '~',
                '!',
                '$',
                '%',
                '^',
                '&',
                '_',
            ];
            $found = false;
            for ($i = 0, $l = count($delimiters); $i < $l; $i++) {
                if (strpos($find, $delimiters[$i]) === false) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                return false;
            }
            $find = $delimiters[$i] . $find . $delimiters[$i];
            foreach ($result as $index => $row) {
                $result[$index][1] = preg_replace(
                    $find,
                    $replaceWith,
                    $row[0]
                );
            }
        }
        return $result;
    }

    /**
     * Replaces a given string in a column with a give replacement
     *
     * @param int     $columnIndex index of the column
     * @param string  $find        string to find in the column
     * @param string  $replaceWith string to replace with
     * @param boolean $useRegex    to use Regex replace or not
     * @param string  $charSet     character set of the connection
     *
     * @return void
     */
    public function replace(
        $columnIndex,
        $find,
        $replaceWith,
        $useRegex,
        $charSet
    ) {
        $column = $this->_columnNames[$columnIndex];
        if ($useRegex) {
            $toReplace = $this->_getRegexReplaceRows(
                $columnIndex,
                $find,
                $replaceWith,
                $charSet
            );
            $sql_query = "UPDATE " . Util::backquote($this->table)
                . " SET " . Util::backquote($column) . " = CASE";
            if (is_array($toReplace)) {
                foreach ($toReplace as $row) {
                    $sql_query .= "\n WHEN " . Util::backquote($column)
                        . " = '" . $this->dbi->escapeString($row[0])
                        . "' THEN '" . $this->dbi->escapeString($row[1]) . "'";
                }
            }
            $sql_query .= " END"
                . " WHERE " . Util::backquote($column)
                . " RLIKE '" . $this->dbi->escapeString($find) . "' COLLATE "
                . $charSet . "_bin"; // here we
            // change the collation of the 2nd operand to a case sensitive
            // binary collation to make sure that the comparison
            // is case sensitive
        } else {
            $sql_query = "UPDATE " . Util::backquote($this->table)
                . " SET " . Util::backquote($column) . " ="
                . " REPLACE("
                . Util::backquote($column) . ", '" . $find . "', '"
                . $replaceWith
                . "')"
                . " WHERE " . Util::backquote($column)
                . " LIKE '%" . $find . "%' COLLATE " . $charSet . "_bin"; // here we
            // change the collation of the 2nd operand to a case sensitive
            // binary collation to make sure that the comparison
            // is case sensitive
        }
        $this->dbi->query(
            $sql_query,
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        $GLOBALS['sql_query'] = $sql_query;
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
        $subtabs['search']['link'] = 'tbl_select.php';
        $subtabs['search']['id'] = 'tbl_search_id';
        $subtabs['search']['args']['pos'] = 0;

        $subtabs['zoom']['icon'] = 'b_select';
        $subtabs['zoom']['link'] = 'tbl_zoom_select.php';
        $subtabs['zoom']['text'] = __('Zoom search');
        $subtabs['zoom']['id'] = 'zoom_search_id';

        $subtabs['replace']['icon'] = 'b_find_replace';
        $subtabs['replace']['link'] = 'tbl_find_replace.php';
        $subtabs['replace']['text'] = __('Find and replace');
        $subtabs['replace']['id'] = 'find_replace_id';

        return $subtabs;
    }

    /**
     * Builds the sql search query from the post parameters
     *
     * @return string the generated SQL query
     */
    private function _buildSqlQuery()
    {
        $sql_query = 'SELECT ';

        // If only distinct values are needed
        $is_distinct = isset($_POST['distinct']) ? 'true' : 'false';
        if ($is_distinct == 'true') {
            $sql_query .= 'DISTINCT ';
        }

        // if all column names were selected to display, we do a 'SELECT *'
        // (more efficient and this helps prevent a problem in IE
        // if one of the rows is edited and we come back to the Select results)
        if (isset($_POST['zoom_submit']) || ! empty($_POST['displayAllColumns'])) {
            $sql_query .= '* ';
        } else {
            $sql_query .= implode(
                ', ',
                Util::backquote($_POST['columnsToDisplay'])
            );
        } // end if

        $sql_query .= ' FROM '
            . Util::backquote($_POST['table']);
        $whereClause = $this->_generateWhereClause();
        $sql_query .= $whereClause;

        // if the search results are to be ordered
        if (isset($_POST['orderByColumn']) && $_POST['orderByColumn'] != '--nil--') {
            $sql_query .= ' ORDER BY '
                . Util::backquote($_POST['orderByColumn'])
                . ' ' . $_POST['order'];
        } // end if
        return $sql_query;
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
        $selected_operator = (isset($_POST['criteriaColumnOperators'][$search_index])
            ? $_POST['criteriaColumnOperators'][$search_index] : '');
        $entered_value = (isset($_POST['criteriaValues'])
            ? $_POST['criteriaValues'] : '');
        $titles = [
            'Browse' => Util::getIcon(
                'b_browse',
                __('Browse foreign values')
            ),
        ];
        //Gets column's type and collation
        $type = $this->_columnTypes[$column_index];
        $collation = $this->_columnCollations[$column_index];
        $cleanType = preg_replace('@\(.*@s', '', $type);
        //Gets column's comparison operators depending on column type
        $typeOperators = $this->dbi->types->getTypeOperatorsHtml(
            $cleanType,
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
        $html_attributes = '';
        if (in_array($cleanType, $this->dbi->types->getIntegerTypes())) {
            $extracted_columnspec = Util::extractColumnSpec(
                $this->_originalColumnTypes[$column_index]
            );
            $is_unsigned = $extracted_columnspec['unsigned'];
            $min_max_values = $this->dbi->types->getIntegerRange(
                $cleanType,
                ! $is_unsigned
            );
            $html_attributes = 'data-min="' . $min_max_values[0] . '" '
                            . 'data-max="' . $min_max_values[1] . '"';
        }

        $searchFormId = $this->_searchType === 'zoom' ? '#zoom_search_form' : '#tbl_search_form';

        $html_attributes .= ' onchange="return verifyAfterSearchFieldChange(' . $column_index . ', \'' . $searchFormId . '\')"';

        $value = $this->template->render('table/search/input_box', [
            'str' => '',
            'column_type' => (string) $type,
            'column_data_type' => strtoupper($cleanType),
            'html_attributes' => $html_attributes,
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

    /**
     * Generates the where clause for the SQL search query to be executed
     *
     * @return string the generated where clause
     */
    private function _generateWhereClause()
    {
        if (isset($_POST['customWhereClause'])
            && trim($_POST['customWhereClause']) != ''
        ) {
            return ' WHERE ' . $_POST['customWhereClause'];
        }

        // If there are no search criteria set or no unary criteria operators,
        // return
        if (! isset($_POST['criteriaValues'])
            && ! isset($_POST['criteriaColumnOperators'])
            && ! isset($_POST['geom_func'])
        ) {
            return '';
        }

        // else continue to form the where clause from column criteria values
        $fullWhereClause = [];
        foreach ($_POST['criteriaColumnOperators'] as $column_index => $operator) {
            $unaryFlag =  $this->dbi->types->isUnaryOperator($operator);
            $tmp_geom_func = isset($_POST['geom_func'][$column_index])
                ? $_POST['geom_func'][$column_index] : null;

            $whereClause = $this->_getWhereClause(
                $_POST['criteriaValues'][$column_index],
                $_POST['criteriaColumnNames'][$column_index],
                $_POST['criteriaColumnTypes'][$column_index],
                $operator,
                $unaryFlag,
                $tmp_geom_func
            );

            if ($whereClause) {
                $fullWhereClause[] = $whereClause;
            }
        } // end foreach

        if (! empty($fullWhereClause)) {
            return ' WHERE ' . implode(' AND ', $fullWhereClause);
        }
        return '';
    }

    /**
     * Return the where clause in case column's type is ENUM.
     *
     * @param mixed  $criteriaValues Search criteria input
     * @param string $func_type      Search function/operator
     *
     * @return string part of where clause.
     */
    private function _getEnumWhereClause($criteriaValues, $func_type)
    {
        if (! is_array($criteriaValues)) {
            $criteriaValues = explode(',', $criteriaValues);
        }
        $enum_selected_count = count($criteriaValues);
        if ($func_type == '=' && $enum_selected_count > 1) {
            $func_type    = 'IN';
            $parens_open  = '(';
            $parens_close = ')';
        } elseif ($func_type == '!=' && $enum_selected_count > 1) {
            $func_type    = 'NOT IN';
            $parens_open  = '(';
            $parens_close = ')';
        } else {
            $parens_open  = '';
            $parens_close = '';
        }
        $enum_where = '\''
            . $this->dbi->escapeString($criteriaValues[0]) . '\'';
        for ($e = 1; $e < $enum_selected_count; $e++) {
            $enum_where .= ', \''
                . $this->dbi->escapeString($criteriaValues[$e]) . '\'';
        }

        return ' ' . $func_type . ' ' . $parens_open
        . $enum_where . $parens_close;
    }

    /**
     * Return the where clause for a geometrical column.
     *
     * @param mixed  $criteriaValues Search criteria input
     * @param string $names          Name of the column on which search is submitted
     * @param string $func_type      Search function/operator
     * @param string $types          Type of the field
     * @param bool   $geom_func      Whether geometry functions should be applied
     *
     * @return string part of where clause.
     */
    private function _getGeomWhereClause(
        $criteriaValues,
        $names,
        $func_type,
        $types,
        $geom_func = null
    ) {
        $geom_unary_functions = [
            'IsEmpty' => 1,
            'IsSimple' => 1,
            'IsRing' => 1,
            'IsClosed' => 1,
        ];
        $where = '';

        // Get details about the geometry functions
        $geom_funcs = Util::getGISFunctions($types, true, false);

        // If the function takes multiple parameters
        if (strpos($func_type, "IS NULL") !== false || strpos($func_type, "IS NOT NULL") !== false) {
            return Util::backquote($names) . " " . $func_type;
        } elseif ($geom_funcs[$geom_func]['params'] > 1) {
            // create gis data from the criteria input
            $gis_data = Util::createGISData($criteriaValues, $this->dbi->getVersion());
            return $geom_func . '(' . Util::backquote($names)
                . ', ' . $gis_data . ')';
        }

        // New output type is the output type of the function being applied
        $type = $geom_funcs[$geom_func]['type'];
        $geom_function_applied = $geom_func
            . '(' . Util::backquote($names) . ')';

        // If the where clause is something like 'IsEmpty(`spatial_col_name`)'
        if (isset($geom_unary_functions[$geom_func])
            && trim($criteriaValues) == ''
        ) {
            $where = $geom_function_applied;
        } elseif (in_array($type, Util::getGISDatatypes())
            && ! empty($criteriaValues)
        ) {
            // create gis data from the criteria input
            $gis_data = Util::createGISData($criteriaValues, $this->dbi->getVersion());
            $where = $geom_function_applied . " " . $func_type . " " . $gis_data;
        } elseif (strlen($criteriaValues) > 0) {
            $where = $geom_function_applied . " "
                . $func_type . " '" . $criteriaValues . "'";
        }
        return $where;
    }

    /**
     * Return the where clause for query generation based on the inputs provided.
     *
     * @param mixed  $criteriaValues Search criteria input
     * @param string $names          Name of the column on which search is submitted
     * @param string $types          Type of the field
     * @param string $func_type      Search function/operator
     * @param bool   $unaryFlag      Whether operator unary or not
     * @param bool   $geom_func      Whether geometry functions should be applied
     *
     * @return string generated where clause.
     */
    private function _getWhereClause(
        $criteriaValues,
        $names,
        $types,
        $func_type,
        $unaryFlag,
        $geom_func = null
    ) {
        // If geometry function is set
        if (! empty($geom_func)) {
            return $this->_getGeomWhereClause(
                $criteriaValues,
                $names,
                $func_type,
                $types,
                $geom_func
            );
        }

        $backquoted_name = Util::backquote($names);
        $where = '';
        if ($unaryFlag) {
            $where = $backquoted_name . ' ' . $func_type;
        } elseif (strncasecmp($types, 'enum', 4) == 0 && (! empty($criteriaValues) || $criteriaValues[0] === '0')) {
            $where = $backquoted_name;
            $where .= $this->_getEnumWhereClause($criteriaValues, $func_type);
        } elseif ($criteriaValues != '') {
            // For these types we quote the value. Even if it's another type
            // (like INT), for a LIKE we always quote the value. MySQL converts
            // strings to numbers and numbers to strings as necessary
            // during the comparison
            if (preg_match('@char|binary|blob|text|set|date|time|year@i', $types)
                || mb_strpos(' ' . $func_type, 'LIKE')
            ) {
                $quot = '\'';
            } else {
                $quot = '';
            }

            // LIKE %...%
            if ($func_type == 'LIKE %...%') {
                $func_type = 'LIKE';
                $criteriaValues = '%' . $criteriaValues . '%';
            }
            if ($func_type == 'REGEXP ^...$') {
                $func_type = 'REGEXP';
                $criteriaValues = '^' . $criteriaValues . '$';
            }

            if ('IN (...)' != $func_type
                && 'NOT IN (...)' != $func_type
                && 'BETWEEN' != $func_type
                && 'NOT BETWEEN' != $func_type
            ) {
                return $backquoted_name . ' ' . $func_type . ' ' . $quot
                        . $this->dbi->escapeString($criteriaValues) . $quot;
            }
            $func_type = str_replace(' (...)', '', $func_type);

            //Don't explode if this is already an array
            //(Case for (NOT) IN/BETWEEN.)
            if (is_array($criteriaValues)) {
                $values = $criteriaValues;
            } else {
                $values = explode(',', $criteriaValues);
            }
            // quote values one by one
            $emptyKey = false;
            foreach ($values as $key => &$value) {
                if ('' === $value) {
                    $emptyKey = $key;
                    $value = 'NULL';
                    continue;
                }
                $value = $quot . $this->dbi->escapeString(trim($value))
                    . $quot;
            }

            if ('BETWEEN' == $func_type || 'NOT BETWEEN' == $func_type) {
                $where = $backquoted_name . ' ' . $func_type . ' '
                    . (isset($values[0]) ? $values[0] : '')
                    . ' AND ' . (isset($values[1]) ? $values[1] : '');
            } else { //[NOT] IN
                if (false !== $emptyKey) {
                    unset($values[$emptyKey]);
                }
                $wheres = [];
                if (! empty($values)) {
                    $wheres[] = $backquoted_name . ' ' . $func_type
                        . ' (' . implode(',', $values) . ')';
                }
                if (false !== $emptyKey) {
                    $wheres[] = $backquoted_name . ' IS NULL';
                }
                $where = implode(' OR ', $wheres);
                if (1 < count($wheres)) {
                    $where = '(' . $where . ')';
                }
            }
        } // end if

        return $where;
    }
}
