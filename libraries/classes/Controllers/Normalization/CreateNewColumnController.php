<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function intval;
use function min;

final class CreateNewColumnController extends AbstractController
{
    /** @var Normalization */
    private $normalization;

    public function __construct(ResponseRenderer $response, Template $template, Normalization $normalization)
    {
        parent::__construct($response, $template);
        $this->normalization = $normalization;
    }

    public function __invoke(ServerRequest $request): void
    {
        $num_fields = min(4096, intval($_POST['numFields']));
        $html = $this->normalization->getHtmlForCreateNewColumn($num_fields, $GLOBALS['db'], $GLOBALS['table']);
        $html .= Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
        $this->response->addHTML($html);
    }
}
