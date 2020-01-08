<?php
/**
 * @package PhpMyAdmin\Controllers\Table
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Common;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * Displays index edit/creation form and handles it.
 *
 * @package PhpMyAdmin\Controllers\Table
 */
class IndexesController extends AbstractController
{
    /**
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template object
     * @param string            $db       Database name
     * @param string            $table    Table name
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);
    }

    /**
     * @return void
     */
    public function index(): void
    {
        if (! isset($_POST['create_edit_table'])) {
            Common::table();
        }
        if (isset($_POST['index'])) {
            if (is_array($_POST['index'])) {
                // coming already from form
                $index = new Index($_POST['index']);
            } else {
                $index = $this->dbi->getTable($this->db, $this->table)->getIndex($_POST['index']);
            }
        } else {
            $index = new Index();
        }

        if (isset($_POST['do_save_data'])) {
            $this->doSaveData($index);
            return;
        }

        $this->displayForm($index);
    }

    /**
     * Display the form to edit/create an index
     *
     * @param Index $index An Index instance.
     *
     * @return void
     */
    public function displayForm(Index $index): void
    {
        $this->dbi->selectDb($GLOBALS['db']);
        $add_fields = 0;
        if (isset($_POST['index']) && is_array($_POST['index'])) {
            // coming already from form
            if (isset($_POST['index']['columns']['names'])) {
                $add_fields = count($_POST['index']['columns']['names'])
                    - $index->getColumnCount();
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
            $index->set($index_params);
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
                'index' => $index,
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
     * and moves back to /table/sql
     *
     * @param Index $index An Index instance.
     *
     * @return void
     */
    public function doSaveData(Index $index): void
    {
        global $containerBuilder;

        $error = false;

        $sql_query = $this->dbi->getTable($this->db, $this->table)
            ->getSqlQueryForIndexCreateOrEdit($index, $error);

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
                    Generator::getMessage($message, $sql_query, 'success')
                );
                $this->response->addJSON(
                    'index_table',
                    Index::getHtmlForIndexes(
                        $this->table,
                        $this->db
                    )
                );
            } else {
                /** @var StructureController $controller */
                $controller = $containerBuilder->get(StructureController::class);
                $controller->index();
            }
        } else {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $error);
        }
    }
}
