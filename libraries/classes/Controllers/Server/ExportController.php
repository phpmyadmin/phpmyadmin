<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function array_merge;

final class ExportController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Options $export,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['unlim_num_rows'] ??= null;
        $GLOBALS['errorUrl'] = Url::getFromRoute('/');
        $GLOBALS['tmp_select'] ??= null;
        $GLOBALS['select_item'] ??= null;

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $pageSettings = new PageSettings('Export');
        $pageSettingsErrorHtml = $pageSettings->getErrorHTML();
        $pageSettingsHtml = $pageSettings->getHTML();

        $this->addScriptFiles(['export.js']);

        $GLOBALS['select_item'] = $GLOBALS['tmp_select'] ?? '';
        $databases = $this->export->getDatabasesForSelectOptions($GLOBALS['select_item']);

        if (! isset($GLOBALS['sql_query'])) {
            $GLOBALS['sql_query'] = '';
        }

        if (! isset($GLOBALS['num_tables'])) {
            $GLOBALS['num_tables'] = 0;
        }

        if (! isset($GLOBALS['unlim_num_rows'])) {
            $GLOBALS['unlim_num_rows'] = 0;
        }

        $GLOBALS['single_table'] = $request->getParam('single_table') ?? $GLOBALS['single_table'] ?? null;

        $exportList = Plugins::getExport('server', isset($GLOBALS['single_table']));

        if ($exportList === []) {
            $this->response->addHTML(Message::error(
                __('Could not load export plugins, please check your installation!'),
            )->getDisplay());

            return;
        }

        $options = $this->export->getOptions(
            'server',
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['sql_query'],
            $GLOBALS['num_tables'],
            $GLOBALS['unlim_num_rows'],
            $exportList,
        );

        $this->render('server/export/index', array_merge($options, [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
            'databases' => $databases,
        ]));
    }
}
