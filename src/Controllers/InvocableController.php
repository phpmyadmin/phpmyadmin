<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;

interface InvocableController
{
    public function __invoke(ServerRequest $request): Response;
}
