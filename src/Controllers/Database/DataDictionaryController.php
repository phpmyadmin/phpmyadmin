<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

use function is_array;
use function str_replace;

#[Route('/database/data-dictionary', ['GET'])]
final readonly class DataDictionaryController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Relation $relation,
        private Transformations $transformations,
        private DatabaseInterface $dbi,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        $relationParameters = $this->relation->getRelationParameters();

        $comment = $this->relation->getDbComment(Current::$database);

        $this->dbi->selectDb(Current::$database);
        $tablesNames = $this->dbi->getTables(Current::$database);
        $tablesFull = $this->dbi->getTablesFull(Current::$database);

        $tables = [];
        foreach ($tablesNames as $tableName) {
            $showComment = (string) ($tablesFull[$tableName]['TABLE_COMMENT'] ?? '');

            $primaryKeys = Index::getPrimary($this->dbi, $tableName, Current::$database)?->getColumns() ?? [];

            $foreigners = $relationParameters->relationFeature !== null
                ? $this->relation->getForeigners(Current::$database, $tableName)
                : null;

            $mimeMap = $relationParameters->browserTransformationFeature !== null
                ? $this->transformations->getMime(Current::$database, $tableName, true)
                : null;

            $columns = $this->dbi->getColumns(Current::$database, $tableName);
            $rows = [];
            foreach ($columns as $row) {
                $extractedColumnSpec = Util::extractColumnSpec($row->type);

                $relation = '';
                if ($foreigners !== null && ! $foreigners->isEmpty()) {
                    $foreigner = $this->relation->searchColumnInForeigners($foreigners, $row->field);
                    if (is_array($foreigner) && isset($foreigner['foreign_table'], $foreigner['foreign_field'])) {
                        $relation = $foreigner['foreign_table'];
                        $relation .= ' -> ';
                        $relation .= $foreigner['foreign_field'];
                    }
                }

                $mime = '';
                if (isset($mimeMap[$row->field]['mimetype'])) {
                    $mime = str_replace('_', '/', $mimeMap[$row->field]['mimetype']);
                }

                $rows[$row->field] = [
                    'name' => $row->field,
                    'has_primary_key' => isset($primaryKeys[$row->field]),
                    'type' => $extractedColumnSpec['type'],
                    'print_type' => $extractedColumnSpec['print_type'],
                    'is_nullable' => $row->isNull,
                    'default' => $row->default,
                    'comment' => $row->comment,
                    'mime' => $mime,
                    'relation' => $relation,
                ];
            }

            $tables[$tableName] = [
                'name' => $tableName,
                'comment' => $showComment,
                'has_relation' => $foreigners !== null && ! $foreigners->isEmpty(),
                'has_mime' => $relationParameters->browserTransformationFeature !== null,
                'columns' => $rows,
                'indexes' => Index::getFromTable($this->dbi, $tableName, Current::$database),
            ];
        }

        $this->response->render('database/data_dictionary/index', [
            'database' => Current::$database,
            'comment' => $comment,
            'tables' => $tables,
        ]);

        return $this->response->response();
    }
}
