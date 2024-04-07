<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Query\Generator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;

use function __;
use function is_array;

abstract class AbstractIndexController
{
    public function __construct(
        protected readonly ResponseRenderer $response,
        protected readonly Template $template,
        protected readonly StructureController $structureController,
        protected readonly Indexes $indexes,
    ) {
    }

    public function handleIndexCreation(ServerRequest $request, string $indexType): void
    {
        $GLOBALS['message'] ??= null;

        $selected = $request->getParsedBodyParam('selected_fld', []);

        if (! is_array($selected) || $selected === []) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No column selected.'));

            return;
        }

        $GLOBALS['sql_query'] = Generator::getAddIndexSql($indexType, Current::$table, $selected);

        $GLOBALS['message'] = $this->indexes->executeAddIndexSql(Current::$database, $GLOBALS['sql_query']);

        ($this->structureController)($request);
    }
}
