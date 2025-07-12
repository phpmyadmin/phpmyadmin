<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Sql;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Utils\ForeignKey;

#[Route('/sql/get-default-fk-check-value', ['GET'])]
final class DefaultForeignKeyCheckValueController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addJSON('default_fk_check_value', ForeignKey::isCheckEnabled());

        return $this->response->response();
    }
}
