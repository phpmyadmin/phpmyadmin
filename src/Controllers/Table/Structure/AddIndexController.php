<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;

final class AddIndexController extends AbstractIndexController implements InvocableController
{
    public function __invoke(ServerRequest $request): Response|null
    {
        $this->handleIndexCreation($request, 'INDEX');

        return null;
    }
}
