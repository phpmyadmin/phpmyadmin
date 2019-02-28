<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Database\Controller
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\Controller as BaseController;

/**
 * Handles database related logic
 *
 * @package PhpMyAdmin\Controllers
 */
abstract class Controller extends BaseController
{
    /**
     * @var string
     */
    protected $db;

    /**
     * Constructor
     *
     * @param \PhpMyAdmin\Response          $response Response object
     * @param \PhpMyAdmin\DatabaseInterface $dbi      DatabaseInterface object
     * @param string                        $db       Database name
     */
    public function __construct($response, $dbi, $db)
    {
        parent::__construct($response, $dbi);
        $this->db = $db;
    }
}
