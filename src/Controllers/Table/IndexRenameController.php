<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;

final class IndexRenameController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly DatabaseInterface $dbi,
        private readonly Indexes $indexes,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        if (! $this->response->checkParameters(['db', 'table'])) {
            return $this->response->response();
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

        $oldIndexName = $request->getParsedBodyParamAsStringOrNull('old_index');
        $indexName = $request->getParsedBodyParamAsString('index');
        if ($oldIndexName === null) {
            $index = $this->dbi->getTable($databaseName->getName(), $tableName->getName())->getIndex($indexName);

            $formParams = [
                'db' => $databaseName->getName(),
                'table' => $tableName->getName(),
                'old_index' => $index->getName(),
            ];

            $this->response->render('table/index_rename_form', ['index' => $index, 'form_params' => $formParams]);

            return $this->response->response();
        }

        // coming already from form
        $index = $this->dbi->getTable($databaseName->getName(), $tableName->getName())->getIndex($oldIndexName);
        $index->setName($indexName);

        $previewSql = $request->hasBodyParam('preview_sql');

        $sqlQuery = $this->indexes->getSqlQueryForRename(
            $oldIndexName,
            $index,
            $databaseName->getName(),
            $tableName->getName(),
        );

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

        $message = Message::success(__('Table %1$s has been altered successfully.'));
        $message->addParam($tableName->getName());
        $this->response->addJSON(
            'message',
            Generator::getMessage($message, $sqlQuery, MessageType::Success),
        );

        $indexes = Index::getFromTable($this->dbi, $tableName->getName(), $databaseName->getName());
        $indexesDuplicates = Index::findDuplicates($tableName->getName(), $databaseName->getName());

        $this->response->addJSON(
            'index_table',
            $this->template->render('indexes', [
                'url_params' => ['db' => $databaseName->getName(), 'table' => $tableName->getName()],
                'indexes' => $indexes,
                'indexes_duplicates' => $indexesDuplicates,
            ]),
        );

        return $this->response->response();
    }
}
