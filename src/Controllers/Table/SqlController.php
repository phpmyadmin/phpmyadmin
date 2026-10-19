<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;
use function htmlspecialchars;

/**
 * Table SQL executor
 */
#[Route('/table/sql', ['GET', 'POST'])]
readonly class SqlController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private SqlQueryForm $sqlQueryForm,
        private PageSettings $pageSettings,
        private DbTableExists $dbTableExists,
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

        if (Current::$table === '') {
            return $this->response->missingParameterError('table');
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            return $this->response->respondWithErrorMessage(__('No database selected.'));
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            return $this->response->respondWithErrorMessage(__('No table selected.'));
        }

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        UrlParams::$goto = Url::getFromRoute('/table/sql');
        UrlParams::$back = Url::getFromRoute('/table/sql');
        $delimiter = $request->getParsedBodyParamAsString('delimiter', ';');

        $this->response->addHTML($this->sqlQueryForm->getHtml(
            Current::$database,
            Current::$table,
            $request->getQueryParam('sql_query', true),
            false,
            htmlspecialchars($delimiter),
        ));

        return $this->response->response();
    }
}
