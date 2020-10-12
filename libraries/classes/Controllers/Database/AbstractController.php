<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController as Controller;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

abstract class AbstractController extends Controller
{
    /** @var string */
    protected $db;

    /**
     * @param Response $response
     * @param string   $db       Database name
     */
    public function __construct($response, Template $template, $db)
    {
        parent::__construct($response, $template);
        $this->db = $db;
    }
}
