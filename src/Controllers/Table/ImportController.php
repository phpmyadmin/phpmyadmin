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
use PhpMyAdmin\Import\ImportForm;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;

#[Route('/table/import', ['GET', 'POST'])]
final readonly class ImportController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private PageSettings $pageSettings,
        private DbTableExists $dbTableExists,
        private ImportForm $importForm,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->pageSettings->init('Import');
        $pageSettingsErrorHtml = $this->pageSettings->getErrorHTML();
        $pageSettingsHtml = $this->pageSettings->getHTML();

        $this->response->addScriptFiles(['import.js']);

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        if (Current::$table === '') {
            return $this->response->missingParameterError('table');
        }

        UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            return $this->response->respondWithErrorMessage(__('No database selected.'));
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            return $this->response->respondWithErrorMessage(__('No table selected.'));
        }

        UrlParams::$params['goto'] = Url::getFromRoute('/table/import');
        UrlParams::$params['back'] = Url::getFromRoute('/table/import');

        $templateParams = $this->importForm->getTemplateParams(
            $request,
            'table',
            ['db' => $databaseName->getName(), 'table' => $tableName->getName()],
        );
        if ($templateParams === null) {
            $this->response->addHTML(Message::error(__(
                'Could not load import plugins, please check your installation!',
            )));

            return $this->response->response();
        }

        $this->response->render('table/import/index', [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
        ] + $templateParams);

        return $this->response->response();
    }
}
