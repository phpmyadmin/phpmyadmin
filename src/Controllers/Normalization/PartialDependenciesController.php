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

#[Route('/normalization/partial-dependencies', ['POST'])]
final class PartialDependenciesController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Normalization $normalization,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $html = $this->normalization->findPartialDependencies(Current::$table, Current::$database);
        $this->response->addHTML($html);

        return $this->response->response();
    }
}
