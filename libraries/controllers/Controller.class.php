<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\TableController
 *
 * @package PMA
 */

namespace PMA\Controllers;

use PMA_DatabaseInterface;
use PMA_Response;

if (!defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Response.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/controllers/Controller.class.php';

/**
 * Base class for all of controller
 *
 * @package PhpMyAdmin
 */
abstract class Controller
{

    /**
     * @var PMA_Response $response
     */
    protected $response;

    /**
     * @var $dbi PMA_DatabaseInterface
     */
    protected $dbi;

    function __construct()
    {
        $this->dbi = $GLOBALS['dbi'];
        $this->response = PMA_Response::getInstance();
    }
}
