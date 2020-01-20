<?php
/**
 * Holds the PhpMyAdmin\Controllers\AbstractController
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

/**
 * Base class for all of controller
 */
abstract class AbstractController
{
    /** @var Response */
    protected $response;

    /** @var DatabaseInterface */
    protected $dbi;

    /** @var Template */
    protected $template;

    /**
     * @param Response          $response Response object
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Template          $template Template that should be used
     */
    public function __construct($response, $dbi, Template $template)
    {
        $this->response = $response;
        $this->dbi = $dbi;
        $this->template = $template;
    }
}
