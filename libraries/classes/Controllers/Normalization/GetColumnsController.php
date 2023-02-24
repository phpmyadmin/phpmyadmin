<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

use function __;
use function _pgettext;

final class GetColumnsController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Normalization $normalization)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $html = '<option selected disabled>' . __('Select one…') . '</option>'
            . '<option value="no_such_col">' . __('No such column') . '</option>';
        //get column whose datatype falls under string category
        $html .= $this->normalization->getHtmlForColumnsList(
            $GLOBALS['db'],
            $GLOBALS['table'],
            _pgettext('string types', 'String'),
        );
        $this->response->addHTML($html);
    }
}
