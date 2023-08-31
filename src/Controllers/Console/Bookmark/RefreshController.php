<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Console\Bookmark;

use PhpMyAdmin\Console;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;

final class RefreshController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $this->response->addJSON('console_message_bookmark', Console::getBookmarkContent());
    }
}
