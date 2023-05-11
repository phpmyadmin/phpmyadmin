<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function array_column;
use function array_splice;
use function count;
use function implode;
use function in_array;
use function is_array;
use function mb_strtoupper;
use function sprintf;
use function str_replace;

final class MoveColumnsController extends AbstractController
{
    private Table $tableObj;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);

        $this->tableObj = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table']);
    }

    public function __invoke(ServerRequest $request): void
    {
        $moveColumns = $request->getParsedBodyParam('move_columns');
        if (! is_array($moveColumns) || ! $this->response->isAjax()) {
            return;
        }

        $this->dbi->selectDb($GLOBALS['db']);

        /**
         * load the definitions for all columns
         */
        $columns = $this->dbi->getColumnsFull($GLOBALS['db'], $GLOBALS['table']);
        $columnNames = array_column($columns, 'Field');
        $changes = [];

        // @see https://mariadb.com/kb/en/library/changes-improvements-in-mariadb-102/#information-schema
        $usesLiteralNull = $this->dbi->isMariaDB() && $this->dbi->getVersion() >= 100200;
        $defaultNullValue = $usesLiteralNull ? 'NULL' : null;
        // move columns from first to last
        for ($i = 0, $l = count($moveColumns); $i < $l; $i++) {
            $column = $moveColumns[$i];
            // is this column already correctly placed?
            if ($columnNames[$i] == $column) {
                continue;
            }

            // it is not, let's move it to index $i
            $data = $columns[$column];
            $extractedColumnSpec = Util::extractColumnSpec($data['Type']);
            if (isset($data['Extra']) && $data['Extra'] === 'on update CURRENT_TIMESTAMP') {
                $extractedColumnSpec['attribute'] = $data['Extra'];
                unset($data['Extra']);
            }

            $timeType = $data['Type'] === 'timestamp' || $data['Type'] === 'datetime';
            $timeDefault = $data['Default'] === 'CURRENT_TIMESTAMP' || $data['Default'] === 'current_timestamp()';
            $currentTimestamp = $timeType && $timeDefault;

            $uuidType = $data['Type'] === 'uuid';
            $uuidDefault = $data['Default'] === 'UUID' || $data['Default'] === 'uuid()';
            $uuid = $uuidType && $uuidDefault;

            // @see https://mariadb.com/kb/en/library/information-schema-columns-table/#examples
            if ($data['Null'] === 'YES' && in_array($data['Default'], [$defaultNullValue, null])) {
                $defaultType = 'NULL';
            } elseif ($currentTimestamp) {
                $defaultType = 'CURRENT_TIMESTAMP';
            } elseif ($uuid) {
                $defaultType = 'UUID';
            } elseif ($data['Default'] === null) {
                $defaultType = 'NONE';
            } else {
                $defaultType = 'USER_DEFINED';
            }

            $virtual = ['VIRTUAL', 'PERSISTENT', 'VIRTUAL GENERATED', 'STORED GENERATED'];
            $data['Virtuality'] = '';
            $data['Expression'] = '';
            if (isset($data['Extra']) && in_array($data['Extra'], $virtual)) {
                $data['Virtuality'] = str_replace(' GENERATED', '', $data['Extra']);
                $expressions = $this->tableObj->getColumnGenerationExpression($column);
                $data['Expression'] = is_array($expressions) ? $expressions[$column] : null;
            }

            $changes[] = 'CHANGE ' . Table::generateAlter(
                $column,
                $column,
                mb_strtoupper($extractedColumnSpec['type']),
                $extractedColumnSpec['spec_in_brackets'],
                $extractedColumnSpec['attribute'],
                $data['Collation'] ?? '',
                $data['Null'] === 'YES' ? 'YES' : 'NO',
                $defaultType,
                $data['Default'] ?? '',
                $data['Extra'] ?? '',
                $data['COLUMN_COMMENT'] ?? '',
                $data['Virtuality'],
                $data['Expression'],
                $i === 0 ? '-first' : $columnNames[$i - 1],
            );
            // update current column_names array, first delete old position
            for ($j = 0, $ll = count($columnNames); $j < $ll; $j++) {
                if ($columnNames[$j] != $column) {
                    continue;
                }

                unset($columnNames[$j]);
            }

            // insert moved column
            array_splice($columnNames, $i, 0, $column);
        }

        if ($changes === [] && ! isset($_REQUEST['preview_sql'])) { // should never happen
            $this->response->setRequestStatus(false);

            return;
        }

        // query for moving the columns
        $sqlQuery = sprintf(
            'ALTER TABLE %s %s',
            Util::backquote($GLOBALS['table']),
            implode(', ', $changes),
        );

        if (isset($_REQUEST['preview_sql'])) { // preview sql
            $this->response->addJSON(
                'sql_data',
                $this->template->render('preview_sql', ['query_data' => $sqlQuery]),
            );

            return;
        }

        $this->dbi->tryQuery($sqlQuery);
        $tmpError = $this->dbi->getError();
        if ($tmpError !== '') {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', Message::error($tmpError));

            return;
        }

        $message = Message::success(
            __('The columns have been moved successfully.'),
        );
        $this->response->addJSON('message', $message);
        $this->response->addJSON('columns', $columnNames);
    }
}
