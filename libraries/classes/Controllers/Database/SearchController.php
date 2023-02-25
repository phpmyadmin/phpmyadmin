<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Search;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;

class SearchController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private DatabaseInterface $dbi)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['urlParams'] ??= null;

        $this->addScriptFiles(['database/search.js', 'sql.js', 'makegrid.js']);

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        if (! $GLOBALS['cfg']['UseDbSearch']) {
            $errorMessage = __(
                'Searching inside the database is disabled by the [code]$cfg[\'UseDbSearch\'][/code] configuration.',
            );
            $errorMessage .= MySQLDocumentation::showDocumentation('config', 'cfg_UseDbSearch');
            $this->response->setRequestStatus(false);
            if ($this->response->isAjax()) {
                $this->response->addJSON('message', Message::error($errorMessage)->getDisplay());

                return;
            }

            $this->render('error/simple', ['error_message' => $errorMessage, 'back_url' => $GLOBALS['errorUrl']]);

            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/database/search');

        // Create a database search instance
        $databaseSearch = new Search($this->dbi, $GLOBALS['db'], $this->template);

        // Main search form has been submitted, get results
        if ($request->hasBodyParam('submit_search')) {
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
