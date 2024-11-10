<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function implode;
use function is_array;

class ExportController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Options $export,
        private readonly PageSettings $pageSettings,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['where_clause'] ??= null;
        $GLOBALS['unlim_num_rows'] ??= null;

        $this->pageSettings->init('Export');
        $pageSettingsErrorHtml = $this->pageSettings->getErrorHTML();
        $pageSettingsHtml = $this->pageSettings->getHTML();

        $this->response->addScriptFiles(['export.js']);

        if (! $this->response->checkParameters(['db', 'table'])) {
            return $this->response->response();
        }

        UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
            Config::getInstance()->settings['DefaultTabTable'],
            'table',
        );
        $GLOBALS['errorUrl'] .= Url::getCommon(UrlParams::$params, '&');

        UrlParams::$params['goto'] = Url::getFromRoute('/table/export');
        UrlParams::$params['back'] = Url::getFromRoute('/table/export');

        // When we have some query, we need to remove LIMIT from that and possibly
        // generate WHERE clause (if we are asked to export specific rows)

        if (! empty($GLOBALS['sql_query'])) {
            $parser = new Parser($GLOBALS['sql_query']);

            if (! empty($parser->statements[0]) && $parser->statements[0] instanceof SelectStatement) {
                // Checking if the WHERE clause has to be replaced.
                $replaces = [];
                if (! empty($GLOBALS['where_clause']) && is_array($GLOBALS['where_clause'])) {
                    $replaces[] = ['WHERE', 'WHERE (' . implode(') OR (', $GLOBALS['where_clause']) . ')'];
                }

                // Preparing to remove the LIMIT clause.
                $replaces[] = ['LIMIT', ''];

                // Replacing the clauses.
                $GLOBALS['sql_query'] = Query::replaceClauses($parser->statements[0], $parser->list, $replaces);
            }
        }

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

        $exportList = Plugins::getExport('table', isset($GLOBALS['single_table']));

        if ($exportList === []) {
            $this->response->addHTML(Message::error(
                __('Could not load export plugins, please check your installation!'),
            )->getDisplay());

            return $this->response->response();
        }

        $exportType = 'table';
        $isReturnBackFromRawExport = $request->getParsedBodyParam('export_type') === 'raw';
        if ($request->hasBodyParam('raw_query') || $isReturnBackFromRawExport) {
            $exportType = 'raw';
        }

        $options = $this->export->getOptions(
            $exportType,
            Current::$database,
            Current::$table,
            $GLOBALS['sql_query'],
            $GLOBALS['num_tables'],
            $GLOBALS['unlim_num_rows'],
            $exportList,
        );

        $this->response->render('table/export/index', array_merge($options, [
            'export_type' => $exportType,
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
        ]));

        return $this->response->response();
    }
}
