<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\TableController
 *
 * @package PMA
 */

namespace PMA\Controllers;

use PMA\DI\Container;

if (!defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/di/Container.class.php';
require_once 'libraries/controllers/Controller.class.php';

/**
 * Handles table related logic
 *
 * @package PhpMyAdmin
 */
abstract class TableController extends Controller
{
    function __construct(Container $container = null)
    {
        parent::__construct($container);
    }
}