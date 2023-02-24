<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class MoveRepeatingGroup extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Normalization $normalization)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $repeatingColumns = $request->getParsedBodyParam('repeatingColumns');
        $newTable = $request->getParsedBodyParam('newTable');
        $newColumn = $request->getParsedBodyParam('newColumn');
        $primary_columns = $request->getParsedBodyParam('primary_columns');
        $res = $this->normalization->moveRepeatingGroup(
            $repeatingColumns,
            $primary_columns,
            $newTable,
            $newColumn,
            $GLOBALS['table'],
            $GLOBALS['db'],
        );
        $this->response->addJSON($res);
    }
}
