<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Database\Designer\Common as DesignerCommon;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function in_array;
use function json_encode;
use function sprintf;

class DesignerController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Designer $databaseDesigner,
        private DesignerCommon $designerCommon,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['message'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $db = $request->getParsedBodyParam('db');
        $table = $request->getParsedBodyParam('table');

        if ($request->hasBodyParam('dialog')) {
            $html = '';
            $dialog = $request->getParsedBodyParam('dialog');
            if ($dialog === 'edit') {
                $html = $this->databaseDesigner->getHtmlForEditOrDeletePages($db, 'editPage');
            } elseif ($dialog === 'delete') {
                $html = $this->databaseDesigner->getHtmlForEditOrDeletePages($db, 'deletePage');
            } elseif ($dialog === 'save_as') {
                $html = $this->databaseDesigner->getHtmlForPageSaveAs($db);
            } elseif ($dialog === 'export') {
                $html = $this->databaseDesigner->getHtmlForSchemaExport(
                    $db,
                    (int) $request->getParsedBodyParam('selected_page'),
                );
            } elseif ($dialog === 'add_table') {
                // Pass the db and table to the getTablesInfo so we only have the table we asked for
                $scriptDisplayField = $this->designerCommon->getTablesInfo($db, $table);
                $tableColumn = $this->designerCommon->getColumnsInfo($scriptDisplayField);
                $tablesAllKeys = $this->designerCommon->getAllKeys($scriptDisplayField);
                $columnsType = $this->databaseDesigner->getColumnTypes($tableColumn, $tablesAllKeys);

                $html = $this->template->render('database/designer/database_tables', [
                    'db' => Current::$database,
                    'text_dir' => LanguageManager::$textDir,
                    'has_query' => isset($_REQUEST['query']),
                    'tab_pos' => [],
                    'display_page' => -1,
                    'tab_column' => $tableColumn,
                    'tables_all_keys' => $tablesAllKeys,
                    'tables' => $scriptDisplayField,
                    'columns_type' => $columnsType,
                ]);
            }

            if ($html !== '') {
                $this->response->addHTML($html);
            }

            return;
        }

        if ($request->hasBodyParam('operation')) {
            $operation = $request->getParsedBodyParam('operation');
            if ($operation === 'deletePage') {
                $success = $this->designerCommon->deletePage($request->getParsedBodyParam('selected_page'));
                $this->response->setRequestStatus($success);
            } elseif ($operation === 'savePage') {
                if ($request->getParsedBodyParam('save_page') === 'same') {
                    $page = $request->getParsedBodyParam('selected_page');
                } elseif ($this->designerCommon->getPageExists($request->getParsedBodyParam('selected_value'))) {
                    $this->response->addJSON(
                        'message',
                        sprintf(
                            /* l10n: The user tries to save a page with an existing name in Designer */
                            __('There already exists a page named "%s" please rename it to something else.'),
                            htmlspecialchars($request->getParsedBodyParam('selected_value')),
                        ),
                    );
                    $this->response->setRequestStatus(false);

                    return;
                } else {
                    $page = $this->designerCommon->createNewPage($request->getParsedBodyParam('selected_value'), $db);
                    $this->response->addJSON('id', $page);
                }

                $success = $this->designerCommon->saveTablePositions($page);
                $this->response->setRequestStatus($success);
            } elseif ($operation === 'setDisplayField') {
                [
                    $success,
                    $GLOBALS['message'],
                ] = $this->designerCommon->saveDisplayField($db, $table, $request->getParsedBodyParam('field'));
                $this->response->setRequestStatus($success);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($operation === 'addNewRelation') {
                [$success, $GLOBALS['message']] = $this->designerCommon->addNewRelation(
                    $request->getParsedBodyParam('T1'),
                    $request->getParsedBodyParam('F1'),
                    $request->getParsedBodyParam('T2'),
                    $request->getParsedBodyParam('F2'),
                    $request->getParsedBodyParam('on_delete'),
                    $request->getParsedBodyParam('on_update'),
                    $request->getParsedBodyParam('DB1'),
                    $request->getParsedBodyParam('DB2'),
                );
                $this->response->setRequestStatus($success);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($operation === 'removeRelation') {
                [$success, $GLOBALS['message']] = $this->designerCommon->removeRelation(
                    $request->getParsedBodyParam('T1'),
                    $request->getParsedBodyParam('F1'),
                    $request->getParsedBodyParam('T2'),
                    $request->getParsedBodyParam('F2'),
                );
                $this->response->setRequestStatus($success);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($operation === 'save_setting_value') {
                $success = $this->designerCommon->saveSetting(
                    $request->getParsedBodyParam('index'),
                    $request->getParsedBodyParam('value'),
                );
                $this->response->setRequestStatus($success);
            }

            return;
        }

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

        $scriptDisplayField = $this->designerCommon->getTablesInfo();

        $visualBuilderMode = $request->hasQueryParam('query');

        if ($visualBuilderMode) {
            $displayPage = $this->designerCommon->getDefaultPage($request->getQueryParam('db'));
        } elseif ($request->hasQueryParam('page')) {
            $displayPage = (int) $request->getQueryParam('page');
        } else {
            $displayPage = $this->designerCommon->getLoadingPage($request->getQueryParam('db'));
        }

        $selectedPage = null;
        if ($displayPage != -1) {
            $selectedPage = $this->designerCommon->getPageName($displayPage);
        }

        $tablePositions = $this->designerCommon->getTablePositions($displayPage);

        $fullTableNames = [];
        foreach ($scriptDisplayField as $designerTable) {
            $fullTableNames[] = $designerTable->getDbTableString();
        }

        foreach ($tablePositions as $position) {
            if (in_array($position['dbName'] . '.' . $position['tableName'], $fullTableNames, true)) {
                continue;
            }

            $designerTables = $this->designerCommon->getTablesInfo($position['dbName'], $position['tableName']);
            foreach ($designerTables as $designerTable) {
                $scriptDisplayField[] = $designerTable;
            }
        }

        $tableColumn = $this->designerCommon->getColumnsInfo($scriptDisplayField);
        $scriptTables = $this->designerCommon->getScriptTabs($scriptDisplayField);
        $tablesAllKeys = $this->designerCommon->getAllKeys($scriptDisplayField);
        $classesSideMenu = $this->databaseDesigner->returnClassNamesFromMenuButtons();
        $scriptContr = $this->designerCommon->getScriptContr($scriptDisplayField);

        $this->response->setMinimalFooter();
        $header = $this->response->getHeader();
        $header->setBodyId('designer_body');

        $this->addScriptFiles(['designer/init.js']);

        $columnsType = $this->databaseDesigner->getColumnTypes($tableColumn, $tablesAllKeys);

        $designerConfig = $this->databaseDesigner->getDesignerConfig(
            Current::$database,
            $scriptDisplayField,
            $scriptTables,
            $scriptContr,
            $displayPage,
        );

        $mainHtml = $this->template->render('database/designer/main', [
            'db' => Current::$database,
            'text_dir' => LanguageManager::$textDir,
            'hidden_input_fields' => Url::getHiddenInputs($request->getQueryParam('db')),
            'designer_config' => json_encode($designerConfig),
            'display_page' => $displayPage,
            'has_query' => $visualBuilderMode,
            'visual_builder' => $visualBuilderMode,
            'selected_page' => $selectedPage,
            'params_array' => $classesSideMenu,
            'tab_pos' => $tablePositions,
            'tab_column' => $tableColumn,
            'tables_all_keys' => $tablesAllKeys,
            'designerTables' => $scriptDisplayField,
            'columns_type' => $columnsType,
        ]);

        // Embed some data into HTML, later it will be read
        // by designer/init.js and converted to JS variables.
        $this->response->addHTML($mainHtml);

        $this->response->addHTML('<div id="PMA_disable_floating_menubar"></div>');
    }
}
