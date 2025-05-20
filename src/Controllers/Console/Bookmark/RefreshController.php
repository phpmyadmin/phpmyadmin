<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Console\Bookmark;

use PhpMyAdmin\Console\Console;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;

final class RefreshController implements InvocableController
{
    public function __construct(private readonly ResponseRenderer $response, private readonly Console $console)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addJSON('console_message_bookmark', $this->console->getBookmarkContent());

        return $this->response->response();
    }
}
