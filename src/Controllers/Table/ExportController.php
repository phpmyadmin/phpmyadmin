<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;
use function array_merge;
use function implode;
use function is_array;

#[Route('/table/export', ['GET', 'POST'])]
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
        $this->pageSettings->init('Export');
        $pageSettingsErrorHtml = $this->pageSettings->getErrorHTML();
        $pageSettingsHtml = $this->pageSettings->getHTML();

        $this->response->addScriptFiles(['export.js']);

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        if (Current::$table === '') {
            return $this->response->missingParameterError('table');
        }

        UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];

        UrlParams::$params['goto'] = Url::getFromRoute('/table/export');
        UrlParams::$params['back'] = Url::getFromRoute('/table/export');

        // When we have some query, we need to remove LIMIT from that and possibly
        // generate WHERE clause (if we are asked to export specific rows)

        if (Current::$sqlQuery !== '') {
            $parser = new Parser(Current::$sqlQuery);

            if (! empty($parser->statements[0]) && $parser->statements[0] instanceof SelectStatement) {
                // Checking if the WHERE clause has to be replaced.
                $replaces = [];
                if (is_array(Current::$whereClause) && Current::$whereClause !== []) {
                    $replaces[] = ['WHERE', 'WHERE (' . implode(') OR (', Current::$whereClause) . ')'];
                }

                // Preparing to remove the LIMIT clause.
                $replaces[] = ['LIMIT', ''];

                // Replacing the clauses.
                Current::$sqlQuery = Query::replaceClauses($parser->statements[0], $parser->list, $replaces);
            }
        }

        if ($request->has('single_table')) {
            Export::$singleTable = (bool) $request->getParam('single_table');
        }

        $exportList = Plugins::getExport(ExportType::Table, Export::$singleTable);

        if ($exportList === []) {
            $this->response->addHTML(Message::error(
                __('Could not load export plugins, please check your installation!'),
            )->getDisplay());

            return $this->response->response();
        }

        $exportType = ExportType::Table;
        $isReturnBackFromRawExport = $request->getParsedBodyParam('export_type') === 'raw';
        if ($request->hasBodyParam('raw_query') || $isReturnBackFromRawExport) {
            $exportType = ExportType::Raw;
        }

        $options = $this->export->getOptions(
            $exportType,
            Current::$database,
            Current::$table,
            Current::$sqlQuery,
            Current::$numTables,
            0,
            $exportList,
            $request->getParam('format'),
            $request->getParam('what'),
        );

        $this->response->render('table/export/index', array_merge($options, [
            'export_type' => $exportType->value,
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
        ]));

        return $this->response->response();
    }
}
