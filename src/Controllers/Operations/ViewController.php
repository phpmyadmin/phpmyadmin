<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Operations;

use PhpMyAdmin\Controllers\InvocableController;
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
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;
use function array_map;
use function is_string;
use function strval;

/**
 * View manipulations
 */
#[Route('/view/operations', ['GET', 'POST'])]
final class ViewController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $tableObject = $this->dbi->getTable(Current::$database, Current::$table);

        $this->response->addScriptFiles(['table/operations.js']);

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

        UrlParams::$params['goto'] = UrlParams::$params['back'] = Url::getFromRoute('/view/operations');

        $message = new Message();
        $type = MessageType::Success;
        $newname = $request->getParsedBodyParam('new_name');

        $warningMessages = [];
        if ($request->hasBodyParam('submitoptions')) {
            if (is_string($newname) && $tableObject->rename($newname)) {
                $message->addText($tableObject->getLastMessage());
                $result = true;
                Current::$table = $tableObject->getName();
                /* Force reread after rename */
                $this->dbi->getCache()->clearTableCache();
                ResponseRenderer::$reload = true;
            } else {
                $message->addText($tableObject->getLastError());
                $result = false;
            }

            $warningMessages = array_map(strval(...), $this->dbi->getWarnings());
        }

        if (isset($result)) {
            // set to success by default, because result set could be empty
            // (for example, a table rename)
            if ($message->getString() === '') {
                if ($result) {
                    $message->addText(
                        __('Your SQL query has been executed successfully.'),
                    );
                } else {
                    $message->addText(__('Error'));
                }

                $type = $result ? MessageType::Success : MessageType::Error;
            }

            if ($warningMessages !== []) {
                $message->addMessagesString($warningMessages);
                $message->setType(MessageType::Error);
            }

            $this->response->addHTML(Generator::getMessage(
                $message,
                Current::$sqlQuery,
                $type,
            ));
        }

        $this->response->render('table/operations/view', [
            'db' => Current::$database,
            'table' => Current::$table,
            'url_params' => UrlParams::$params,
        ]);

        return $this->response->response();
    }
}
