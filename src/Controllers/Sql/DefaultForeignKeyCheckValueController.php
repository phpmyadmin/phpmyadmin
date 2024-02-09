<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Utils\ForeignKey;

final class DefaultForeignKeyCheckValueController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $this->response->addJSON('default_fk_check_value', ForeignKey::isCheckEnabled());
    }
}
