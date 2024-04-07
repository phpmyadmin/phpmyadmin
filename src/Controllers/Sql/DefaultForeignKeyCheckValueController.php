<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Utils\ForeignKey;

final class DefaultForeignKeyCheckValueController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Template $template)
    {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        $this->response->addJSON('default_fk_check_value', ForeignKey::isCheckEnabled());

        return null;
    }
}
