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

/**
 * Normalization process (temporarily specific to 1NF).
 */
class MainController extends AbstractController
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
        if (isset($_POST['splitColumn'])) {
            $num_fields = min(4096, intval($_POST['numFields']));
            $html = $this->normalization->getHtmlForCreateNewColumn($num_fields, $GLOBALS['db'], $GLOBALS['table']);
            $html .= Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
            echo $html;

            return;
        }

        if (isset($_POST['findPdl'])) {
            $html = $this->normalization->findPartialDependencies($GLOBALS['table'], $GLOBALS['db']);
            echo $html;

            return;
        }

        $this->addScriptFiles(['normalization.js', 'vendor/jquery/jquery.uitablefilter.js']);

        $this->render('table/normalization/normalization', [
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
        ]);
    }
}
