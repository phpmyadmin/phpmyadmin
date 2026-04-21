<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;

#[Route('/table/structure/copy-structure', ['POST'])]
final readonly class CopyStructureController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private DatabaseInterface $dbi,
        private DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (Current::$database === '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No databases selected.')));

            return $this->response->response();
        }

        if (Current::$table === '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No table selected.')));

            return $this->response->response();
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No databases selected.')));

            return $this->response->response();
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No table selected.')));

            return $this->response->response();
        }

        $object = $this->dbi->getTable($databaseName->getName(), $tableName->getName());
        $this->response->addJSON('sql', $object->showCreate());

        return $this->response->response();
    }
}
