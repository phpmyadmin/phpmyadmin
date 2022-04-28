<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Search;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;

class SearchController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(ResponseRenderer $response, Template $template, DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;
        $GLOBALS['num_tables'] = $GLOBALS['num_tables'] ?? null;
        $GLOBALS['total_num_tables'] = $GLOBALS['total_num_tables'] ?? null;
        $GLOBALS['sub_part'] = $GLOBALS['sub_part'] ?? null;
        $GLOBALS['tooltip_truename'] = $GLOBALS['tooltip_truename'] ?? null;
        $GLOBALS['tooltip_aliasname'] = $GLOBALS['tooltip_aliasname'] ?? null;
        $GLOBALS['pos'] = $GLOBALS['pos'] ?? null;

        $this->addScriptFiles(['database/search.js', 'sql.js', 'makegrid.js']);

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        // If config variable $cfg['UseDbSearch'] is on false : exit.
        if (! $GLOBALS['cfg']['UseDbSearch']) {
            Generator::mysqlDie(
                __('Access denied!'),
                '',
                false,
                $GLOBALS['errorUrl']
            );
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/database/search');

        // Create a database search instance
        $databaseSearch = new Search($this->dbi, $GLOBALS['db'], $this->template);

        // Display top links if we are not in an Ajax request
        if (! $this->response->isAjax()) {
            [
                $GLOBALS['tables'],
                $GLOBALS['num_tables'],
                $GLOBALS['total_num_tables'],
                $GLOBALS['sub_part'],,,
                $GLOBALS['tooltip_truename'],
                $GLOBALS['tooltip_aliasname'],
                $GLOBALS['pos'],
            ] = Util::getDbInfo($GLOBALS['db'], $GLOBALS['sub_part'] ?? '');
        }

        // Main search form has been submitted, get results
        if (isset($_POST['submit_search'])) {
            $this->response->addHTML($databaseSearch->getSearchResults());
        }

        // If we are in an Ajax request, we need to exit after displaying all the HTML
        if ($this->response->isAjax() && empty($_REQUEST['ajax_page_request'])) {
            return;
        }

        // Display the search form
        $this->response->addHTML($databaseSearch->getMainHtml());
    }
}
