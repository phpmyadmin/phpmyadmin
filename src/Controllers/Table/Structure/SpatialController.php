<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Structure;

use PhpMyAdmin\Http\ServerRequest;

final class SpatialController extends AbstractIndexController
{
    public function __invoke(ServerRequest $request): void
    {
        $this->handleIndexCreation($request, 'SPATIAL');
    }
}
