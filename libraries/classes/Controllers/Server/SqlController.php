<?php
/**
 * Holds the PhpMyAdmin\Controllers\Server\SqlController
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Common;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;

/**
 * Server SQL executor
 */
class SqlController extends AbstractController
{
    /** @var SqlQueryForm */
    private $sqlQueryForm;

    /**
     * @param Response          $response     Response object
     * @param DatabaseInterface $dbi          DatabaseInterface object
     * @param Template          $template     Template that should be used (if provided, default one otherwise)
     * @param SqlQueryForm      $sqlQueryForm SqlQueryForm instance
     */
    public function __construct($response, $dbi, Template $template, SqlQueryForm $sqlQueryForm)
    {
        parent::__construct($response, $dbi, $template);
        $this->sqlQueryForm = $sqlQueryForm;
    }

    public function index(): void
    {
        $this->addScriptFiles([
            'makegrid.js',
            'vendor/jquery/jquery.uitablefilter.js',
            'vendor/stickyfill.min.js',
            'sql.js',
        ]);

        $pageSettings = new PageSettings('Sql');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        Common::server();

        $this->response->addHTML($this->sqlQueryForm->getHtml());
    }
}
