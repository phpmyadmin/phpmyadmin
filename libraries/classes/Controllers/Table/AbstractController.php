<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController as Controller;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

abstract class AbstractController extends Controller
{
    /** @var string */
    protected $db;

    /** @var string */
    protected $table;

    /**
     * @param Response $response
     * @param string   $db       Database name
     * @param string   $table    Table name
     */
    public function __construct($response, Template $template, $db, $table)
    {
        parent::__construct($response, $template);
        $this->db = $db;
        $this->table = $table;
    }
}
