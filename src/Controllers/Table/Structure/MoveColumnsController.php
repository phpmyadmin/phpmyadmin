<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function array_diff;
use function array_is_list;
use function array_search;
use function array_splice;
use function assert;
use function count;
use function implode;
use function is_array;

final class MoveColumnsController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $moveColumns = $request->getParsedBodyParam('move_columns');
        $previewSql = $request->getParsedBodyParam('preview_sql') === '1';
        if (! is_array($moveColumns) || ! array_is_list($moveColumns) || ! $this->response->isAjax()) {
            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        $this->dbi->selectDb(Current::$database);

        $createTableSql = $this->dbi->getTable(Current::$database, Current::$table)->showCreate();
        $sqlQuery = $this->generateAlterTableSql($createTableSql, $moveColumns);

        if ($sqlQuery instanceof Message) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', $sqlQuery);

            return $this->response->response();
        }

        if ($previewSql) {
            $this->response->addJSON(
                'sql_data',
                $this->template->render('preview_sql', ['query_data' => $sqlQuery]),
            );

            return $this->response->response();
        }

        $this->dbi->tryQuery($sqlQuery);
        $tmpError = $this->dbi->getError();
        if ($tmpError !== '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error($tmpError));

            return $this->response->response();
        }

        $message = Message::success(
            __('The columns have been moved successfully.'),
        );
        $this->response->addJSON('message', $message);
        $this->response->addJSON('columns', $moveColumns);

        return $this->response->response();
    }

    /** @param list<string> $moveColumns */
    private function generateAlterTableSql(string $createTableSql, array $moveColumns): string|Message
    {
        $parser = new Parser($createTableSql);
        /** @var CreateStatement $statement */
        $statement = $parser->statements[0];
        /** @var CreateDefinition[] $fields */
        $fields = $statement->fields;
        $columns = [];
        $columnNames = [];
        foreach ($fields as $field) {
            if ($field->name === null) {
                continue;
            }

            $columns[$field->name] = $field;
            $columnNames[] = $field->name;
        }

        // Ensure the columns from client match the columns from the table
        if (
            count($columnNames) !== count($moveColumns) ||
            array_diff($columnNames, $moveColumns) !== []
        ) {
            return Message::error(__('The selected columns do not match the columns in the table.'));
        }

        $changes = [];

        // move columns from first to last
        foreach ($moveColumns as $i => $columnName) {
            // is this column already correctly placed?
            if ($columnNames[$i] === $columnName) {
                continue;
            }

            $changes[] =
                'CHANGE ' . Util::backquote($columnName) . ' ' . $columns[$columnName]->build() .
                ($i === 0 ? ' FIRST' : ' AFTER ' . Util::backquote($columnNames[$i - 1]));

            // Move column to its new position
            /**
             * @var int $j
             * We are sure that the value exists because we checked it with array_diff and the type of both is string
             */
            $j = array_search($columnName, $columnNames, true);
            array_splice($columnNames, $j, 1);
            array_splice($columnNames, $i, 0, $columnName);
        }

        if ($changes === []) {
            return Message::error(__('The selected columns are already in the correct order.'));
        }

        assert($statement->name !== null, 'Alter table statement has no name');

        return 'ALTER TABLE ' . Util::backquote($statement->name->table) . "\n  " . implode(",\n  ", $changes);
    }
}
