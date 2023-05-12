<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function implode;
use function is_array;

class ExportController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Options $export,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['replaces'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['where_clause'] ??= null;
        $GLOBALS['unlim_num_rows'] ??= null;

        $pageSettings = new PageSettings('Export');
        $pageSettingsErrorHtml = $pageSettings->getErrorHTML();
        $pageSettingsHtml = $pageSettings->getHTML();

        $this->addScriptFiles(['export.js']);

        $this->checkParameters(['db', 'table']);

        $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/table/export');
        $GLOBALS['urlParams']['back'] = Url::getFromRoute('/table/export');

        // When we have some query, we need to remove LIMIT from that and possibly
        // generate WHERE clause (if we are asked to export specific rows)

        if (! empty($GLOBALS['sql_query'])) {
            $parser = new Parser($GLOBALS['sql_query']);

            if (! empty($parser->statements[0]) && ($parser->statements[0] instanceof SelectStatement)) {
                // Checking if the WHERE clause has to be replaced.
                if (! empty($GLOBALS['where_clause']) && is_array($GLOBALS['where_clause'])) {
                    $GLOBALS['replaces'][] = ['WHERE', 'WHERE (' . implode(') OR (', $GLOBALS['where_clause']) . ')'];
                }

                // Preparing to remove the LIMIT clause.
                $GLOBALS['replaces'][] = ['LIMIT', ''];

                // Replacing the clauses.
                $GLOBALS['sql_query'] = Query::replaceClauses(
                    $parser->statements[0],
                    $parser->list,
                    $GLOBALS['replaces'],
                );
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

            return;
        }

        $exportType = 'table';
        $isReturnBackFromRawExport = $request->getParsedBodyParam('export_type') === 'raw';
        if ($request->hasBodyParam('raw_query') || $isReturnBackFromRawExport) {
            $exportType = 'raw';
        }

        $options = $this->export->getOptions(
            $exportType,
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['sql_query'],
            $GLOBALS['num_tables'],
            $GLOBALS['unlim_num_rows'],
            $exportList,
        );

        $this->render('table/export/index', array_merge($options, [
            'export_type' => $exportType,
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
        ]));
    }
}
