<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

abstract class AbstractController implements InvocableController
{
    public function __construct(protected ResponseRenderer $response, protected Template $template)
    {
    }
}
