<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;

use function __;
use function array_merge;

#[Route('/server/export', ['GET', 'POST'])]
final class ExportController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Options $export,
        private readonly DatabaseInterface $dbi,
        private readonly PageSettings $pageSettings,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->pageSettings->init('Export');
        $pageSettingsErrorHtml = $this->pageSettings->getErrorHTML();
        $pageSettingsHtml = $this->pageSettings->getHTML();

        $this->response->addScriptFiles(['export.js']);

        $databases = $this->export->getDatabasesForSelectOptions();

        if ($request->has('single_table')) {
            Export::$singleTable = (bool) $request->getParam('single_table');
        }

        $exportList = Plugins::getExport(ExportType::Server, Export::$singleTable);

        if ($exportList === []) {
            $this->response->addHTML(Message::error(
                __('Could not load export plugins, please check your installation!'),
            )->getDisplay());

            return $this->response->response();
        }

        $options = $this->export->getOptions(
            ExportType::Server,
            Current::$database,
            Current::$table,
            Current::$sqlQuery,
            Current::$numTables,
            0,
            $exportList,
            $request->getParam('format'),
            $request->getParam('what'),
        );

        $this->response->render('server/export/index', array_merge($options, [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
            'databases' => $databases,
        ]));

        return $this->response->response();
    }
}
