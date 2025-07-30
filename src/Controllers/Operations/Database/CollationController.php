<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Operations\Database;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Util;

use function __;

#[Route('/database/operations/collation', ['POST'])]
final class CollationController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Operations $operations,
        private readonly DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->isAjax()) {
            return $this->response->response();
        }

        $dbCollation = $request->getParsedBodyParamAsString('db_collation', '');
        if ($dbCollation === '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No collation provided.')));

            return $this->response->response();
        }

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error(__('No databases selected.')));

            return $this->response->response();
        }

        $sqlQuery = 'ALTER DATABASE ' . Util::backquote(Current::$database)
            . ' DEFAULT' . Util::getCharsetQueryPart($dbCollation);
        $this->dbi->query($sqlQuery);
        $message = Message::success();

        /**
         * Changes tables charset if requested by the user
         */
        if ($request->getParsedBodyParam('change_all_tables_collations') === 'on') {
            foreach ($this->dbi->getTables(Current::$database) as $tableName) {
                if ($this->dbi->getTable(Current::$database, $tableName)->isView()) {
                    // Skip views, we can not change the collation of a view.
                    // issue #15283
                    continue;
                }

                $sqlQuery = 'ALTER TABLE '
                    . Util::backquote(Current::$database)
                    . '.'
                    . Util::backquote($tableName)
                    . ' DEFAULT '
                    . Util::getCharsetQueryPart($dbCollation);
                $this->dbi->query($sqlQuery);

                /**
                 * Changes columns charset if requested by the user
                 */
                if ($request->getParsedBodyParam('change_all_tables_columns_collations') !== 'on') {
                    continue;
                }

                $this->operations->changeAllColumnsCollation(Current::$database, $tableName, $dbCollation);
            }
        }

        $this->response->setRequestStatus($message->isSuccess());
        $this->response->addJSON('message', $message);

        return $this->response->response();
    }
}
