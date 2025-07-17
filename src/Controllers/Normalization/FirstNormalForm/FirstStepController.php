<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization\FirstNormalForm;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function in_array;

#[Route('/normalization/1nf/step1', ['POST'])]
final class FirstStepController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Normalization $normalization,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addScriptFiles(['normalization.js', 'vendor/jquery/jquery.uitablefilter.js']);

        $normalForm = '1nf';
        $normalizeTo = $request->getParsedBodyParamAsString('normalizeTo', '');
        if (in_array($normalizeTo, ['1nf', '2nf', '3nf'], true)) {
            $normalForm = $normalizeTo;
        }

        $html = $this->normalization->getHtmlFor1NFStep1(Current::$database, Current::$table, $normalForm);
        $this->response->addHTML($html);

        return $this->response->response();
    }
}
