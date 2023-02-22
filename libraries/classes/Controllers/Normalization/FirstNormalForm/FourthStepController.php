<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization\FirstNormalForm;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class FourthStepController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Normalization $normalization)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $res = $this->normalization->getHtmlContentsFor1NFStep4($GLOBALS['db'], $GLOBALS['table']);
        $this->response->addJSON($res);
    }
}
