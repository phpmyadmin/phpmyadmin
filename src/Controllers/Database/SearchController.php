<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Search;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;

final readonly class SearchController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Template $template,
        private DatabaseInterface $dbi,
        private DbTableExists $dbTableExists,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addScriptFiles(['database/search.js', 'sql.js', 'makegrid.js']);

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        $errorUrl = Url::getFromRoute($this->config->settings['DefaultTabDatabase']);
        $errorUrl .= Url::getCommon(['db' => Current::$database], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return $this->response->response();
        }

        if (! $this->config->settings['UseDbSearch']) {
            $errorMessage = __(
                'Searching inside the database is disabled by the [code]$cfg[\'UseDbSearch\'][/code] configuration.',
            );
            $errorMessage .= MySQLDocumentation::showDocumentation('config', 'cfg_UseDbSearch');
            $this->response->setRequestStatus(false);
            if ($request->isAjax()) {
                $this->response->addJSON('message', Message::error($errorMessage)->getDisplay());

                return $this->response->response();
            }

            $this->response->render('error/simple', [
                'error_message' => $errorMessage,
                'back_url' => $errorUrl,
            ]);

            return $this->response->response();
        }

        UrlParams::$params['goto'] = Url::getFromRoute('/database/search');

        // Create a database search instance
        $databaseSearch = new Search($this->dbi, Current::$database, $this->template);

        // Main search form has been submitted, get results
        if ($request->hasBodyParam('submit_search')) {
            $this->response->addHTML($databaseSearch->getSearchResults());
        }

        // If we are in an Ajax request, we need to exit after displaying all the HTML
        if ($request->isAjax() && empty($_REQUEST['ajax_page_request'])) {
            return $this->response->response();
        }

        // Display the search form
        $this->response->addHTML($databaseSearch->getMainHtml());

        return $this->response->response();
    }
}
