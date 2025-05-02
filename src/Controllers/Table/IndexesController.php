<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\UrlParams;

use function __;
use function count;
use function is_array;
use function is_numeric;
use function json_decode;
use function min;

/**
 * Displays index edit/creation form and handles it.
 */
final readonly class IndexesController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Template $template,
        private DatabaseInterface $dbi,
        private Indexes $indexes,
        private DbTableExists $dbTableExists,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! isset($_POST['create_edit_table'])) {
            if (Current::$database === '') {
                return $this->response->missingParameterError('db');
            }

            if (Current::$table === '') {
                return $this->response->missingParameterError('table');
            }

            UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];

            $databaseName = DatabaseName::tryFrom($request->getParam('db'));
            if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No databases selected.')));

                    return $this->response->response();
                }

                $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);

                return $this->response->response();
            }

            $tableName = TableName::tryFrom($request->getParam('table'));
            if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
                if ($request->isAjax()) {
                    $this->response->setRequestStatus(false);
                    $this->response->addJSON('message', Message::error(__('No table selected.')));

                    return $this->response->response();
                }

                $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No table selected.')]);

                return $this->response->response();
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

                return $this->response->response();
            }

            $logicError = $this->indexes->getError();
            if ($logicError instanceof Message) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $logicError);

                return $this->response->response();
            }

            $this->dbi->query($sqlQuery);

            if ($request->isAjax()) {
                $message = Message::success(
                    __('Table %1$s has been altered successfully.'),
                );
                $message->addParam(Current::$table);
                $this->response->addJSON(
                    'message',
                    Generator::getMessage($message, $sqlQuery, MessageType::Success),
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

                return $this->response->response();
            }

            /** @var StructureController $controller */
            $controller = ContainerBuilder::getContainer()->get(StructureController::class);

            return $controller($request);
        }

        $this->displayForm($index);

        return $this->response->response();
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

        $this->response->render('table/index_form', [
            'fields' => $fields,
            'index' => $index,
            'form_params' => $formParams,
            'add_fields' => $addFields,
            'create_edit_table' => isset($_POST['create_edit_table']),
            'default_sliders_state' => $this->config->settings['InitialSlidersState'],
            'is_from_nav' => isset($_POST['is_from_nav']),
        ]);
    }
}
