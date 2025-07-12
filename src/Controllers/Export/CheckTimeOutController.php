<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

#[Route('/export/check-time-out', ['GET'])]
final class CheckTimeOutController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (! $request->isAjax()) {
            return $this->response->response();
        }

        if (isset($_SESSION['pma_export_error'])) {
            unset($_SESSION['pma_export_error']);
            $this->response->addJSON('message', 'timeout');

            return $this->response->response();
        }

        $this->response->addJSON('message', 'success');

        return $this->response->response();
    }
}
