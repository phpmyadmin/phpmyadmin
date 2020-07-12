<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Common;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use function array_search;
use function count;
use function htmlspecialchars;
use function in_array;
use function intval;
use function is_numeric;
use function json_encode;
use function mb_strtolower;
use function md5;
use function preg_match;
use function preg_replace;
use function str_ireplace;
use function str_replace;
use function strncasecmp;

/**
 * Handles table zoom search tab.
 *
 * Display table zoom search form, create SQL queries from form data.
 */
class ZoomSearchController extends AbstractController
{
    /** @var Search */
    private $search;

    /** @var Relation */
    private $relation;

    /** @var array */
    private $_columnNames;

    /** @var array */
    private $_columnTypes;

    /** @var array */
    private $_originalColumnTypes;

    /** @var array */
    private $_columnCollations;

    /** @var array */
    private $_columnNullFlags;

    /** @var bool Whether a geometry column is present */
    private $_geomColumnFlag;

    /** @var array Foreign keys */
    private $_foreigners;

    /**
     * @param Response          $response A Response instance.
     * @param DatabaseInterface $dbi      A DatabaseInterface instance.
     * @param Template          $template A Template instance.
     * @param string            $db       Database name.
     * @param string            $table    Table name.
     * @param Search            $search   A Search instance.
     * @param Relation          $relation A Relation instance.
     */
    public function __construct($response, $dbi, Template $template, $db, $table, Search $search, Relation $relation)
    {
        parent::__construct($response, $dbi, $template, $db, $table);
        $this->search = $search;
        $this->relation = $relation;
        $this->_columnNames = [];
        $this->_columnTypes = [];
        $this->_originalColumnTypes = [];
        $this->_columnCollations = [];
        $this->_columnNullFlags = [];
        $this->_geomColumnFlag = false;
        $this->_foreigners = [];
        $this->loadTableInfo();
    }

    public function index(): void
    {
        global $goto;

        Common::table();

        $this->addScriptFiles([
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
        ]);

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

        /**
         * Handle the input criteria and generate the query result
         * Form for displaying query results
         */
        if (! isset($_POST['zoom_submit'])
            || $_POST['criteriaColumnNames'][0] === 'pma_null'
            || $_POST['criteriaColumnNames'][1] === 'pma_null'
            || $_POST['criteriaColumnNames'][0] == $_POST['criteriaColumnNames'][1]
        ) {
            return;
        }

        if (! isset($goto)) {
            $goto = Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabTable'],
                'table'
            );
        }
        $this->zoomSubmitAction($dataLabel, $goto);
    }

    /**
     * Gets all the columns of a table along with their types, collations
     * and whether null or not.
     */
    private function loadTableInfo(): void
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
                = ! empty($row['Collation']) && $row['Collation'] !== 'NULL'
                ? $row['Collation']
                : '';
        } // end for

        // Retrieve foreign keys
        $this->_foreigners = $this->relation->getForeigners($this->db, $this->table);
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

        if (! isset($goto)) {
            $goto = Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabTable'],
                'table'
            );
        }

        $column_names = $this->_columnNames;
        $criteria_column_names = $_POST['criteriaColumnNames'] ?? null;
        $keys = [];
        for ($i = 0; $i < 4; $i++) {
            if (! isset($criteria_column_names[$i])) {
                continue;
            }

            if ($criteria_column_names[$i] === 'pma_null') {
                continue;
            }

            $keys[$criteria_column_names[$i]] = array_search($criteria_column_names[$i], $column_names);
        }

        $this->render('table/zoom_search/index', [
            'db' => $this->db,
            'table' => $this->table,
            'goto' => $goto,
            'self' => $this,
            'geom_column_flag' => $this->_geomColumnFlag,
            'column_names' => $column_names,
            'data_label' => $dataLabel,
            'keys' => $keys,
            'criteria_column_names' => $criteria_column_names,
            'criteria_column_types' => $_POST['criteriaColumnTypes'] ?? null,
            'max_plot_limit' => ! empty($_POST['maxPlotLimit'])
                ? intval($_POST['maxPlotLimit'])
                : intval($GLOBALS['cfg']['maxRowPlotLimit']),
        ]);
    }

    /**
     * Get data row action
     *
     * @return void
     */
    public function getDataRowAction()
    {
        $extra_data = [];
        $row_info_query = 'SELECT * FROM ' . Util::backquote($_POST['db']) . '.'
            . Util::backquote($_POST['table']) . ' WHERE ' . $_POST['where_clause'];
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
                if ($fields_meta[$i]->type === 'bit') {
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
     * Change table info action
     *
     * @return void
     */
    public function changeTableInfoAction()
    {
        $field = $_POST['field'];
        if ($field === 'pma_null') {
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
                $result,
                count($this->_columnNames),
                $fields_meta,
                $tmpRow,
                true
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

        $this->render('table/zoom_search/result_form', [
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
        ]);
    }

    /**
     * Provides a column's type, collation, operators list, and criteria value
     * to display in table search form
     *
     * @param int $search_index Row number in table search form
     * @param int $column_index Column index in ColumnNames array
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
        $htmlAttributes = '';
        if (in_array($cleanType, $this->dbi->types->getIntegerTypes())) {
            $extractedColumnspec = Util::extractColumnSpec(
                $this->_originalColumnTypes[$column_index]
            );
            $is_unsigned = $extractedColumnspec['unsigned'];
            $minMaxValues = $this->dbi->types->getIntegerRange(
                $cleanType,
                ! $is_unsigned
            );
            $htmlAttributes = 'data-min="' . $minMaxValues[0] . '" '
                            . 'data-max="' . $minMaxValues[1] . '"';
            $type = 'INT';
        }

        $htmlAttributes .= ' onchange="return '
                        . 'verifyAfterSearchFieldChange(' . $column_index . ', \'#zoom_search_form\')"';

        $value = $this->template->render('table/search/input_box', [
            'str' => '',
            'column_type' => (string) $type,
            'html_attributes' => $htmlAttributes,
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
