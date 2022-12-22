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
    /** @var Designer */
    private $databaseDesigner;

    /** @var DesignerCommon */
    private $designerCommon;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Designer $databaseDesigner,
        DesignerCommon $designerCommon
    ) {
        parent::__construct($response, $template);
        $this->databaseDesigner = $databaseDesigner;
        $this->designerCommon = $designerCommon;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['script_display_field'] = $GLOBALS['script_display_field'] ?? null;
        $GLOBALS['tab_column'] = $GLOBALS['tab_column'] ?? null;
        $GLOBALS['tables_all_keys'] = $GLOBALS['tables_all_keys'] ?? null;
        $GLOBALS['tables_pk_or_unique_keys'] = $GLOBALS['tables_pk_or_unique_keys'] ?? null;
        $GLOBALS['success'] = $GLOBALS['success'] ?? null;
        $GLOBALS['page'] = $GLOBALS['page'] ?? null;
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;
        $GLOBALS['selected_page'] = $GLOBALS['selected_page'] ?? null;
        $GLOBALS['tab_pos'] = $GLOBALS['tab_pos'] ?? null;
        $GLOBALS['fullTableNames'] = $GLOBALS['fullTableNames'] ?? null;
        $GLOBALS['script_tables'] = $GLOBALS['script_tables'] ?? null;
        $GLOBALS['script_contr'] = $GLOBALS['script_contr'] ?? null;
        $GLOBALS['params'] = $GLOBALS['params'] ?? null;
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;
        $GLOBALS['num_tables'] = $GLOBALS['num_tables'] ?? null;
        $GLOBALS['total_num_tables'] = $GLOBALS['total_num_tables'] ?? null;
        $GLOBALS['tooltip_truename'] = $GLOBALS['tooltip_truename'] ?? null;
        $GLOBALS['tooltip_aliasname'] = $GLOBALS['tooltip_aliasname'] ?? null;
        $GLOBALS['pos'] = $GLOBALS['pos'] ?? null;
        $GLOBALS['classes_side_menu'] = $GLOBALS['classes_side_menu'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

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
                $html = $this->databaseDesigner->getHtmlForSchemaExport($db, $request->getParsedBodyParam('selected_page'));
            } elseif ($dialog === 'add_table') {
                // Pass the db and table to the getTablesInfo so we only have the table we asked for
                $GLOBALS['script_display_field'] = $this->designerCommon->getTablesInfo($db, $table);
                $GLOBALS['tab_column'] = $this->designerCommon->getColumnsInfo($GLOBALS['script_display_field']);
                $GLOBALS['tables_all_keys'] = $this->designerCommon->getAllKeys($GLOBALS['script_display_field']);
                $GLOBALS['tables_pk_or_unique_keys'] = $this->designerCommon->getPkOrUniqueKeys(
                    $GLOBALS['script_display_field']
                );

                $html = $this->databaseDesigner->getDatabaseTables(
                    $db,
                    $GLOBALS['script_display_field'],
                    [],
                    -1,
                    $GLOBALS['tab_column'],
                    $GLOBALS['tables_all_keys'],
                    $GLOBALS['tables_pk_or_unique_keys']
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
                $GLOBALS['success'] = $this->designerCommon->deletePage($request->getParsedBodyParam('selected_page'));
                $this->response->setRequestStatus($GLOBALS['success']);
            } elseif ($operation === 'savePage') {
                if ($request->getParsedBodyParam('save_page') === 'same') {
                    $GLOBALS['page'] = $request->getParsedBodyParam('selected_page');
                } elseif ($this->designerCommon->getPageExists($request->getParsedBodyParam('selected_value'))) {
                    $this->response->addJSON(
                        'message',
                        sprintf(
                            /* l10n: The user tries to save a page with an existing name in Designer */
                            __('There already exists a page named "%s" please rename it to something else.'),
                            htmlspecialchars($request->getParsedBodyParam('selected_value'))
                        )
                    );
                    $this->response->setRequestStatus(false);

                    return;
                } else {
                    $GLOBALS['page'] = $this->designerCommon->createNewPage($request->getParsedBodyParam('selected_value'), $db);
                    $this->response->addJSON('id', $GLOBALS['page']);
                }

                $GLOBALS['success'] = $this->designerCommon->saveTablePositions($GLOBALS['page']);
                $this->response->setRequestStatus($GLOBALS['success']);
            } elseif ($operation === 'setDisplayField') {
                [
                    $GLOBALS['success'],
                    $GLOBALS['message'],
                ] = $this->designerCommon->saveDisplayField($db, $table, $request->getParsedBodyParam('field'));
                $this->response->setRequestStatus($GLOBALS['success']);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($operation === 'addNewRelation') {
                [$GLOBALS['success'], $GLOBALS['message']] = $this->designerCommon->addNewRelation(
                    $db,
                    $request->getParsedBodyParam('T1'),
                    $request->getParsedBodyParam('F1'),
                    $request->getParsedBodyParam('T2'),
                    $request->getParsedBodyParam('F2'),
                    $request->getParsedBodyParam('on_delete'),
                    $request->getParsedBodyParam('on_update'),
                    $request->getParsedBodyParam('DB1'),
                    $request->getParsedBodyParam('DB2')
                );
                $this->response->setRequestStatus($GLOBALS['success']);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($operation === 'removeRelation') {
                [$GLOBALS['success'], $GLOBALS['message']] = $this->designerCommon->removeRelation(
                    $request->getParsedBodyParam('T1'),
                    $request->getParsedBodyParam('F1'),
                    $request->getParsedBodyParam('T2'),
                    $request->getParsedBodyParam('F2')
                );
                $this->response->setRequestStatus($GLOBALS['success']);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($operation === 'save_setting_value') {
                $GLOBALS['success'] = $this->designerCommon->saveSetting(
                    $request->getParsedBodyParam('index'),
                    $request->getParsedBodyParam('value')
                );
                $this->response->setRequestStatus($GLOBALS['success']);
            }

            return;
        }

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $GLOBALS['script_display_field'] = $this->designerCommon->getTablesInfo();

        $GLOBALS['selected_page'] = null;

        $visualBuilderMode = $request->hasQueryParam('query');

        if ($visualBuilderMode) {
            $displayPage = $this->designerCommon->getDefaultPage($request->getQueryParam('db'));
        } elseif ($request->hasQueryParam('page')) {
            $displayPage = (int) $request->getQueryParam('page');
        } else {
            $displayPage = $this->designerCommon->getLoadingPage($request->getQueryParam('db'));
        }

        if ($displayPage != -1) {
            $GLOBALS['selected_page'] = $this->designerCommon->getPageName($displayPage);
        }

        $GLOBALS['tab_pos'] = $this->designerCommon->getTablePositions($displayPage);

        $GLOBALS['fullTableNames'] = [];

        foreach ($GLOBALS['script_display_field'] as $designerTable) {
            $GLOBALS['fullTableNames'][] = $designerTable->getDbTableString();
        }

        foreach ($GLOBALS['tab_pos'] as $position) {
            if (in_array($position['dbName'] . '.' . $position['tableName'], $GLOBALS['fullTableNames'])) {
                continue;
            }

            $designerTables = $this->designerCommon->getTablesInfo($position['dbName'], $position['tableName']);
            foreach ($designerTables as $designerTable) {
                $GLOBALS['script_display_field'][] = $designerTable;
            }
        }

        $GLOBALS['tab_column'] = $this->designerCommon->getColumnsInfo($GLOBALS['script_display_field']);
        $GLOBALS['script_tables'] = $this->designerCommon->getScriptTabs($GLOBALS['script_display_field']);
        $GLOBALS['tables_pk_or_unique_keys'] = $this->designerCommon->getPkOrUniqueKeys(
            $GLOBALS['script_display_field']
        );
        $GLOBALS['tables_all_keys'] = $this->designerCommon->getAllKeys($GLOBALS['script_display_field']);
        $GLOBALS['classes_side_menu'] = $this->databaseDesigner->returnClassNamesFromMenuButtons();

        $GLOBALS['script_contr'] = $this->designerCommon->getScriptContr($GLOBALS['script_display_field']);

        $GLOBALS['params'] = ['lang' => $GLOBALS['lang']];
        if ($request->hasQueryParam('db')) {
            $GLOBALS['params']['db'] = $request->getQueryParam('db');
        }

        $this->response->setMinimalFooter();
        $header = $this->response->getHeader();
        $header->setBodyId('designer_body');

        $this->addScriptFiles([
            'designer/database.js',
            'designer/objects.js',
            'designer/page.js',
            'designer/history.js',
            'designer/move.js',
            'designer/init.js',
        ]);

        [
            $GLOBALS['tables'],
            $GLOBALS['num_tables'],
            $GLOBALS['total_num_tables'],,,
            $GLOBALS['tooltip_truename'],
            $GLOBALS['tooltip_aliasname'],
            $GLOBALS['pos'],
        ] = Util::getDbInfo($request, $GLOBALS['db']);

        // Embed some data into HTML, later it will be read
        // by designer/init.js and converted to JS variables.
        $this->response->addHTML(
            $this->databaseDesigner->getHtmlForMain(
                $GLOBALS['db'],
                $request->getQueryParam('db'),
                $GLOBALS['script_display_field'],
                $GLOBALS['script_tables'],
                $GLOBALS['script_contr'],
                $GLOBALS['script_display_field'],
                $displayPage,
                $visualBuilderMode,
                $GLOBALS['selected_page'],
                $GLOBALS['classes_side_menu'],
                $GLOBALS['tab_pos'],
                $GLOBALS['tab_column'],
                $GLOBALS['tables_all_keys'],
                $GLOBALS['tables_pk_or_unique_keys']
            )
        );

        $this->response->addHTML('<div id="PMA_disable_floating_menubar"></div>');
    }
}
