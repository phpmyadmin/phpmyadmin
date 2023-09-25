<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Console\Bookmark;

use PhpMyAdmin\Console;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

final class RefreshController extends AbstractController
{
    public function __construct(
        protected ResponseRenderer $response,
        protected Template $template,
        private Console $console,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->response->addJSON('console_message_bookmark', $this->console->getBookmarkContent());
    }
}
