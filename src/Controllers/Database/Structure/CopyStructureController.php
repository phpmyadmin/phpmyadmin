<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;
use function implode;
use function sprintf;

#[Route('/database/structure/copy-structure', ['POST'])]
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

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No databases selected.')));

            return $this->response->response();
        }

        $dbName = $databaseName->getName();

        /** @var string[] $tableNames */
        $tableNames = $this->dbi->getTables($dbName);

        $baseTables = [];
        $views = [];
        foreach ($tableNames as $table) {
            $object = $this->dbi->getTable($dbName, $table);
            if ($object->isView()) {
                $views[] = $table;
            } else {
                $baseTables[] = $table;
            }
        }

        $segments = [
            sprintf('-- Database: %s', $dbName),
        ];

        foreach ($baseTables as $table) {
            $object = $this->dbi->getTable($dbName, $table);
            $segments[] = $object->showCreate();
        }

        if ($views !== []) {
            $segments[] = '-- Views';
            foreach ($views as $table) {
                $object = $this->dbi->getTable($dbName, $table);
                $segments[] = $object->showCreate();
            }
        }

        $this->response->addJSON('sql', implode("\n\n", $segments));

        return $this->response->response();
    }
}
