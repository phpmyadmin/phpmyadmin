<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Index;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

use function is_array;
use function str_replace;

class DataDictionaryController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Relation $relation,
        private Transformations $transformations,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        if (! $this->checkParameters(['db'], true)) {
            return;
        }

        $relationParameters = $this->relation->getRelationParameters();

        $comment = $this->relation->getDbComment(Current::$database);

        $this->dbi->selectDb(Current::$database);
        $tablesNames = $this->dbi->getTables(Current::$database);

        $tables = [];
        foreach ($tablesNames as $tableName) {
            $showComment = (string) $this->dbi->getTable(Current::$database, $tableName)
                ->getStatusInfo('TABLE_COMMENT');

            [, $primaryKeys] = Util::processIndexData(
                $this->dbi->getTableIndexes(Current::$database, $tableName),
            );

            $foreigners = $this->relation->getRelationsAndStatus(
                $relationParameters->relationFeature !== null,
                Current::$database,
                $tableName,
            );

            $columnsComments = $this->relation->getComments(Current::$database, $tableName);

            $columns = $this->dbi->getColumns(Current::$database, $tableName);
            $rows = [];
            foreach ($columns as $row) {
                $extractedColumnSpec = Util::extractColumnSpec($row->type);

                $relation = '';
                if ($foreigners !== []) {
                    $foreigner = $this->relation->searchColumnInForeigners($foreigners, $row->field);
                    if (is_array($foreigner) && isset($foreigner['foreign_table'], $foreigner['foreign_field'])) {
                        $relation = $foreigner['foreign_table'];
                        $relation .= ' -> ';
                        $relation .= $foreigner['foreign_field'];
                    }
                }

                $mime = '';
                if ($relationParameters->browserTransformationFeature !== null) {
                    $mimeMap = $this->transformations->getMime(Current::$database, $tableName, true);
                    if (is_array($mimeMap) && isset($mimeMap[$row->field]['mimetype'])) {
                        $mime = str_replace('_', '/', $mimeMap[$row->field]['mimetype']);
                    }
                }

                $rows[$row->field] = [
                    'name' => $row->field,
                    'has_primary_key' => isset($primaryKeys[$row->field]),
                    'type' => $extractedColumnSpec['type'],
                    'print_type' => $extractedColumnSpec['print_type'],
                    'is_nullable' => $row->isNull,
                    'default' => $row->default,
                    'comment' => $columnsComments[$row->field] ?? '',
                    'mime' => $mime,
                    'relation' => $relation,
                ];
            }

            $tables[$tableName] = [
                'name' => $tableName,
                'comment' => $showComment,
                'has_relation' => $foreigners !== [],
                'has_mime' => $relationParameters->browserTransformationFeature !== null,
                'columns' => $rows,
                'indexes' => Index::getFromTable($this->dbi, $tableName, Current::$database),
            ];
        }

        $this->render('database/data_dictionary/index', [
            'database' => Current::$database,
            'comment' => $comment,
            'tables' => $tables,
        ]);
    }
}
