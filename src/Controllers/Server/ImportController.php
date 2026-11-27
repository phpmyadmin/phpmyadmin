<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\ImportForm;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;

#[Route('/server/import', ['GET', 'POST'])]
final readonly class ImportController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private DatabaseInterface $dbi,
        private PageSettings $pageSettings,
        private ImportForm $importForm,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->pageSettings->init('Import');
        $pageSettingsErrorHtml = $this->pageSettings->getErrorHTML();
        $pageSettingsHtml = $this->pageSettings->getHTML();

        $this->response->addScriptFiles(['import.js']);

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $templateParams = $this->importForm->getTemplateParams($request, 'server', []);
        if ($templateParams === null) {
            $this->response->addHTML(Message::error(__(
                'Could not load import plugins, please check your installation!',
            )));

            return $this->response->response();
        }

        $this->response->render('server/import/index', [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
        ] + $templateParams);

        return $this->response->response();
    }
}
