<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Controller
 *
 * @package PhpMyAdmin\Controllers
 */
namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;

/**
 * Base class for all of controller
 *
 * @package PhpMyAdmin\Controllers
 */
abstract class Controller
{

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var DatabaseInterface
     */
    protected $dbi;

    /**
     * Constructor
     */
    public function __construct($response, $dbi)
    {
        $this->response = $response;
        $this->dbi = $dbi;
    }
}
