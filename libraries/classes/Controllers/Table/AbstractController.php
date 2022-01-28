<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

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

    /**
     * @deprecated Get the value from $GLOBALS['table'] or from the {@see \PhpMyAdmin\Http\ServerRequest} instance.
     *
     * @var string
     */
    protected $table;

    public function __construct(ResponseRenderer $response, Template $template, string $db, string $table)
    {
        parent::__construct($response, $template);
        $this->db = $db;
        $this->table = $table;
    }
}
