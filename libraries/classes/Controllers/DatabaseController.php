<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

final class DatabaseController extends AbstractController
{
    public function all(): void
    {
        global $dblist;

        $this->response->addJSON(['databases' => $dblist->databases]);
    }
}
