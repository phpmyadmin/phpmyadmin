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

require_once 'libraries/controllers/Controller.class.php';

/**
 * Handles table related logic
 *
 * @package PhpMyAdmin
 */
abstract class TableController extends Controller
{
    /**
     * @var string $db
     */
    protected $db;

    /**
     * @var string $table
     */
    protected $table;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->db = $this->container->get('db');
        $this->table = $this->container->get('table');
    }
}
