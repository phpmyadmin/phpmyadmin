<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

final class DatabaseController extends AbstractController
{
    public function __invoke(): void
    {
        $GLOBALS['dblist'] = $GLOBALS['dblist'] ?? null;
        $this->response->addJSON(['databases' => $GLOBALS['dblist']->databases]);
    }
}
