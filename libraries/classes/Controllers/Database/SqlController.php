<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function htmlspecialchars;

/**
 * Database SQL executor
 */
class SqlController extends AbstractController
{
    /** @var SqlQueryForm */
    private $sqlQueryForm;

    /**
     * @param Response $response
     * @param string   $db       Database name
     */
    public function __construct($response, Template $template, $db, SqlQueryForm $sqlQueryForm)
    {
        parent::__construct($response, $template, $db);
        $this->sqlQueryForm = $sqlQueryForm;
    }

    public function index(): void
    {
        global $goto, $back, $db, $cfg, $err_url;

        $this->addScriptFiles([
            'makegrid.js',
            'vendor/jquery/jquery.uitablefilter.js',
            'vendor/stickyfill.min.js',
            'sql.js',
        ]);

        $pageSettings = new PageSettings('Sql');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        Util::checkParameters(['db']);

        $err_url = Util::getScriptNameForOption($cfg['DefaultTabDatabase'], 'database');
        $err_url .= Url::getCommon(['db' => $db], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        $goto = Url::getFromRoute('/database/sql');
        $back = $goto;

        $this->response->addHTML($this->sqlQueryForm->getHtml(
            true,
            false,
            isset($_POST['delimiter'])
                ? htmlspecialchars($_POST['delimiter'])
                : ';'
        ));
    }
}
