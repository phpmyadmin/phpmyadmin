<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\TableController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

/**
 * Handles table related logic
 *
 * @package PhpMyAdmin\Controllers
 */
abstract class TableController extends Controller
{
    /**
     * @var string
     */
    protected $db;

    /**
     * @var string
     */
    protected $table;

    /**
     * Constructor
     *
     * @param \PhpMyAdmin\Response          $response Response object
     * @param \PhpMyAdmin\DatabaseInterface $dbi      DatabaseInterface object
     * @param string                        $db       Database name
     * @param string                        $table    Table name
     */
    public function __construct(
        $response,
        $dbi,
        $db,
        $table
    ) {
        parent::__construct($response, $dbi);
        $this->db = $db;
        $this->table = $table;
    }
}
