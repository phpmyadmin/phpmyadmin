<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Controller
 *
 * @package PhpMyAdmin\Controllers
 */
namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

require_once 'libraries/database_interface.inc.php';

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
     * @var \PhpMyAdmin\Di\Container
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
