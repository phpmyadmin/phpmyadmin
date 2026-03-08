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

use function __;
use function _pgettext;

#[Route('/normalization/get-columns', ['POST'])]
final readonly class GetColumnsController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Normalization $normalization,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $html = '<option selected disabled>' . __('Select one…') . '</option>'
            . '<option value="no_such_col">' . __('No such column') . '</option>';
        //get column whose datatype falls under string category
        $html .= $this->normalization->getHtmlForColumnsList(
            Current::$database,
            Current::$table,
            _pgettext('string types', 'String'),
        );
        $this->response->addHTML($html);

        return $this->response->response();
    }
}
