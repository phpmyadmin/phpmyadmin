<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

final class AddNewPrimaryController extends AbstractController
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
        $num_fields = 1;
        $columnMeta = [
            'Field' => $GLOBALS['table'] . '_id',
            'Extra' => 'auto_increment',
        ];
        $html = $this->normalization->getHtmlForCreateNewColumn(
            $num_fields,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $columnMeta
        );
        $html .= Url::getHiddenInputs($GLOBALS['db'], $GLOBALS['table']);
        $this->response->addHTML($html);
    }
}
