<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization\ThirdNormalForm;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function json_decode;

#[Route('/normalization/3nf/create-new-tables', ['POST'])]
final readonly class CreateNewTablesController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Normalization $normalization,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $newtables = json_decode($request->getParsedBodyParamAsString('newTables'), true);
        $res = $this->normalization->createNewTablesFor3NF($newtables, Current::$database);
        $this->response->addJSON($res);

        return $this->response->response();
    }
}
