<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status\Monitor;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Server\Status\AbstractController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Server\Status\Monitor;
use PhpMyAdmin\Template;

#[Route('/server/status/monitor/slow-log', ['POST'])]
final class SlowLogController extends AbstractController implements InvocableController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Data $data,
        private readonly Monitor $monitor,
        private readonly DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template, $data);
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        if (! $request->isAjax()) {
            return $this->response->response();
        }

        $data = $this->monitor->getJsonForLogDataTypeSlow(
            (int) $request->getParsedBodyParamAsStringOrNull('time_start'),
            (int) $request->getParsedBodyParamAsStringOrNull('time_end'),
        );
        if ($data === null) {
            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        $this->response->addJSON(['message' => $data]);

        return $this->response->response();
    }
}
