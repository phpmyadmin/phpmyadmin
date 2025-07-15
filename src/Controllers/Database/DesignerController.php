<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Database\Designer\Common as DesignerCommon;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function htmlspecialchars;
use function in_array;
use function json_encode;
use function sprintf;

#[Route('/database/designer', ['GET', 'POST'])]
final class DesignerController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Template $template,
        private readonly Designer $databaseDesigner,
        private readonly DesignerCommon $designerCommon,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $db = $request->getParsedBodyParamAsStringOrNull('db');
        $table = $request->getParsedBodyParamAsStringOrNull('table');

        if ($request->hasBodyParam('dialog')) {
            $html = '';
            $dialog = $request->getParsedBodyParamAsString('dialog');
            if ($dialog === 'edit') {
                $html = $this->databaseDesigner->getHtmlForEditOrDeletePages($db, 'editPage');
            } elseif ($dialog === 'delete') {
                $html = $this->databaseDesigner->getHtmlForEditOrDeletePages($db, 'deletePage');
            } elseif ($dialog === 'save_as') {
                $html = $this->databaseDesigner->getHtmlForPageSaveAs($db);
            } elseif ($dialog === 'export') {
                $html = $this->databaseDesigner->getHtmlForSchemaExport(
                    $db,
                    (int) $request->getParsedBodyParamAsStringOrNull('selected_page'),
                    $request->getParam('format'),
                    $request->getParam('export_type'),
                );
            } elseif ($dialog === 'add_table') {
                // Pass the db and table to the getTablesInfo so we only have the table we asked for
                $scriptDisplayField = $this->designerCommon->getTablesInfo($db, $table);
                $tableColumn = $this->designerCommon->getColumnsInfo($scriptDisplayField);
                $tablesAllKeys = $this->designerCommon->getAllKeys($scriptDisplayField);
                $columnsType = $this->databaseDesigner->getColumnTypes($tableColumn, $tablesAllKeys);

                $html = $this->template->render('database/designer/database_tables', [
                    'db' => Current::$database,
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

            return $this->response->response();
        }

        if ($request->hasBodyParam('operation')) {
            $operation = $request->getParsedBodyParamAsString('operation');
            if ($operation === 'deletePage') {
                $success = $this->designerCommon->deletePage(
                    (int) $request->getParsedBodyParamAsString('selected_page'),
                );
                $this->response->setRequestStatus($success);
            } elseif ($operation === 'savePage') {
                if ($request->getParsedBodyParamAsString('save_page') === 'same') {
                    $page = $request->getParsedBodyParamAsString('selected_page');
                } elseif (
                    $this->designerCommon->getPageExists(
                        $request->getParsedBodyParamAsString('selected_value'),
                    )
                ) {
                    $this->response->addJSON(
                        'message',
                        sprintf(
                            /* l10n: The user tries to save a page with an existing name in Designer */
                            __('There already exists a page named "%s" please rename it to something else.'),
                            htmlspecialchars($request->getParsedBodyParamAsString('selected_value')),
                        ),
                    );
                    $this->response->setRequestStatus(false);

                    return $this->response->response();
                } else {
                    $page = $this->designerCommon->createNewPage(
                        $request->getParsedBodyParamAsString('selected_value'),
                        $db,
                    );
                    $this->response->addJSON('id', $page);
                }

                $success = $this->designerCommon->saveTablePositions((int) $page);
                $this->response->setRequestStatus($success);
            } elseif ($operation === 'setDisplayField') {
                Current::$message = $this->designerCommon->saveDisplayField(
                    $db,
                    $table,
                    $request->getParsedBodyParamAsString('field'),
                );
                $this->response->setRequestStatus(Current::$message->isSuccess());
                $this->response->addJSON('message', Current::$message);
            } elseif ($operation === 'addNewRelation') {
                Current::$message = $this->designerCommon->addNewRelation(
                    $request->getParsedBodyParamAsString('T1'),
                    $request->getParsedBodyParamAsString('F1'),
                    $request->getParsedBodyParamAsString('T2'),
                    $request->getParsedBodyParamAsString('F2'),
                    $request->getParsedBodyParamAsString('on_delete'),
                    $request->getParsedBodyParamAsString('on_update'),
                    $request->getParsedBodyParamAsString('DB1'),
                    $request->getParsedBodyParamAsString('DB2'),
                );
                $this->response->setRequestStatus(Current::$message->isSuccess());
                $this->response->addJSON('message', Current::$message);
            } elseif ($operation === 'removeRelation') {
                Current::$message = $this->designerCommon->removeRelation(
                    $request->getParsedBodyParamAsString('T1'),
                    $request->getParsedBodyParamAsString('F1'),
                    $request->getParsedBodyParamAsString('T2'),
                    $request->getParsedBodyParamAsString('F2'),
                );
                $this->response->setRequestStatus(Current::$message->isSuccess());
                $this->response->addJSON('message', Current::$message);
            } elseif ($operation === 'save_setting_value') {
                $success = $this->designerCommon->saveSetting(
                    $request->getParsedBodyParamAsString('index'),
                    $request->getParsedBodyParamAsString('value'),
                );
                $this->response->setRequestStatus($success);
            }

            return $this->response->response();
        }

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
        if ($displayPage !== -1) {
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

        $this->response->addScriptFiles(['designer/init.js']);

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

        return $this->response->response();
    }
}
