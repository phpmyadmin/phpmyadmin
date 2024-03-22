<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function is_array;
use function is_numeric;
use function json_decode;
use function min;

/**
 * Displays index edit/creation form and handles it.
 */
class IndexesController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private Indexes $indexes,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        if (! isset($_POST['create_edit_table'])) {
            if (! $this->checkParameters(['db', 'table'])) {
                return;
            }

            $GLOBALS['urlParams'] = ['db' => Current::$database, 'table' => Current::$table];
            $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
                Config::getInstance()->settings['DefaultTabTable'],
                'table',
            );
            $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

            $databaseName = DatabaseName::tryFrom($request->getParam('db'));
            if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No databases selected.')));

                    return;
                }

                $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

                return;
            }

            $tableName = TableName::tryFrom($request->getParam('table'));
            if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No table selected.')));

                    return;
                }

                $this->redirect('/', ['reload' => true, 'message' => __('No table selected.')]);

                return;
            }
        }

        if (isset($_POST['index'])) {
            if (is_array($_POST['index'])) {
                // coming already from form
                $index = new Index($_POST['index']);
            } else {
                $index = $this->dbi->getTable(Current::$database, Current::$table)->getIndex($_POST['index']);
            }
        } else {
            $index = new Index();
        }

        if (isset($_POST['do_save_data'])) {
            $previewSql = $request->hasBodyParam('preview_sql');
            if (isset($_POST['old_index'])) {
                $oldIndex = is_array($_POST['old_index']) ? $_POST['old_index']['Key_name'] : $_POST['old_index'];
            } else {
                $oldIndex = null;
            }

            $sqlQuery = $this->indexes->getSqlQueryForIndexCreateOrEdit(
                $oldIndex,
                $index,
                Current::$database,
                Current::$table,
            );

            // If there is a request for SQL previewing.
            if ($previewSql) {
                $this->response->addJSON(
                    'sql_data',
                    $this->template->render('preview_sql', ['query_data' => $sqlQuery]),
                );

                return;
            }

            $logicError = $this->indexes->getError();
            if ($logicError instanceof Message) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $logicError);

                return;
            }

            $this->dbi->query($sqlQuery);

            if ($request->isAjax()) {
                $message = Message::success(
                    __('Table %1$s has been altered successfully.'),
                );
                $message->addParam(Current::$table);
                $this->response->addJSON(
                    'message',
                    Generator::getMessage($message, $sqlQuery, 'success'),
                );

                $indexes = Index::getFromTable($this->dbi, Current::$table, Current::$database);
                $indexesDuplicates = Index::findDuplicates(Current::$table, Current::$database);

                $this->response->addJSON(
                    'index_table',
                    $this->template->render('indexes', [
                        'url_params' => ['db' => Current::$database, 'table' => Current::$table],
                        'indexes' => $indexes,
                        'indexes_duplicates' => $indexesDuplicates,
                    ]),
                );

                return;
            }

            /** @var StructureController $controller */
            $controller = ContainerBuilder::getContainer()->get(StructureController::class);
            $controller($request);

            return;
        }

        $this->displayForm($index);
    }

    /**
     * Display the form to edit/create an index
     *
     * @param Index $index An Index instance.
     */
    private function displayForm(Index $index): void
    {
        $this->dbi->selectDb(Current::$database);
        $addFields = 0;
        if (isset($_POST['index']) && is_array($_POST['index'])) {
            // coming already from form
            if (isset($_POST['index']['columns']['names'])) {
                $addFields = count($_POST['index']['columns']['names'])
                    - $index->getColumnCount();
            }

            if (isset($_POST['add_fields'])) {
                $addFields += $_POST['added_fields'];
            }
        } elseif (isset($_POST['create_index'])) {
            /**
             * In most cases, an index may consist of up to 16 columns, so add an initial limit.
             * More columns could be added later if necessary.
             *
             * @see https://dev.mysql.com/doc/refman/5.6/en/multiple-column-indexes.html "up to 16 columns"
             * @see https://mariadb.com/kb/en/innodb-limitations/#limitations-on-schema "maximum of 16 columns"
             * @see https://mariadb.com/kb/en/myisam-overview/#myisam-features "Maximum of 32 columns per index"
             */
            $addFields = 1;
            if (is_numeric($_POST['added_fields']) && $_POST['added_fields'] >= 2) {
                $addFields = min((int) $_POST['added_fields'], 16);
            }
        }

        // Get fields and stores their name/type
        if (isset($_POST['create_edit_table'])) {
            $fields = json_decode($_POST['columns'], true);
            $indexParams = ['Non_unique' => $_POST['index']['Index_choice'] !== 'UNIQUE'];
            $index->set($indexParams);
            $addFields = count($fields);
        } else {
            $fields = $this->dbi->getTable(Current::$database, Current::$table)
                ->getNameAndTypeOfTheColumns();
        }

        $formParams = ['db' => Current::$database, 'table' => Current::$table];

        if (isset($_POST['create_index'])) {
            $formParams['create_index'] = 1;
        } elseif (isset($_POST['old_index'])) {
            $formParams['old_index'] = $_POST['old_index'];
        } elseif (isset($_POST['index'])) {
            $formParams['old_index'] = $_POST['index'];
        }

        $this->render('table/index_form', [
            'fields' => $fields,
            'index' => $index,
            'form_params' => $formParams,
            'add_fields' => $addFields,
            'create_edit_table' => isset($_POST['create_edit_table']),
            'default_sliders_state' => Config::getInstance()->settings['InitialSlidersState'],
            'is_from_nav' => isset($_POST['is_from_nav']),
        ]);
    }
}
