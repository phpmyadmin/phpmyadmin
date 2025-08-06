<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;

#[Route('/server/status/processes', ['GET', 'POST'])]
final class ProcessesController extends AbstractController implements InvocableController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private readonly DatabaseInterface $dbi,
        private readonly Processes $processes,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): Response
    {
        $showExecuting = $request->hasBodyParam('showExecuting');
        $full = $request->getParsedBodyParam('full') === '1';
        $orderByField = $request->getParsedBodyParamAsString('order_by_field', '');
        $sortOrder = $request->getParsedBodyParamAsString('sort_order', '');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->response->addScriptFiles(['server/status/processes.js']);

        $listHtml = $this->template->render('server/status/processes/list', $this->processes->getList(
            $showExecuting,
            $full,
            $orderByField,
            $sortOrder,
        ));

        $urlParams = [
            'ajax_request' => true,
            'full' => $full,
            'column_name' => $request->getParsedBodyParam('column_name', ''),
            'order_by_field' => $orderByField,
            'sort_order' => $sortOrder,
        ];

        $this->response->render('server/status/processes/index', [
            'url_params' => $urlParams,
            'is_checked' => $showExecuting,
            'server_process_list' => $listHtml,
        ]);

        return $this->response->response();
    }
}
