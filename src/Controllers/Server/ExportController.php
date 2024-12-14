<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\ResponseRenderer;

use function __;
use function array_merge;

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
        $GLOBALS['unlim_num_rows'] ??= null;
        $GLOBALS['tmp_select'] ??= null;
        $GLOBALS['select_item'] ??= null;

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $this->pageSettings->init('Export');
        $pageSettingsErrorHtml = $this->pageSettings->getErrorHTML();
        $pageSettingsHtml = $this->pageSettings->getHTML();

        $this->response->addScriptFiles(['export.js']);

        $GLOBALS['select_item'] = $GLOBALS['tmp_select'] ?? '';
        $databases = $this->export->getDatabasesForSelectOptions($GLOBALS['select_item']);

        if (! isset($GLOBALS['num_tables'])) {
            $GLOBALS['num_tables'] = 0;
        }

        if (! isset($GLOBALS['unlim_num_rows'])) {
            $GLOBALS['unlim_num_rows'] = 0;
        }

        $GLOBALS['single_table'] = $request->getParam('single_table') ?? $GLOBALS['single_table'] ?? null;

        $exportList = Plugins::getExport(ExportType::Server, isset($GLOBALS['single_table']));

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
            $GLOBALS['num_tables'],
            $GLOBALS['unlim_num_rows'],
            $exportList,
        );

        $this->response->render('server/export/index', array_merge($options, [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
            'databases' => $databases,
        ]));

        return $this->response->response();
    }
}
