<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Table\RelationController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Index;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Handles table relation logic
 *
 * @package PhpMyAdmin\Controllers
 */
class RelationController extends AbstractController
{
    /**
     * @var array
     */
    protected $options_array;

    /**
     * @var array
     */
    protected $cfgRelation;

    /**
     * @var array
     */
    protected $existrel;

    /**
     * @var string
     */
    protected $tbl_storage_engine;

    /**
     * @var array
     */
    protected $existrel_foreign;

    /**
     * @var Table
     */
    protected $upd_query;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * Constructor
     *
     * @param Response          $response           Response object
     * @param DatabaseInterface $dbi                DatabaseInterface object
     * @param Template          $template           Template object
     * @param string            $db                 Database name
     * @param string            $table              Table name
     * @param array|null        $options_array      Options
     * @param array|null        $cfgRelation        Config relation
     * @param string            $tbl_storage_engine Table storage engine
     * @param array|null        $existrel           Relations
     * @param array|null        $existrel_foreign   External relations
     * @param Table             $upd_query          Update query
     * @param Relation          $relation           Relation instance
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table,
        $options_array,
        $cfgRelation,
        $tbl_storage_engine,
        $existrel,
        $existrel_foreign,
        $upd_query,
        Relation $relation
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);

        $this->options_array = $options_array;
        $this->cfgRelation = $cfgRelation;
        $this->tbl_storage_engine = $tbl_storage_engine;
        $this->existrel = $existrel;
        $this->existrel_foreign = $existrel_foreign;
        $this->upd_query = $upd_query;
        $this->relation = $relation;
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
        if (isset($_POST['getDropdownValues'])
            && $_POST['getDropdownValues'] === 'true'
        ) {
            // if both db and table are selected
            if (isset($_POST['foreignTable'])) {
                $this->getDropdownValueForTableAction();
            } else { // if only the db is selected
                $this->getDropdownValueForDbAction();
            }
            return;
        }

        $this->response->getHeader()->getScripts()->addFiles(
            [
                'table/relation.js',
                'indexes.js',
            ]
        );

        // Set the database
        $this->dbi->selectDb($this->db);

        // updates for Internal relations
        if (isset($_POST['destination_db']) && $this->cfgRelation['relwork']) {
            $this->updateForInternalRelationAction();
        }

        // updates for foreign keys
        $this->updateForForeignKeysAction();

        // Updates for display field
        if ($this->cfgRelation['displaywork'] && isset($_POST['display_field'])) {
            $this->updateForDisplayField();
        }

        // If we did an update, refresh our data
        if (isset($_POST['destination_db']) && $this->cfgRelation['relwork']) {
            $this->existrel = $this->relation->getForeigners(
                $this->db,
                $this->table,
                '',
                'internal'
            );
        }
        if (isset($_POST['destination_foreign_db'])
            && Util::isForeignKeySupported($this->tbl_storage_engine)
        ) {
            $this->existrel_foreign = $this->relation->getForeigners(
                $this->db,
                $this->table,
                '',
                'foreign'
            );
        }

        /**
         * Dialog
         */
        // Now find out the columns of our $table
        // need to use DatabaseInterface::QUERY_STORE with $this->dbi->numRows()
        // in mysqli
        $columns = $this->dbi->getColumns($this->db, $this->table);

        $column_array = [];
        $column_hash_array = [];
        $column_array[''] = '';
        foreach ($columns as $column) {
            if (strtoupper($this->tbl_storage_engine) == 'INNODB'
                || ! empty($column['Key'])
            ) {
                $column_array[$column['Field']] = $column['Field'];
                $column_hash_array[$column['Field']] = md5($column['Field']);
            }
        }
        if ($GLOBALS['cfg']['NaturalOrder']) {
            uksort($column_array, 'strnatcasecmp');
        }

        // common form
        $engine = $this->dbi->getTable($this->db, $this->table)->getStorageEngine();
        $foreignKeySupported = Util::isForeignKeySupported($this->tbl_storage_engine);
        $this->response->addHTML(
            $this->template->render('table/relation/common_form', [
                'is_foreign_key_supported' => Util::isForeignKeySupported($engine),
                'db' => $this->db,
                'table' => $this->table,
                'cfg_relation' => $this->cfgRelation,
                'tbl_storage_engine' => $this->tbl_storage_engine,
                'existrel' => isset($this->existrel) ? $this->existrel : [],
                'existrel_foreign' => is_array($this->existrel_foreign) && array_key_exists('foreign_keys_data', $this->existrel_foreign)
                    ? $this->existrel_foreign['foreign_keys_data'] : [],
                'options_array' => $this->options_array,
                'column_array' => $column_array,
                'column_hash_array' => $column_hash_array,
                'save_row' => array_values($columns),
                'url_params' => $GLOBALS['url_params'],
                'databases' => $GLOBALS['dblist']->databases,
                'dbi' => $this->dbi,
                'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
                'foreignKeySupported' => $foreignKeySupported,
                'displayIndexesHtml' => $foreignKeySupported ? Index::getHtmlForDisplayIndexes() : null,
            ])
        );
    }

    /**
     * Update for display field
     *
     * @return void
     */
    public function updateForDisplayField()
    {
        if ($this->upd_query->updateDisplayField(
            $_POST['display_field'],
            $this->cfgRelation
        )
        ) {
            $this->response->addHTML(
                Util::getMessage(
                    __('Display column was successfully updated.'),
                    '',
                    'success'
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
        $multi_edit_columns_name = isset($_POST['foreign_key_fields_name'])
            ? $_POST['foreign_key_fields_name']
            : null;
        $preview_sql_data = '';
        $seen_error = false;

        // (for now, one index name only; we keep the definitions if the
        // foreign db is not the same)
        if (isset($_POST['destination_foreign_db'])
            && isset($_POST['destination_foreign_table'])
            && isset($_POST['destination_foreign_column'])) {
            list($html, $preview_sql_data, $display_query, $seen_error)
                = $this->upd_query->updateForeignKeys(
                    $_POST['destination_foreign_db'],
                    $multi_edit_columns_name,
                    $_POST['destination_foreign_table'],
                    $_POST['destination_foreign_column'],
                    $this->options_array,
                    $this->table,
                    is_array($this->existrel_foreign) && array_key_exists('foreign_keys_data', $this->existrel_foreign)
                    ? $this->existrel_foreign['foreign_keys_data'] : []
                );
            $this->response->addHTML($html);
        }

        // If there is a request for SQL previewing.
        if (isset($_POST['preview_sql'])) {
            Core::previewSQL($preview_sql_data);
        }

        if (! empty($display_query) && ! $seen_error) {
            $GLOBALS['display_query'] = $display_query;
            $this->response->addHTML(
                Util::getMessage(
                    __('Your SQL query has been executed successfully.'),
                    null,
                    'success'
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
        $multi_edit_columns_name = isset($_POST['fields_name'])
            ? $_POST['fields_name']
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
                    '',
                    'success'
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
        $foreignTable = $_POST['foreignTable'];
        $table_obj = $this->dbi->getTable($_POST['foreignDb'], $foreignTable);
        // Since views do not have keys defined on them provide the full list of
        // columns
        if ($table_obj->isView()) {
            $columnList = $table_obj->getColumns(false, false);
        } else {
            $columnList = $table_obj->getIndexedColumns(false, false);
        }
        $columns = [];
        foreach ($columnList as $column) {
            $columns[] = htmlspecialchars($column);
        }
        if ($GLOBALS['cfg']['NaturalOrder']) {
            usort($columns, 'strnatcasecmp');
        }
        $this->response->addJSON('columns', $columns);

        // @todo should be: $server->db($db)->table($table)->primary()
        $primary = Index::getPrimary($foreignTable, $_POST['foreignDb']);
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
        $tables = [];
        $foreign = isset($_POST['foreign']) && $_POST['foreign'] === 'true';

        if ($foreign) {
            $query = 'SHOW TABLE STATUS FROM '
                . Util::backquote($_POST['foreignDb']);
            $tables_rs = $this->dbi->query(
                $query,
                DatabaseInterface::CONNECT_USER,
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
                . Util::backquote($_POST['foreignDb']);
            $tables_rs = $this->dbi->query(
                $query,
                DatabaseInterface::CONNECT_USER,
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
