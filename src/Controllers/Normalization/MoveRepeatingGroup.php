<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

#[Route('/normalization/move-repeating-group', ['POST'])]
final class MoveRepeatingGroup implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Normalization $normalization,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $repeatingColumns = $request->getParsedBodyParamAsString('repeatingColumns');
        $newTable = $request->getParsedBodyParamAsString('newTable');
        $newColumn = $request->getParsedBodyParamAsString('newColumn');
        $primaryColumns = $request->getParsedBodyParamAsString('primary_columns');
        $res = $this->normalization->moveRepeatingGroup(
            $repeatingColumns,
            $primaryColumns,
            $newTable,
            $newColumn,
            Current::$table,
            Current::$database,
        );
        $this->response->addJSON($res);

        return $this->response->response();
    }
}
