<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Common;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Table\Search;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use function in_array;
use function intval;
use function mb_strtolower;
use function md5;
use function preg_match;
use function preg_replace;
use function str_ireplace;
use function str_replace;
use function strncasecmp;

/**
 * Handles table search tab.
 *
 * Display table search form, create SQL query from form data
 * and call Sql::executeQueryAndSendQueryResponse() to execute it.
 */
class SearchController extends AbstractController
{
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
     * @var bool
     */
    private $_geomColumnFlag;
    /**
     * Foreign Keys
     *
     * @access private
     * @var array
     */
    private $_foreigners;

    /** @var Search */
    private $search;

    /** @var Relation */
    private $relation;

    /**
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template object
     * @param string            $db       Database name
     * @param string            $table    Table name
     * @param Search            $search   A Search instance.
     * @param Relation          $relation Relation instance
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table,
        Search $search,
        Relation $relation
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);
        $this->search = $search;
        $this->relation = $relation;
        $this->_columnNames = [];
        $this->_columnNullFlags = [];
        $this->_columnTypes = [];
        $this->_columnCollations = [];
        $this->_geomColumnFlag = false;
        $this->_foreigners = [];
        $this->_loadTableInfo();
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
     */
    public function index(): void
    {
        Common::table();

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFiles([
            'makegrid.js',
            'sql.js',
            'table/select.js',
            'table/change.js',
            'vendor/jquery/jquery.uitablefilter.js',
            'gis_data_editor.js',
        ]);

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
     */
    public function displaySelectionFormAction(): void
    {
        global $goto, $cfg;

        if (! isset($goto)) {
            $goto = Util::getScriptNameForOption(
                $cfg['DefaultTabTable'],
                'table'
            );
        }

        $this->response->addHTML(
            $this->template->render('table/search/index', [
                'db' => $this->db,
                'table' => $this->table,
                'goto' => $goto,
                'self' => $this,
                'geom_column_flag' => $this->_geomColumnFlag,
                'column_names' => $this->_columnNames,
                'column_types' => $this->_columnTypes,
                'column_collations' => $this->_columnCollations,
                'default_sliders_state' => $cfg['InitialSlidersState'],
                'max_rows' => intval($cfg['MaxRows']),
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
