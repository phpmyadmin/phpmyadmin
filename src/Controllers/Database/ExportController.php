<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;
use function array_merge;
use function count;
use function is_array;

#[Route('/database/export', ['GET', 'POST'])]
final class ExportController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Export $export,
        private readonly Options $exportOptions,
        private readonly PageSettings $pageSettings,
        private readonly DbTableExists $dbTableExists,
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

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
        }

        UrlParams::$params['goto'] = Url::getFromRoute('/database/export');

        $tableNames = $this->export->getTableNames(Current::$database);
        Current::$numTables = count($tableNames);

        // exit if no tables in db found
        if (Current::$numTables < 1) {
            $this->response->addHTML(
                Message::error(__('No tables found in database.'))->getDisplay(),
            );

            return $this->response->response();
        }

        $selectedTable = $request->getParsedBodyParam('selected_tbl');
        $tableSelect = $request->getParsedBodyParam('table_select');
        $tableStructure = $request->getParsedBodyParam('table_structure');
        $tableData = $request->getParsedBodyParam('table_data');
        $tablesForMultiValues = [];

        foreach ($tableNames as $tableName) {
            if (is_array($tableSelect)) {
                $isChecked = $this->export->getCheckedClause($tableName, $tableSelect);
            } elseif (is_array($selectedTable)) {
                $isChecked = $this->export->getCheckedClause($tableName, $selectedTable);
            } else {
                $isChecked = true;
            }

            if (is_array($tableStructure)) {
                $structureChecked = $this->export->getCheckedClause($tableName, $tableStructure);
            } else {
                $structureChecked = $isChecked;
            }

            if (is_array($tableData)) {
                $dataChecked = $this->export->getCheckedClause($tableName, $tableData);
            } else {
                $dataChecked = $isChecked;
            }

            $tablesForMultiValues[] = [
                'name' => $tableName,
                'is_checked_select' => $isChecked,
                'is_checked_structure' => $structureChecked,
                'is_checked_data' => $dataChecked,
            ];
        }

        $isReturnBackFromRawExport = $request->getParsedBodyParam('export_type') === 'raw';
        if ($request->hasBodyParam('raw_query') || $isReturnBackFromRawExport) {
            $exportType = ExportType::Raw;
        } else {
            $exportType = ExportType::Database;
        }

        if ($request->has('single_table')) {
            Export::$singleTable = (bool) $request->getParam('single_table');
        }

        $exportList = Plugins::getExport($exportType, Export::$singleTable);

        if ($exportList === []) {
            $this->response->addHTML(Message::error(
                __('Could not load export plugins, please check your installation!'),
            )->getDisplay());

            return $this->response->response();
        }

        $options = $this->exportOptions->getOptions(
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

        $this->response->render('database/export/index', array_merge($options, [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
            'structure_or_data_forced' => $request->getParsedBodyParam('structure_or_data_forced', 0),
            'tables' => $tablesForMultiValues,
        ]));

        return $this->response->response();
    }
}
