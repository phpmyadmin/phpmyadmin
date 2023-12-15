<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Search;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;

class SearchController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['urlParams'] ??= null;

        $this->addScriptFiles(['database/search.js', 'sql.js', 'makegrid.js']);

        if (! $this->checkParameters(['db'])) {
            return;
        }

        $config = Config::getInstance();
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return;
        }

        if (! $config->settings['UseDbSearch']) {
            $errorMessage = __(
                'Searching inside the database is disabled by the [code]$cfg[\'UseDbSearch\'][/code] configuration.',
            );
            $errorMessage .= MySQLDocumentation::showDocumentation('config', 'cfg_UseDbSearch');
            $this->response->setRequestStatus(false);
            if ($request->isAjax()) {
                $this->response->addJSON('message', Message::error($errorMessage)->getDisplay());

                return;
            }

            $this->render('error/simple', ['error_message' => $errorMessage, 'back_url' => $GLOBALS['errorUrl']]);

            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/database/search');

        // Create a database search instance
        $databaseSearch = new Search($this->dbi, Current::$database, $this->template);

        // Main search form has been submitted, get results
        if ($request->hasBodyParam('submit_search')) {
            $this->response->addHTML($databaseSearch->getSearchResults());
        }

        // If we are in an Ajax request, we need to exit after displaying all the HTML
        if ($request->isAjax() && empty($_REQUEST['ajax_page_request'])) {
            return;
        }

        // Display the search form
        $this->response->addHTML($databaseSearch->getMainHtml());
    }
}
