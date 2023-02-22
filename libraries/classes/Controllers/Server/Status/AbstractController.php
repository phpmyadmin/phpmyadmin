<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Controllers\AbstractController as Controller;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;

abstract class AbstractController extends Controller
{
    public function __construct(ResponseRenderer $response, Template $template, protected Data $data)
    {
        parent::__construct($response, $template);
    }
}
