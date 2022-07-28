<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Export;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;

final class CheckTimeOutController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $this->response->setAjax(true);

        if (isset($_SESSION['pma_export_error'])) {
            unset($_SESSION['pma_export_error']);
            $this->response->addJSON('message', 'timeout');

            return;
        }

        $this->response->addJSON('message', 'success');
    }
}
