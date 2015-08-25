<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\TableController
 *
 * @package PMA
 */
namespace PMA\Controllers;

use PMA\DI\Container;
use PMA\libraries\DatabaseInterface;
use PMA\libraries\Response;

require_once 'libraries/di/Container.class.php';
require_once 'libraries/database_interface.inc.php';

/**
 * Base class for all of controller
 *
 * @package PhpMyAdmin
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
     * @var Container
     */
    protected $container;

    /**
     * Constructor
     */
    public function __construct()
    {
        $container = Container::getDefaultContainer();
        $this->container = $container;
        $this->dbi = $this->container->get('dbi');
        $this->response = $this->container->get('response');
    }
}
