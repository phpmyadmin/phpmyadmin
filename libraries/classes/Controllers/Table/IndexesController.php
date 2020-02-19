<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function count;
use function is_array;
use function json_decode;

/**
 * Displays index edit/creation form and handles it.
 */
class IndexesController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param string            $db       Database name.
     * @param string            $table    Table name.
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, $table, $dbi)
    {
        parent::__construct($response, $template, $db, $table);
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        global $db, $table, $url_params, $cfg, $err_url;

        if (! isset($_POST['create_edit_table'])) {
            Util::checkParameters(['db', 'table']);

            $url_params = ['db' => $db, 'table' => $table];
            $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $err_url .= Url::getCommon($url_params, '&');

            DbTableExists::check();
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
            $this->doSaveData($index, false);

            return;
        }

        $this->displayForm($index);
    }

    public function indexRename(): void
    {
        global $db, $table, $url_params, $cfg, $err_url;

        if (! isset($_POST['create_edit_table'])) {
            Util::checkParameters(['db', 'table']);

            $url_params = ['db' => $db, 'table' => $table];
            $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
            $err_url .= Url::getCommon($url_params, '&');

            DbTableExists::check();
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
            $this->doSaveData($index, true);

            return;
        }

        $this->displayRenameForm($index);
    }

    /**
     * Display the rename form to rename an index
     *
     * @param Index $index An Index instance.
     */
    public function displayRenameForm(Index $index): void
    {
        $this->dbi->selectDb($GLOBALS['db']);

        $formParams = [
            'db' => $this->db,
            'table' => $this->table,
        ];

        if (isset($_POST['old_index'])) {
            $formParams['old_index'] = $_POST['old_index'];
        } elseif (isset($_POST['index'])) {
            $formParams['old_index'] = $_POST['index'];
        }

        $this->addScriptFiles(['indexes.js']);

        $this->render('table/index_rename_form', [
            'index' => $index,
            'form_params' => $formParams,
        ]);
    }

    /**
     * Display the form to edit/create an index
     *
     * @param Index $index An Index instance.
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
        }

        // Get fields and stores their name/type
        if (isset($_POST['create_edit_table'])) {
            $fields = json_decode($_POST['columns'], true);
            $index_params = [
                'Non_unique' => $_POST['index']['Index_choice'] === 'UNIQUE'
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

        $this->addScriptFiles(['indexes.js']);

        $this->render('table/index_form', [
            'fields' => $fields,
            'index' => $index,
            'form_params' => $form_params,
            'add_fields' => $add_fields,
            'create_edit_table' => isset($_POST['create_edit_table']),
            'default_sliders_state' => $GLOBALS['cfg']['InitialSlidersState'],
        ]);
    }

    /**
     * Process the data from the edit/create index form,
     * run the query to build the new index
     * and moves back to /table/sql
     *
     * @param Index $index      An Index instance.
     * @param bool  $renameMode Rename the Index mode
     */
    public function doSaveData(Index $index, bool $renameMode): void
    {
        global $containerBuilder;

        $error = false;
        $sql_query = '';
        if ($renameMode && Compatibility::isCompatibleRenameIndex($this->dbi->getVersion())) {
            $oldIndexName = $_POST['old_index'];

            if ($oldIndexName === 'PRIMARY') {
                if ($index->getName() === '') {
                    $index->setName('PRIMARY');
                } elseif ($index->getName() !== 'PRIMARY') {
                    $error = Message::error(
                        __('The name of the primary key must be "PRIMARY"!')
                    );
                }
            }

            $sql_query = QueryGenerator::getSqlQueryForIndexRename(
                $this->db,
                $this->table,
                $oldIndexName,
                $index->getName()
            );
        } else {
            $sql_query = $this->dbi->getTable($this->db, $this->table)
                ->getSqlQueryForIndexCreateOrEdit($index, $error);
        }

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

                $indexes = Index::getFromTable($this->table, $this->db);
                $indexesDuplicates = Index::findDuplicates($this->table, $this->db);

                $this->response->addJSON(
                    'index_table',
                    $this->template->render('indexes', [
                        'url_params' => [
                            'db' => $this->db,
                            'table' => $this->table,
                        ],
                        'indexes' => $indexes,
                        'indexes_duplicates' => $indexesDuplicates,
                    ])
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
