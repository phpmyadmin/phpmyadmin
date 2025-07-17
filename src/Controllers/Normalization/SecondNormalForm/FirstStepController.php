<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization\SecondNormalForm;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

#[Route('/normalization/2nf/step1', ['POST'])]
final class FirstStepController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Normalization $normalization,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $res = $this->normalization->getHtmlFor2NFstep1(Current::$database, Current::$table);
        $this->response->addJSON($res);

        return $this->response->response();
    }
}
