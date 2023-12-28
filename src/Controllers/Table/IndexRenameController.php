<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
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

final class IndexRenameController extends AbstractController
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

        $oldIndexName = $request->getParsedBodyParam('old_index');
        $indexName = $request->getParsedBodyParam('index');
        if ($oldIndexName === null) {
            $index = $this->dbi->getTable($databaseName->getName(), $tableName->getName())->getIndex($indexName);

            $formParams = [
                'db' => $databaseName->getName(),
                'table' => $tableName->getName(),
                'old_index' => $index->getName(),
            ];

            $this->render('table/index_rename_form', ['index' => $index, 'form_params' => $formParams]);

            return;
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

            return;
        }

        $logicError = $this->indexes->getError();
        if ($logicError instanceof Message) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $logicError);

            return;
        }

        $this->dbi->query($sqlQuery);

        $message = Message::success(__('Table %1$s has been altered successfully.'));
        $message->addParam($tableName->getName());
        $this->response->addJSON(
            'message',
            Generator::getMessage($message, $sqlQuery, 'success'),
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
    }
}
