<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status\Processes;

use PhpMyAdmin\Controllers\Server\Status\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;

final class RefreshController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private Processes $processes,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): void
    {
        if (! $this->response->isAjax()) {
            return;
        }

        $this->render('server/status/processes/list', $this->processes->getList(
            $request->hasBodyParam('showExecuting'),
            $request->hasBodyParam('full'),
            (string) $request->getParsedBodyParam('order_by_field', ''),
            (string) $request->getParsedBodyParam('sort_order', ''),
        ));
    }
}
