<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;

final class DatabaseController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $this->response->addJSON(['databases' => DatabaseInterface::getInstance()->getDatabaseList()]);
    }
}
