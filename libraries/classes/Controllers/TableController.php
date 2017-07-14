<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\TableController
 *
 * @package PhpMyAdmin\Controllers
 */
namespace PhpMyAdmin\Controllers;

/**
 * Handles table related logic
 *
 * @package PhpMyAdmin\Controllers
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
