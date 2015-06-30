<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\TableIndexesController
 *
 * @package PMA
 */

namespace PMA\Controllers\Table;

use PMA\Controllers\TableController;
use PMA_Index;
use PMA_Message;
use PMA_Response;
use PMA\Template;
use PMA_Util;

require_once 'libraries/common.inc.php';
require_once 'libraries/Index.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Index.class.php';
require_once 'libraries/controllers/TableController.class.php';
require_once 'libraries/Template.class.php';

class TableIndexesController extends TableController
{
    /**
     * @var string $db
     */
    protected $db;

    /**
     * @var string $table
     */
    protected $table;

    /**
     * @var PMA_Index $index
     */
    protected $index;

    function __construct()
    {
        parent::__construct();

        $this->db = $GLOBALS['db'];
        $this->table = $GLOBALS['table'];

        /**
         * Extract values for common work
         * @todo Extract common files
         */
        $db = $this->db;
        $table = $this->table;

        if (!isset($_REQUEST['create_edit_table'])) {
            include_once 'libraries/tbl_common.inc.php';
        }

        if (isset($_REQUEST['index'])) {
            if (is_array($_REQUEST['index'])) {
                // coming already from form
                $this->index = new PMA_Index($_REQUEST['index']);
            } else {
                $this->index = $this->dbi->getTable($this->db, $this->table)
                    ->getIndex($_REQUEST['index']);
            }
        } else {
            $this->index = new PMA_Index;
        }
    }

    public function indexAction()
    {
        if (isset($_REQUEST['do_save_data'])) {
            $this->doSaveDataAction();
            return;
        } // end builds the new index

        $this->displayFormAction();
    }

    /**
     * Display the form to edit/create an index
     */
    public function displayFormAction()
    {
        include_once 'libraries/tbl_info.inc.php';

        $add_fields = 0;
        if (isset($_REQUEST['index']) && is_array($_REQUEST['index'])) {
            // coming already from form
            if (isset($_REQUEST['index']['columns']['names'])) {
                $add_fields = count($_REQUEST['index']['columns']['names'])
                    - $this->index->getColumnCount();
            }
            if (isset($_REQUEST['add_fields'])) {
                $add_fields += $_REQUEST['added_fields'];
            }
        } elseif (isset($_REQUEST['create_index'])) {
            $add_fields = $_REQUEST['added_fields'];
        } // end preparing form values

        // Get fields and stores their name/type
        if (isset($_REQUEST['create_edit_table'])) {
            $fields = json_decode($_REQUEST['columns'], true);
            $index_params = array(
                'Non_unique' => ($_REQUEST['index']['Index_choice'] == 'UNIQUE') ? '0' : '1'
            );
            $this->index->set($index_params);
            $add_fields = count($fields);
        } else {
            $fields = $this->dbi->getTable($this->db, $this->table)->getNameAndTypeOfTheColumns();
        }

        $form_params = array(
            'db' => $this->db,
            'table' => $this->table,
        );

        if (isset($_REQUEST['create_index'])) {
            $form_params['create_index'] = 1;
        } elseif (isset($_REQUEST['old_index'])) {
            $form_params['old_index'] = $_REQUEST['old_index'];
        } elseif (isset($_REQUEST['index'])) {
            $form_params['old_index'] = $_REQUEST['index'];
        }

        $this->response->getHeader()->getScripts()->addFile('indexes.js');

        $this->response->addHTML(Template::get('index_form')
            ->render(array(
                'fields' => $fields,
                'index' => $this->index,
                'form_params' => $form_params,
                'add_fields' => $add_fields
            ))
        );
    }

    /**
     * Process the data from the edit/create index form,
     * run the query to build the new index
     * and moves back to "tbl_sql.php"
     */
    public function doSaveDataAction()
    {
        $error = false;

        $sql_query = $this->dbi->getTable($this->db, $this->table)
            ->getSqlQueryForIndexCreateOrEdit($this->index, $error);

        // If there is a request for SQL previewing.
        if (isset($_REQUEST['preview_sql'])) {

            PMA_Response::getInstance()->addJSON(
                'sql_data',
                Template::get('preview_sql')
                    ->render(
                        array(
                            'query_data' => $sql_query
                        )
                    )
            );
        } elseif (!$error) {

            $this->dbi->query($sql_query);
            if ($GLOBALS['is_ajax_request'] == true) {
                $message = PMA_Message::success(
                    __('Table %1$s has been altered successfully.')
                );
                $message->addParam($this->table);
                $response = PMA_Response::getInstance();
                $response->addJSON(
                    'message', PMA_Util::getMessage($message, $sql_query, 'success')
                );
                $response->addJSON(
                    'index_table', PMA_Index::getHtmlForIndexes(
                    $this->table, $this->db
                )
                );
            } else {
                include 'tbl_structure.php';
            }
        } else {
            $response = PMA_Response::getInstance();
            $response->isSuccess(false);
            $response->addJSON('message', $error);
        }
    }
}
