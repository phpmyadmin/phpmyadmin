<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Processes;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

class ProcessesController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private DatabaseInterface $dbi,
        private Processes $processes,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] = Url::getFromRoute('/');

        $showExecuting = $request->hasBodyParam('showExecuting');
        $full = $request->hasBodyParam('full');
        $orderByField = (string) $request->getParsedBodyParam('order_by_field', '');
        $sortOrder = (string) $request->getParsedBodyParam('sort_order', '');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->addScriptFiles(['server/status/processes.js']);

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

        $this->render('server/status/processes/index', [
            'url_params' => $urlParams,
            'is_checked' => $showExecuting,
            'server_process_list' => $listHtml,
        ]);
    }
}
