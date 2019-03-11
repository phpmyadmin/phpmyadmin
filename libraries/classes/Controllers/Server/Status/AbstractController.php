<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\Status\AbstractController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Controllers\AbstractController as Controller;
use PhpMyAdmin\Server\Status\Data;

/**
 * Abstract class Controller
 * @package PhpMyAdmin\Controllers\Server\Status
 */
abstract class AbstractController extends Controller
{
    /**
     * @var Data
     */
    protected $data;

    /**
     * AbstractController constructor.
     * @param \PhpMyAdmin\Response          $response Response object
     * @param \PhpMyAdmin\DatabaseInterface $dbi      DatabaseInterface object
     * @param Data                          $data     Data object
     */
    public function __construct($response, $dbi, $data)
    {
        parent::__construct($response, $dbi);
        $this->data = $data;
    }
}
