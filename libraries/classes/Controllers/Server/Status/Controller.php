<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\Status\Controller
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\Controllers\Controller as BaseController;
use PhpMyAdmin\Server\Status\Data;

/**
 * Abstract class Controller
 * @package PhpMyAdmin\Controllers\Server\Status
 */
abstract class Controller extends BaseController
{
    /**
     * @var Data
     */
    protected $data;

    /**
     * Controller constructor.
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
