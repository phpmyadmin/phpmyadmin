<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Controllers\AbstractController as Controller;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;

abstract class AbstractController extends Controller
{
    /** @var Data */
    protected $data;

    /**
     * @param ResponseRenderer $response
     * @param Data             $data
     */
    public function __construct($response, Template $template, $data)
    {
        parent::__construct($response, $template);
        $this->data = $data;
    }
}
