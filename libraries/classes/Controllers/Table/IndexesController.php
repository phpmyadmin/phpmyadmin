<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Table\IndexesController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Class IndexesController
 *
 * @package PhpMyAdmin\Controllers
 */
class IndexesController extends AbstractController
{
    /**
     * @var Index
     */
    protected $index;

    /**
     * Constructor
     *
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template object
     * @param string            $db       Database name
     * @param string            $table    Table name
     * @param Index             $index    Index object
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table,
        $index
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);

        $this->index = $index;
    }

    /**
     * Index
     *
     * @return void
     */
    public function indexAction()
    {
        if (isset($_POST['do_save_data'])) {
            $this->doSaveDataAction();
            return;
        } // end builds the new index

        $this->displayFormAction();
    }

    /**
     * Display the form to edit/create an index
     *
     * @return void
     */
    public function displayFormAction()
    {
        $this->dbi->selectDb($GLOBALS['db']);
        $add_fields = 0;
        if (isset($_POST['index']) && is_array($_POST['index'])) {
            // coming already from form
            if (isset($_POST['index']['columns']['names'])) {
                $add_fields = count($_POST['index']['columns']['names'])
                    - $this->index->getColumnCount();
            }
            if (isset($_POST['add_fields'])) {
                $add_fields += $_POST['added_fields'];
            }
        } elseif (isset($_POST['create_index'])) {
            $add_fields = $_POST['added_fields'];
        } // end preparing form values

        // Get fields and stores their name/type
        if (isset($_POST['create_edit_table'])) {
            $fields = json_decode($_POST['columns'], true);
            $index_params = [
                'Non_unique' => $_POST['index']['Index_choice'] == 'UNIQUE'
                    ? '0' : '1',
            ];
            $this->index->set($index_params);
            $add_fields = count($fields);
        } else {
            $fields = $this->dbi->getTable($this->db, $this->table)
                ->getNameAndTypeOfTheColumns();
        }

        $form_params = [
            'db' => $this->db,
            'table' => $this->table,
        ];

        if (isset($_POST['create_index'])) {
            $form_params['create_index'] = 1;
        } elseif (isset($_POST['old_index'])) {
            $form_params['old_index'] = $_POST['old_index'];
        } elseif (isset($_POST['index'])) {
            $form_params['old_index'] = $_POST['index'];
        }

        $this->response->getHeader()->getScripts()->addFile('indexes.js');

        $this->response->addHTML(
            $this->template->render('table/index_form', [
                'fields' => $fields,
                'index' => $this->index,
                'form_params' => $form_params,
                'add_fields' => $add_fields,
                'create_edit_table' => isset($_POST['create_edit_table']),
                'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
            ])
        );
    }

    /**
     * Process the data from the edit/create index form,
     * run the query to build the new index
     * and moves back to "tbl_sql.php"
     *
     * @return void
     */
    public function doSaveDataAction()
    {
        $error = false;

        $sql_query = $this->dbi->getTable($this->db, $this->table)
            ->getSqlQueryForIndexCreateOrEdit($this->index, $error);

        // If there is a request for SQL previewing.
        if (isset($_POST['preview_sql'])) {
            $this->response->addJSON(
                'sql_data',
                $this->template->render('preview_sql', ['query_data' => $sql_query])
            );
        } elseif (! $error) {
            $this->dbi->query($sql_query);
            $response = Response::getInstance();
            if ($response->isAjax()) {
                $message = Message::success(
                    __('Table %1$s has been altered successfully.')
                );
                $message->addParam($this->table);
                $this->response->addJSON(
                    'message',
                    Util::getMessage($message, $sql_query, 'success')
                );
                $this->response->addJSON(
                    'index_table',
                    Index::getHtmlForIndexes(
                        $this->table,
                        $this->db
                    )
                );
            } else {
                include ROOT_PATH . 'tbl_structure.php';
            }
        } else {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $error);
        }
    }
}
