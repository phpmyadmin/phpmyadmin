<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization\SecondNormalForm;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function json_decode;

final class NewTablesController extends AbstractController
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
        $partialDependencies = json_decode($_POST['pd'], true);
        $html = $this->normalization->getHtmlForNewTables2NF($partialDependencies, $GLOBALS['table']);
        $this->response->addHTML($html);
    }
}
