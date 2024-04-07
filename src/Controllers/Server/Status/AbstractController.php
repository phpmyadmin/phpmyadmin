<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;

abstract class AbstractController
{
    public function __construct(
        protected readonly ResponseRenderer $response,
        protected readonly Template $template,
        protected readonly Data $data,
    ) {
    }
}
