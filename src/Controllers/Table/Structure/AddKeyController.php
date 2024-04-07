<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class AddKeyController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly SqlController $sqlController,
        private readonly StructureController $structureController,
    ) {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        ($this->sqlController)($request);

        $GLOBALS['reload'] = true;

        ($this->structureController)($request);

        return null;
    }
}
