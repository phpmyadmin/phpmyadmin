<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Query\Generator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Indexes;
use PhpMyAdmin\Template;

use function __;
use function is_array;

abstract class AbstractIndexController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        protected StructureController $structureController,
        protected Indexes $indexes,
    ) {
        parent::__construct($response, $template);
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

        $GLOBALS['sql_query'] = Generator::getAddIndexSql($indexType, $GLOBALS['table'], $selected);

        $GLOBALS['message'] = $this->indexes->executeAddIndexSql($GLOBALS['db'], $GLOBALS['sql_query']);

        ($this->structureController)($request);
    }
}
