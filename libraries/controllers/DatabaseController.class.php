<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\DatabaseController
 *
 * @package PMA
 */

namespace PMA\Controllers;

use PMA\DI\Container;

if (!defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/controllers/Controller.class.php';

/**
 * Handles database related logic
 *
 * @package PhpMyAdmin
 */
abstract class DatabaseController extends Controller
{
    /**
     * @var string $db
     */
    protected $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = $this->container->get('db');
    }
}
