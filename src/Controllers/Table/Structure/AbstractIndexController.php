<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Query\Generator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use Webmozart\Assert\Assert;

use function __;
use function is_array;

abstract class AbstractIndexController
{
    public function __construct(
        protected readonly ResponseRenderer $response,
        protected readonly StructureController $structureController,
        protected readonly Indexes $indexes,
    ) {
    }

    /** @psalm-param 'FULLTEXT'|'INDEX'|'PRIMARY'|'SPATIAL'|'UNIQUE' $indexType */
    public function handleIndexCreation(ServerRequest $request, string $indexType): Response
    {
        $selected = $request->getParsedBodyParam('selected_fld', []);
        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return $this->response->response();
        }

        Assert::allString($selected);

        if ($indexType === 'PRIMARY') {
            $hasPrimaryKey = $this->indexes->hasPrimaryKey(Current::$database, Current::$table);
            $statement = Generator::getAddPrimaryKeyStatement(Current::$table, $selected[0], $hasPrimaryKey);
        } else {
            $statement = Generator::getAddIndexSql($indexType, Current::$table, $selected);
        }

        $message = $this->indexes->executeAddIndexSql(Current::$database, $statement);

        Current::$sqlQuery = $statement;
        $GLOBALS['message'] = $message;

        return ($this->structureController)($request);
    }
}
