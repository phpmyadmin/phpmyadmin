<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;
use function htmlspecialchars;

/**
 * Database SQL executor
 */
#[Route('/database/sql', ['GET', 'POST'])]
class SqlController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly SqlQueryForm $sqlQueryForm,
        private readonly PageSettings $pageSettings,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addScriptFiles(['makegrid.js', 'vendor/jquery/jquery.uitablefilter.js', 'sql.js']);

        $this->pageSettings->init('Sql');
        $this->response->addHTML($this->pageSettings->getErrorHTML());
        $this->response->addHTML($this->pageSettings->getHTML());

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
        }

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        UrlParams::$goto = Url::getFromRoute('/database/sql');
        UrlParams::$back = UrlParams::$goto;
        $delimiter = $request->getParsedBodyParamAsString('delimiter', ';');

        $this->response->addHTML($this->sqlQueryForm->getHtml(
            Current::$database,
            '',
            true,
            false,
            htmlspecialchars($delimiter),
        ));

        return $this->response->response();
    }
}
