<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivilegesFactory;

use function __;
use function is_array;
use function is_numeric;
use function min;
use function strlen;

/**
 * Displays add field form and handles it.
 */
#[Route('/table/add-field', ['GET', 'POST'])]
final readonly class AddFieldController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Transformations $transformations,
        private Config $config,
        private DatabaseInterface $dbi,
        private ColumnsDefinition $columnsDefinition,
        private DbTableExists $dbTableExists,
        private UserPrivilegesFactory $userPrivilegesFactory,
        private Template $template,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $numberOfFields = $request->getParsedBodyParamAsStringOrNull('num_fields');

        $this->response->addScriptFiles(['table/structure.js']);

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        if (Current::$table === '') {
            return $this->response->missingParameterError('table');
        }

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $cfg = $this->config->settings;

        /**
         * Defines the url to return to in case of error in a sql statement
         */
        $errorUrl = Url::getFromRoute(
            '/table/sql',
            ['db' => Current::$database, 'table' => Current::$table],
        );

        // check number of fields to be created
        if (is_numeric($numberOfFields) && $numberOfFields > 0) {
            $numFields = min(4096, (int) $numberOfFields);
        } else {
            $numFields = 1;
        }

        if (isset($_POST['do_save_data'])) {
            // avoid an incorrect calling of PMA_updateColumns() via
            // /table/structure below
            unset($_POST['do_save_data']);

            $createAddField = new CreateAddField($this->dbi);

            Current::$sqlQuery = $createAddField->getColumnCreationQuery(Current::$table);

            // If there is a request for SQL previewing.
            if (isset($_POST['preview_sql'])) {
                $this->response->addJSON(
                    'sql_data',
                    $this->template->render('components/_preview_sql', ['query_data' => Current::$sqlQuery]),
                );

                return $this->response->response();
            }

            $errorMessageHtml = $createAddField->tryColumnCreationQuery(
                DatabaseName::from(Current::$database),
                Current::$sqlQuery,
            );
            if ($errorMessageHtml !== null) {
                $this->response->addHTML($errorMessageHtml . Generator::getBackUrlHtml($errorUrl));
                $this->response->setRequestStatus(false);

                return $this->response->response();
            }

            // Update comment table for mime types [MIME]
            if (isset($_POST['field_mimetype']) && is_array($_POST['field_mimetype']) && $cfg['BrowseMIME']) {
                foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                    if (! isset($_POST['field_name'][$fieldindex]) || strlen($_POST['field_name'][$fieldindex]) <= 0) {
                        continue;
                    }

                    $this->transformations->setMime(
                        Current::$database,
                        Current::$table,
                        $_POST['field_name'][$fieldindex],
                        $mimetype,
                        $_POST['field_transformation'][$fieldindex],
                        $_POST['field_transformation_options'][$fieldindex],
                        $_POST['field_input_transformation'][$fieldindex],
                        $_POST['field_input_transformation_options'][$fieldindex],
                    );
                }
            }

            // Go back to the structure sub-page
            Current::$message = Message::success(
                __('Table %1$s has been altered successfully.'),
            );
            Current::$message->addParam(Current::$table);
            $this->response->addJSON(
                'message',
                Generator::getMessage(Current::$message, Current::$sqlQuery, MessageType::Success),
            );

            // Give an URL to call and use to appends the structure after the success message
            $this->response->addJSON(
                'structure_refresh_route',
                Url::getFromRoute('/table/structure', [
                    'db' => Current::$database,
                    'table' => Current::$table,
                    'ajax_request' => '1',
                ]),
            );

            return $this->response->response();
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No table selected.')]);
        }

        $this->response->addScriptFiles(['vendor/jquery/jquery.uitablefilter.js']);

        if (Current::$server === 0) {
            return $this->response->missingParameterError('server');
        }

        $templateData = $this->columnsDefinition->displayForm($userPrivileges, '/table/add-field', $numFields);

        $this->response->render('columns_definitions/column_definitions_form', $templateData);

        return $this->response->response();
    }
}
