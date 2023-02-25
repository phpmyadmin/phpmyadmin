<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization\FirstNormalForm;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function in_array;

final class FirstStepController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Normalization $normalization)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->addScriptFiles(['normalization.js', 'vendor/jquery/jquery.uitablefilter.js']);

        $normalForm = '1nf';
        $normalizeTo = $request->getParsedBodyParam('normalizeTo');
        if ($normalizeTo !== null && in_array($normalizeTo, ['1nf', '2nf', '3nf'])) {
            $normalForm = $normalizeTo;
        }

        $html = $this->normalization->getHtmlFor1NFStep1($GLOBALS['db'], $GLOBALS['table'], $normalForm);
        $this->response->addHTML($html);
    }
}
