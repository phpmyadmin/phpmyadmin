<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\ResponseRenderer;
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

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        SqlQueryForm $sqlQueryForm
    ) {
        parent::__construct($response, $template);
        $this->sqlQueryForm = $sqlQueryForm;
    }

    public function __invoke(): void
    {
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $GLOBALS['goto'] = $GLOBALS['goto'] ?? null;
        $GLOBALS['back'] = $GLOBALS['back'] ?? null;

        $this->addScriptFiles(['makegrid.js', 'vendor/jquery/jquery.uitablefilter.js', 'sql.js']);

        $pageSettings = new PageSettings('Sql');
        $this->response->addHTML($pageSettings->getErrorHTML());
        $this->response->addHTML($pageSettings->getHTML());

        $this->checkParameters(['db', 'table']);

        $url_params = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($url_params, '&');

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        $GLOBALS['goto'] = Url::getFromRoute('/table/sql');
        $GLOBALS['back'] = Url::getFromRoute('/table/sql');

        $this->response->addHTML($this->sqlQueryForm->getHtml(
            $GLOBALS['db'],
            $GLOBALS['table'],
            $_GET['sql_query'] ?? true,
            false,
            isset($_POST['delimiter'])
                ? htmlspecialchars($_POST['delimiter'])
                : ';'
        ));
    }
}
