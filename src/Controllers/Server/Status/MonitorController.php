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
use PhpMyAdmin\Server\SysInfo\SysInfo;
use PhpMyAdmin\Template;

use function is_numeric;
use function microtime;

#[Route('/server/status/monitor', ['GET'])]
final class MonitorController extends AbstractController implements InvocableController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private readonly DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->response->addScriptFiles([
            'vendor/chart.umd.js',
            'vendor/chartjs-adapter-date-fns.bundle.js',
            'vendor/jquery/jquery.tablesorter.js',
            'jquery.sortable-table.js',
            'server/status/monitor.js',
        ]);

        $form = [
            'server_time' => (int) (microtime(true) * 1000),
            'server_os' => SysInfo::getOs(),
            'is_superuser' => $this->dbi->isSuperUser(),
            'server_db_isLocal' => $this->data->dbIsLocal,
        ];

        $javascriptVariableNames = [];
        foreach ($this->data->status as $name => $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $javascriptVariableNames[] = $name;
        }

        $this->response->render('server/status/monitor/index', [
            'javascript_variable_names' => $javascriptVariableNames,
            'form' => $form,
        ]);

        return $this->response->response();
    }
}
