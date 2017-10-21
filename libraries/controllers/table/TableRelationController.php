<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\TableRelationController
 *
 * @package PMA
 */

namespace PMA\libraries\controllers\table;

require_once 'libraries/index.lib.php';

use PMA\libraries\controllers\TableController;
use PMA\libraries\DatabaseInterface;
use PMA\libraries\Index;
use PMA\libraries\Table;
use PMA\libraries\Template;
use PMA\libraries\Util;

/**
 * Handles table relation logic
 *
 * @package PhpMyAdmin
 */
class TableRelationController extends TableController
{
    /**
     * @var array $options_array
     */
    protected $options_array;

    /**
     * @var array $cfgRelation
     */
    protected $cfgRelation;

    /**
     * @var array $existrel
     */
    protected $existrel;

    /**
     * @var string $tbl_storage_engine
     */
    protected $tbl_storage_engine;

    /**
     * @var array $existrel_foreign
     */
    protected $existrel_foreign;

    /**
     * @var Table $udp_query
     */
    protected $upd_query;

    /**
     * Constructor
     *
     * @param array  $options_array      Options
     * @param array  $cfgRelation        Config relation
     * @param string $tbl_storage_engine Table storage engine
     * @param array  $existrel           Relations
     * @param array  $existrel_foreign   External relations
     * @param string $upd_query          Update query
     */
    public function __construct($options_array, $cfgRelation, $tbl_storage_engine,
        $existrel, $existrel_foreign, $upd_query
    ) {
        parent::__construct();

        $this->options_array = $options_array;
        $this->cfgRelation = $cfgRelation;
        $this->tbl_storage_engine = $tbl_storage_engine;
        $this->existrel = $existrel;
        $this->existrel_foreign = $existrel_foreign;
        $this->upd_query = $upd_query;
    }

    /**
     * Index
     *
     * @return void
     */
    public function indexAction()
    {
        // Send table of column names to populate corresponding dropdowns depending
        // on the current selection
        if (isset($_REQUEST['getDropdownValues'])
            && $_REQUEST['getDropdownValues'] === 'true'
        ) {
            // if both db and table are selected
            if (isset($_REQUEST['foreignTable'])) {
                $this->getDropdownValueForTableAction();
            } else { // if only the db is selected
                $this->getDropdownValueForDbAction();
            }
            return;
        }

        $this->response->getHeader()->getScripts()->addFiles(
            array(
                'tbl_relation.js',
                'indexes.js'
            )
        );

        // Gets tables information
        include_once 'libraries/tbl_info.inc.php';

        // updates for Internal relations
        if (isset($_POST['destination_db']) && $this->cfgRelation['relwork']) {
            $this->updateForInternalRelationAction();
        }

        // updates for foreign keys
        if (isset($_POST['destination_foreign_db'])) {
            $this->updateForForeignKeysAction();
        }

        // Updates for display field
        if ($this->cfgRelation['displaywork'] && isset($_POST['display_field'])) {
            $this->updateForDisplayField();
        }

        // If we did an update, refresh our data
        if (isset($_POST['destination_db']) && $this->cfgRelation['relwork']) {
            $this->existrel = PMA_getForeigners(
                $this->db, $this->table, '', 'internal'
            );
        }
        if (isset($_POST['destination_foreign_db'])
            && Util::isForeignKeySupported($this->tbl_storage_engine)
        ) {
            $this->existrel_foreign = PMA_getForeigners(
                $this->db, $this->table, '', 'foreign'
            );
        }

        // display secondary level tabs if necessary
        $engine = $this->dbi->getTable($this->db, $this->table)
            ->getStatusInfo('ENGINE');

        $this->response->addHTML(
            Template::get('table/secondary_tabs')->render(
                array(
                    'url_params' => array(
                        'db' => $GLOBALS['db'],
                        'table' => $GLOBALS['table']
                    ),
                    'engine' => $engine
                )
            )
        );
        $this->response->addHTML('<div id="structure_content">');

        /**
         * Dialog
         */
        // Now find out the columns of our $table
        // need to use DatabaseInterface::QUERY_STORE with $this->dbi->numRows()
        // in mysqli
        $columns = $this->dbi->getColumns($this->db, $this->table);

        // common form
        $this->response->addHTML(
            Template::get('table/relation/common_form')->render(
                array(
                    'db' => $this->db,
                    'table' => $this->table,
                    'columns' => $columns,
                    'cfgRelation' => $this->cfgRelation,
                    'tbl_storage_engine' => $this->tbl_storage_engine,
                    'existrel' => isset($this->existrel) ? $this->existrel : array(),
                    'existrel_foreign' => isset($this->existrel_foreign)
                        ? $this->existrel_foreign['foreign_keys_data'] : array(),
                    'options_array' => $this->options_array
                )
            )
        );

        if (Util::isForeignKeySupported($this->tbl_storage_engine)) {
            $this->response->addHTML(PMA_getHtmlForDisplayIndexes());
        }
        $this->response->addHTML('</div>');
    }

    /**
     * Update for display field
     *
     * @return void
     */
    public function updateForDisplayField()
    {
        if ($this->upd_query->updateDisplayField(
            $_POST['display_field'], $this->cfgRelation
        )
        ) {
            $this->response->addHTML(
                Util::getMessage(
                    __('Display column was successfully updated.'),
                    '', 'success'
                )
            );
        }
    }

    /**
     * Update for FK
     *
     * @return void
     */
    public function updateForForeignKeysAction()
    {
        $multi_edit_columns_name = isset($_REQUEST['foreign_key_fields_name'])
            ? $_REQUEST['foreign_key_fields_name']
            : null;

        // (for now, one index name only; we keep the definitions if the
        // foreign db is not the same)
        list($html, $preview_sql_data, $display_query, $seen_error)
            = $this->upd_query->updateForeignKeys(
                $_POST['destination_foreign_db'],
                $multi_edit_columns_name, $_POST['destination_foreign_table'],
                $_POST['destination_foreign_column'], $this->options_array,
                $this->table,
                isset($this->existrel_foreign)
                ? $this->existrel_foreign['foreign_keys_data']
                : null
            );
        $this->response->addHTML($html);

        // If there is a request for SQL previewing.
        if (isset($_REQUEST['preview_sql'])) {
            PMA_previewSQL($preview_sql_data);
        }

        if (!empty($display_query) && !$seen_error) {
            $GLOBALS['display_query'] = $display_query;
            $this->response->addHTML(
                Util::getMessage(
                    __('Your SQL query has been executed successfully.'),
                    null, 'success'
                )
            );
        }
    }

    /**
     * Update for internal relation
     *
     * @return void
     */
    public function updateForInternalRelationAction()
    {
        $multi_edit_columns_name = isset($_REQUEST['fields_name'])
            ? $_REQUEST['fields_name']
            : null;

        if ($this->upd_query->updateInternalRelations(
            $multi_edit_columns_name,
            $_POST['destination_db'],
            $_POST['destination_table'],
            $_POST['destination_column'],
            $this->cfgRelation,
            isset($this->existrel) ? $this->existrel : null
        )
        ) {
            $this->response->addHTML(
                Util::getMessage(
                    __('Internal relationships were successfully updated.'),
                    '', 'success'
                )
            );
        }
    }

    /**
     * Send table columns for foreign table dropdown
     *
     * @return void
     *
     */
    public function getDropdownValueForTableAction()
    {
        $foreignTable = $_REQUEST['foreignTable'];
        $table_obj = $this->dbi->getTable($_REQUEST['foreignDb'], $foreignTable);
        // Since views do not have keys defined on them provide the full list of
        // columns
        if ($table_obj->isView()) {
            $columnList = $table_obj->getColumns(false, false);
        } else {
            $columnList = $table_obj->getIndexedColumns(false, false);
        }
        $columns = array();
        foreach ($columnList as $column) {
            $columns[] = htmlspecialchars($column);
        }
        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($columns, 'strnatcasecmp');
        }
        $this->response->addJSON('columns', $columns);

        // @todo should be: $server->db($db)->table($table)->primary()
        $primary = Index::getPrimary($foreignTable, $_REQUEST['foreignDb']);
        if (false === $primary) {
            return;
        }

        $this->response->addJSON('primary', array_keys($primary->getColumns()));
    }

    /**
     * Send database selection values for dropdown
     *
     * @return void
     *
     */
    public function getDropdownValueForDbAction()
    {
        $tables = array();
        $foreign = isset($_REQUEST['foreign']) && $_REQUEST['foreign'] === 'true';

        if ($foreign) {
            $query = 'SHOW TABLE STATUS FROM '
                . Util::backquote($_REQUEST['foreignDb']);
            $tables_rs = $this->dbi->query(
                $query,
                null,
                DatabaseInterface::QUERY_STORE
            );

            while ($row = $this->dbi->fetchArray($tables_rs)) {
                if (isset($row['Engine'])
                    &&  mb_strtoupper($row['Engine']) == $this->tbl_storage_engine
                ) {
                    $tables[] = htmlspecialchars($row['Name']);
                }
            }
        } else {
            $query = 'SHOW TABLES FROM '
                . Util::backquote($_REQUEST['foreignDb']);
            $tables_rs = $this->dbi->query(
                $query,
                null,
                DatabaseInterface::QUERY_STORE
            );
            while ($row = $this->dbi->fetchArray($tables_rs)) {
                $tables[] = htmlspecialchars($row[0]);
            }
        }
        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($tables, 'strnatcasecmp');
        }
        $this->response->addJSON('tables', $tables);
    }
}
