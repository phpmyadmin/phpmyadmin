<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Export\Options;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function count;
use function is_array;

final class ExportController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Export $export,
        private Options $exportOptions,
        private PageSettings $pageSettings,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['table_select'] ??= null;
        $GLOBALS['unlim_num_rows'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->pageSettings->init('Export');
        $pageSettingsErrorHtml = $this->pageSettings->getErrorHTML();
        $pageSettingsHtml = $this->pageSettings->getHTML();

        $this->addScriptFiles(['export.js']);

        if (! $this->checkParameters(['db'])) {
            return;
        }

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
            Config::getInstance()->settings['DefaultTabDatabase'],
            'database',
        );
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/database/export');

        $tableNames = $this->export->getTableNames(Current::$database);
        $GLOBALS['num_tables'] = count($tableNames);

        // exit if no tables in db found
        if ($GLOBALS['num_tables'] < 1) {
            $this->response->addHTML(
                Message::error(__('No tables found in database.'))->getDisplay(),
            );

            return;
        }

        $selectedTable = $request->getParsedBodyParam('selected_tbl');
        if (! empty($selectedTable) && empty($GLOBALS['table_select'])) {
            $GLOBALS['table_select'] = $selectedTable;
        }

        $tablesForMultiValues = [];

        foreach ($tableNames as $tableName) {
            $tableSelect = $request->getParsedBodyParam('table_select');
            if (is_array($tableSelect)) {
                $isChecked = $this->export->getCheckedClause($tableName, $tableSelect);
            } elseif (isset($GLOBALS['table_select'])) {
                $isChecked = $this->export->getCheckedClause($tableName, $GLOBALS['table_select']);
            } else {
                $isChecked = true;
            }

            $tableStructure = $request->getParsedBodyParam('table_structure');
            if (is_array($tableStructure)) {
                $structureChecked = $this->export->getCheckedClause($tableName, $tableStructure);
            } else {
                $structureChecked = $isChecked;
            }

            $tableData = $request->getParsedBodyParam('table_data');
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

        if (! isset($GLOBALS['sql_query'])) {
            $GLOBALS['sql_query'] = '';
        }

        if (! isset($GLOBALS['unlim_num_rows'])) {
            $GLOBALS['unlim_num_rows'] = 0;
        }

        $isReturnBackFromRawExport = $request->getParsedBodyParam('export_type') === 'raw';
        if ($request->hasBodyParam('raw_query') || $isReturnBackFromRawExport) {
            $exportType = 'raw';
        } else {
            $exportType = 'database';
        }

        $GLOBALS['single_table'] = $request->getParam('single_table') ?? $GLOBALS['single_table'] ?? null;

        $exportList = Plugins::getExport($exportType, isset($GLOBALS['single_table']));

        if ($exportList === []) {
            $this->response->addHTML(Message::error(
                __('Could not load export plugins, please check your installation!'),
            )->getDisplay());

            return;
        }

        $options = $this->exportOptions->getOptions(
            $exportType,
            Current::$database,
            Current::$table,
            $GLOBALS['sql_query'],
            $GLOBALS['num_tables'],
            $GLOBALS['unlim_num_rows'],
            $exportList,
        );

        $this->render('database/export/index', array_merge($options, [
            'page_settings_error_html' => $pageSettingsErrorHtml,
            'page_settings_html' => $pageSettingsHtml,
            'structure_or_data_forced' => $request->getParsedBodyParam('structure_or_data_forced', 0),
            'tables' => $tablesForMultiValues,
        ]));
    }
}
