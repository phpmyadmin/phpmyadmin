<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\View;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function is_string;

/**
 * View manipulations
 */
class OperationsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Operations $operations,
        private DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['reload'] ??= null;
        $GLOBALS['result'] ??= null;
        $tableObject = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table']);

        $GLOBALS['errorUrl'] ??= null;
        $this->addScriptFiles(['table/operations.js']);

        if (! $this->checkParameters(['db', 'table'])) {
            return;
        }

        $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
            Config::getInstance()->settings['DefaultTabTable'],
            'table',
        );
        $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->hasDatabase($databaseName)) {
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

        $GLOBALS['urlParams']['goto'] = $GLOBALS['urlParams']['back'] = Url::getFromRoute('/view/operations');

        $message = new Message();
        $type = 'success';
        $newname = $request->getParsedBodyParam('new_name');

        $warningMessages = [];
        if ($request->hasBodyParam('submitoptions')) {
            if (is_string($newname) && $tableObject->rename($newname)) {
                $message->addText($tableObject->getLastMessage());
                $GLOBALS['result'] = true;
                $GLOBALS['table'] = $tableObject->getName();
                /* Force reread after rename */
                $tableObject->getStatusInfo(null, true);
                $GLOBALS['reload'] = true;
            } else {
                    $message->addText($tableObject->getLastError());
                    $GLOBALS['result'] = false;
            }

            $warningMessages = $this->operations->getWarningMessagesArray();
        }

        if (isset($GLOBALS['result'])) {
            // set to success by default, because result set could be empty
            // (for example, a table rename)
            if ($message->getString() === '') {
                if ($GLOBALS['result']) {
                    $message->addText(
                        __('Your SQL query has been executed successfully.'),
                    );
                } else {
                    $message->addText(__('Error'));
                }

                // $result should exist, regardless of $_message
                $type = $GLOBALS['result'] ? 'success' : 'error';
            }

            if ($warningMessages !== []) {
                $message->addMessagesString($warningMessages);
                $message->setType(Message::ERROR);
            }

            $this->response->addHTML(Generator::getMessage(
                $message,
                $GLOBALS['sql_query'],
                $type,
            ));
        }

        $this->render('table/operations/view', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'url_params' => $GLOBALS['urlParams'],
        ]);
    }
}
