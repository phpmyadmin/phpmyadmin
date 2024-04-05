<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;

final class UniqueController extends AbstractIndexController
{
    public function __invoke(ServerRequest $request): Response|null
    {
        $this->handleIndexCreation($request, 'UNIQUE');

        return null;
    }
}
