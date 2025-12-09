<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Utils\ForeignKey;

use function __;
use function is_array;

#[Route('/table/delete/confirm', ['POST'])]
final class DeleteConfirmController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $selected = $_POST['rows_to_delete'] ?? null;

        if (! is_array($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No row selected.'));

            return $this->response->response();
        }

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

        $this->response->render('table/delete/confirm', [
            'db' => Current::$database,
            'table' => Current::$table,
            'selected' => $selected,
            'sql_query' => Current::$sqlQuery,
            'is_foreign_key_check' => ForeignKey::isCheckEnabled(),
        ]);

        return $this->response->response();
    }
}
