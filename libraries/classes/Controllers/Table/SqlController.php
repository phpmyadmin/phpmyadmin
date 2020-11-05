<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function htmlspecialchars;

/**
 * Table SQL executor
 */
final class SqlController extends AbstractController
{
    /** @var SqlQueryForm */
    private $sqlQueryForm;

    /**
     * @param Response $response
     * @param string   $db       Database name.
     * @param string   $table    Table name.
     */
    public function __construct($response, Template $template, $db, $table, SqlQueryForm $sqlQueryForm)
    {
        parent::__construct($response, $template, $db, $table);
        $this->sqlQueryForm = $sqlQueryForm;
    }

    public function index(): void
    {
        global $err_url, $goto, $back, $db, $table, $cfg;

        $this->addScriptFiles([
            'makegrid.js',
            'vendor/jquery/jquery.uitablefilter.js',
            'vendor/stickyfill.min.js',
            'sql.js',
        ]);

        $pageSettings = new PageSettings('Sql');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        Util::checkParameters(['db', 'table']);

        $url_params = ['db' => $db, 'table' => $table];
        $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $err_url .= Url::getCommon($url_params, '&');

        DbTableExists::check();

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        $goto = Url::getFromRoute('/table/sql');
        $back = Url::getFromRoute('/table/sql');

        $this->response->addHTML($this->sqlQueryForm->getHtml(
            $_GET['sql_query'] ?? true,
            false,
            isset($_POST['delimiter'])
                ? htmlspecialchars($_POST['delimiter'])
                : ';'
        ));
    }
}
