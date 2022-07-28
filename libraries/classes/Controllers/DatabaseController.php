<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\ServerRequest;

final class DatabaseController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['dblist'] = $GLOBALS['dblist'] ?? null;
        $this->response->addJSON(['databases' => $GLOBALS['dblist']->databases]);
    }
}
