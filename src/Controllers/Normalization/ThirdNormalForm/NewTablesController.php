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

#[Route('/normalization/3nf/new-tables', ['POST'])]
final class NewTablesController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Normalization $normalization,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $dependencies = json_decode($request->getParsedBodyParamAsString('pd'));
        $tables = json_decode($request->getParsedBodyParamAsString('tables'), true);
        $newTables = $this->normalization->getHtmlForNewTables3NF($dependencies, $tables, Current::$database);
        $this->response->addJSON($newTables);

        return $this->response->response();
    }
}
