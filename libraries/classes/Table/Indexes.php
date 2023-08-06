<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;

final class Indexes
{
    public function __construct(
        protected ResponseRenderer $response,
        protected Template $template,
        private DatabaseInterface $dbi,
    ) {
    }

    /**
     * Process the data from the edit/create index form,
     * run the query to build the new index
     * and moves back to /table/sql
     *
     * @param Index $index      An Index instance.
     * @param bool  $renameMode Rename the Index mode
     */
    public function doSaveData(
        ServerRequest $request,
        Index $index,
        bool $renameMode,
        string $db,
        string $table,
        bool $previewSql,
        string $oldIndexName = '',
    ): void {
        $error = false;
        if ($renameMode && Compatibility::isCompatibleRenameIndex($this->dbi->getVersion())) {
            if ($oldIndexName === 'PRIMARY') {
                if ($index->getName() === '') {
                    $index->setName('PRIMARY');
                } elseif ($index->getName() !== 'PRIMARY') {
                    $error = Message::error(
                        __('The name of the primary key must be "PRIMARY"!'),
                    );
                }
            }

            $sqlQuery = QueryGenerator::getSqlQueryForIndexRename(
                $db,
                $table,
                $oldIndexName,
                $index->getName(),
            );
        } else {
            $sqlQuery = $this->dbi->getTable($db, $table)
                ->getSqlQueryForIndexCreateOrEdit($index, $error);
        }

        // If there is a request for SQL previewing.
        if ($previewSql) {
            $this->response->addJSON(
                'sql_data',
                $this->template->render('preview_sql', ['query_data' => $sqlQuery]),
            );

            return;
        }

        if ($error) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $error);

            return;
        }

        $this->dbi->query($sqlQuery);
        if ($request->isAjax()) {
            $message = Message::success(
                __('Table %1$s has been altered successfully.'),
            );
            $message->addParam($table);
            $this->response->addJSON(
                'message',
                Generator::getMessage($message, $sqlQuery, 'success'),
            );

            $indexes = Index::getFromTable($this->dbi, $table, $db);
            $indexesDuplicates = Index::findDuplicates($table, $db);

            $this->response->addJSON(
                'index_table',
                $this->template->render('indexes', [
                    'url_params' => ['db' => $db, 'table' => $table],
                    'indexes' => $indexes,
                    'indexes_duplicates' => $indexesDuplicates,
                ]),
            );

            return;
        }

        /** @var StructureController $controller */
        $controller = Core::getContainerBuilder()->get(StructureController::class);
        $controller($request);
    }

    public function executeAddIndexSql(string|DatabaseName $db, string $sql): Message
    {
        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($sql);

        if (! $result) {
            return Message::error($this->dbi->getError());
        }

        return Message::success();
    }
}
