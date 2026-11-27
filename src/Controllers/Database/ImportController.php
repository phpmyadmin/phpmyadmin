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
use PhpMyAdmin\Import\ImportForm;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;

#[Route('/database/import', ['GET', 'POST'])]
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

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            return $this->response->respondWithErrorMessage(__('No database selected.'));
        }

        $templateParams = $this->importForm->getTemplateParams(
            $request,
            'database',
            ['db' => $databaseName->getName()],
        );
        if ($templateParams === null) {
            $this->response->addHTML(Message::error(__(
                'Could not load import plugins, please check your installation!',
            )));

            return $this->response->response();
        }

        $this->response->render('database/import/index', [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
        ] + $templateParams);

        return $this->response->response();
    }
}
