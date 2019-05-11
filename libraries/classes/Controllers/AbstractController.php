<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\AbstractController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * Base class for all of controller
 *
 * @package PhpMyAdmin\Controllers
 */
abstract class AbstractController
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
     * @var Template
     */
    protected $template;

    /**
     * AbstractController constructor.
     *
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template that should be used (if provided, default one otherwise)
     */
    public function __construct($response, $dbi, Template $template)
    {
        $this->response = $response;
        $this->dbi = $dbi;
        $this->template = $template;
    }
}
