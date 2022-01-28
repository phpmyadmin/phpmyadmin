<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController as Controller;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

abstract class AbstractController extends Controller
{
    /**
     * @deprecated Get the value from $GLOBALS['db'] or from the {@see \PhpMyAdmin\Http\ServerRequest} instance.
     *
     * @var string
     */
    protected $db;

    public function __construct(ResponseRenderer $response, Template $template, string $db)
    {
        parent::__construct($response, $template);
        $this->db = $db;
    }
}
