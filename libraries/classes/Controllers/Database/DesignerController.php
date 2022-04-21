<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Database\Designer\Common as DesignerCommon;
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

    public function __invoke(): void
    {
        $GLOBALS['script_display_field'] = $GLOBALS['script_display_field'] ?? null;
        $GLOBALS['tab_column'] = $GLOBALS['tab_column'] ?? null;
        $GLOBALS['tables_all_keys'] = $GLOBALS['tables_all_keys'] ?? null;
        $GLOBALS['tables_pk_or_unique_keys'] = $GLOBALS['tables_pk_or_unique_keys'] ?? null;
        $GLOBALS['success'] = $GLOBALS['success'] ?? null;
        $GLOBALS['page'] = $GLOBALS['page'] ?? null;
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;
        $GLOBALS['display_page'] = $GLOBALS['display_page'] ?? null;
        $GLOBALS['selected_page'] = $GLOBALS['selected_page'] ?? null;
        $GLOBALS['tab_pos'] = $GLOBALS['tab_pos'] ?? null;
        $GLOBALS['fullTableNames'] = $GLOBALS['fullTableNames'] ?? null;
        $GLOBALS['script_tables'] = $GLOBALS['script_tables'] ?? null;
        $GLOBALS['script_contr'] = $GLOBALS['script_contr'] ?? null;
        $GLOBALS['params'] = $GLOBALS['params'] ?? null;
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;
        $GLOBALS['num_tables'] = $GLOBALS['num_tables'] ?? null;
        $GLOBALS['total_num_tables'] = $GLOBALS['total_num_tables'] ?? null;
        $GLOBALS['sub_part'] = $GLOBALS['sub_part'] ?? null;
        $GLOBALS['tooltip_truename'] = $GLOBALS['tooltip_truename'] ?? null;
        $GLOBALS['tooltip_aliasname'] = $GLOBALS['tooltip_aliasname'] ?? null;
        $GLOBALS['pos'] = $GLOBALS['pos'] ?? null;
        $GLOBALS['classes_side_menu'] = $GLOBALS['classes_side_menu'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

        if (isset($_POST['dialog'])) {
            if ($_POST['dialog'] === 'edit') {
                $html = $this->databaseDesigner->getHtmlForEditOrDeletePages($_POST['db'], 'editPage');
            } elseif ($_POST['dialog'] === 'delete') {
                $html = $this->databaseDesigner->getHtmlForEditOrDeletePages($_POST['db'], 'deletePage');
            } elseif ($_POST['dialog'] === 'save_as') {
                $html = $this->databaseDesigner->getHtmlForPageSaveAs($_POST['db']);
            } elseif ($_POST['dialog'] === 'export') {
                $html = $this->databaseDesigner->getHtmlForSchemaExport($_POST['db'], $_POST['selected_page']);
            } elseif ($_POST['dialog'] === 'add_table') {
                // Pass the db and table to the getTablesInfo so we only have the table we asked for
                $GLOBALS['script_display_field'] = $this->designerCommon->getTablesInfo($_POST['db'], $_POST['table']);
                $GLOBALS['tab_column'] = $this->designerCommon->getColumnsInfo($GLOBALS['script_display_field']);
                $GLOBALS['tables_all_keys'] = $this->designerCommon->getAllKeys($GLOBALS['script_display_field']);
                $GLOBALS['tables_pk_or_unique_keys'] = $this->designerCommon->getPkOrUniqueKeys(
                    $GLOBALS['script_display_field']
                );

                $html = $this->databaseDesigner->getDatabaseTables(
                    $_POST['db'],
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

        if (isset($_POST['operation'])) {
            if ($_POST['operation'] === 'deletePage') {
                $GLOBALS['success'] = $this->designerCommon->deletePage($_POST['selected_page']);
                $this->response->setRequestStatus($GLOBALS['success']);
            } elseif ($_POST['operation'] === 'savePage') {
                if ($_POST['save_page'] === 'same') {
                    $GLOBALS['page'] = $_POST['selected_page'];
                } elseif ($this->designerCommon->getPageExists($_POST['selected_value'])) {
                    $this->response->addJSON(
                        'message',
                        sprintf(
                            /* l10n: The user tries to save a page with an existing name in Designer */
                            __('There already exists a page named "%s" please rename it to something else.'),
                            htmlspecialchars($_POST['selected_value'])
                        )
                    );
                    $this->response->setRequestStatus(false);

                    return;
                } else {
                    $GLOBALS['page'] = $this->designerCommon->createNewPage($_POST['selected_value'], $_POST['db']);
                    $this->response->addJSON('id', $GLOBALS['page']);
                }

                $GLOBALS['success'] = $this->designerCommon->saveTablePositions($GLOBALS['page']);
                $this->response->setRequestStatus($GLOBALS['success']);
            } elseif ($_POST['operation'] === 'setDisplayField') {
                [
                    $GLOBALS['success'],
                    $GLOBALS['message'],
                ] = $this->designerCommon->saveDisplayField($_POST['db'], $_POST['table'], $_POST['field']);
                $this->response->setRequestStatus($GLOBALS['success']);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($_POST['operation'] === 'addNewRelation') {
                [$GLOBALS['success'], $GLOBALS['message']] = $this->designerCommon->addNewRelation(
                    $_POST['db'],
                    $_POST['T1'],
                    $_POST['F1'],
                    $_POST['T2'],
                    $_POST['F2'],
                    $_POST['on_delete'],
                    $_POST['on_update'],
                    $_POST['DB1'],
                    $_POST['DB2']
                );
                $this->response->setRequestStatus($GLOBALS['success']);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($_POST['operation'] === 'removeRelation') {
                [$GLOBALS['success'], $GLOBALS['message']] = $this->designerCommon->removeRelation(
                    $_POST['T1'],
                    $_POST['F1'],
                    $_POST['T2'],
                    $_POST['F2']
                );
                $this->response->setRequestStatus($GLOBALS['success']);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($_POST['operation'] === 'save_setting_value') {
                $GLOBALS['success'] = $this->designerCommon->saveSetting($_POST['index'], $_POST['value']);
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

        $GLOBALS['display_page'] = -1;
        $GLOBALS['selected_page'] = null;

        $visualBuilderMode = isset($_GET['query']);

        if ($visualBuilderMode) {
            $GLOBALS['display_page'] = $this->designerCommon->getDefaultPage($_GET['db']);
        } elseif (! empty($_GET['page'])) {
            $GLOBALS['display_page'] = $_GET['page'];
        } else {
            $GLOBALS['display_page'] = $this->designerCommon->getLoadingPage($_GET['db']);
        }

        if ($GLOBALS['display_page'] != -1) {
            $GLOBALS['selected_page'] = $this->designerCommon->getPageName($GLOBALS['display_page']);
        }

        $GLOBALS['tab_pos'] = $this->designerCommon->getTablePositions($GLOBALS['display_page']);

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
        if (isset($_GET['db'])) {
            $GLOBALS['params']['db'] = $_GET['db'];
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
            $GLOBALS['total_num_tables'],
            $GLOBALS['sub_part'],,,
            $GLOBALS['tooltip_truename'],
            $GLOBALS['tooltip_aliasname'],
            $GLOBALS['pos'],
        ] = Util::getDbInfo($GLOBALS['db'], $GLOBALS['sub_part'] ?? '');

        // Embed some data into HTML, later it will be read
        // by designer/init.js and converted to JS variables.
        $this->response->addHTML(
            $this->databaseDesigner->getHtmlForMain(
                $GLOBALS['db'],
                $_GET['db'],
                $GLOBALS['script_display_field'],
                $GLOBALS['script_tables'],
                $GLOBALS['script_contr'],
                $GLOBALS['script_display_field'],
                $GLOBALS['display_page'],
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
