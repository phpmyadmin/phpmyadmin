<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status\Processes;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Server\Status\AbstractController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;

#[Route('/server/status/processes/refresh', ['POST'])]
final class RefreshController extends AbstractController implements InvocableController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private readonly Processes $processes,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->isAjax()) {
            return $this->response->response();
        }

        $this->response->render('server/status/processes/list', $this->processes->getList(
            $request->hasBodyParam('showExecuting'),
            $request->hasBodyParam('full'),
            $request->getParsedBodyParamAsString('order_by_field', ''),
            $request->getParsedBodyParamAsString('sort_order', ''),
        ));

        return $this->response->response();
    }
}
