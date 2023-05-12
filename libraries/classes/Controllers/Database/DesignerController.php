<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Database\Designer\Common as DesignerCommon;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function in_array;
use function sprintf;

class DesignerController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Designer $databaseDesigner,
        private DesignerCommon $designerCommon,
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
                    $request->getParsedBodyParam('selected_page'),
                );
            } elseif ($dialog === 'add_table') {
                // Pass the db and table to the getTablesInfo so we only have the table we asked for
                $scriptDisplayField = $this->designerCommon->getTablesInfo($db, $table);
                $tableColumn = $this->designerCommon->getColumnsInfo($scriptDisplayField);
                $tablesAllKeys = $this->designerCommon->getAllKeys($scriptDisplayField);
                $tablesPkOrUniqueKeys = $this->designerCommon->getPkOrUniqueKeys($scriptDisplayField);

                $html = $this->databaseDesigner->getDatabaseTables(
                    $db,
                    $scriptDisplayField,
                    [],
                    -1,
                    $tableColumn,
                    $tablesAllKeys,
                    $tablesPkOrUniqueKeys,
                );
            }

            if (! empty($html)) {
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

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
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
            if (in_array($position['dbName'] . '.' . $position['tableName'], $fullTableNames)) {
                continue;
            }

            $designerTables = $this->designerCommon->getTablesInfo($position['dbName'], $position['tableName']);
            foreach ($designerTables as $designerTable) {
                $scriptDisplayField[] = $designerTable;
            }
        }

        $tableColumn = $this->designerCommon->getColumnsInfo($scriptDisplayField);
        $scriptTables = $this->designerCommon->getScriptTabs($scriptDisplayField);
        $tablesPkOrUniqueKeys = $this->designerCommon->getPkOrUniqueKeys($scriptDisplayField);
        $tablesAllKeys = $this->designerCommon->getAllKeys($scriptDisplayField);
        $classesSideMenu = $this->databaseDesigner->returnClassNamesFromMenuButtons();

        $scriptContr = $this->designerCommon->getScriptContr($scriptDisplayField);

        $this->response->setMinimalFooter();
        $header = $this->response->getHeader();
        $header->setBodyId('designer_body');

        $this->addScriptFiles(['designer/init.js']);

        // Embed some data into HTML, later it will be read
        // by designer/init.js and converted to JS variables.
        $this->response->addHTML(
            $this->databaseDesigner->getHtmlForMain(
                $GLOBALS['db'],
                $request->getQueryParam('db'),
                $scriptDisplayField,
                $scriptTables,
                $scriptContr,
                $scriptDisplayField,
                $displayPage,
                $visualBuilderMode,
                $selectedPage,
                $classesSideMenu,
                $tablePositions,
                $tableColumn,
                $tablesAllKeys,
                $tablesPkOrUniqueKeys,
            ),
        );

        $this->response->addHTML('<div id="PMA_disable_floating_menubar"></div>');
    }
}
