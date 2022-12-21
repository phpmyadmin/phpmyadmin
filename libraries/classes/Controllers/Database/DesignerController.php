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
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;
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
                $scriptDisplayField = $this->designerCommon->getTablesInfo($_POST['db'], $_POST['table']);
                $tableColumn = $this->designerCommon->getColumnsInfo($scriptDisplayField);
                $tablesAllKeys = $this->designerCommon->getAllKeys($scriptDisplayField);
                $tablesPkOrUniqueKeys = $this->designerCommon->getPkOrUniqueKeys($scriptDisplayField);

                $html = $this->databaseDesigner->getDatabaseTables(
                    $_POST['db'],
                    $scriptDisplayField,
                    [],
                    -1,
                    $tableColumn,
                    $tablesAllKeys,
                    $tablesPkOrUniqueKeys
                );
            }

            if (! empty($html)) {
                $this->response->addHTML($html);
            }

            return;
        }

        if (isset($_POST['operation'])) {
            if ($_POST['operation'] === 'deletePage') {
                $success = $this->designerCommon->deletePage($_POST['selected_page']);
                $this->response->setRequestStatus($success);
            } elseif ($_POST['operation'] === 'savePage') {
                if ($_POST['save_page'] === 'same') {
                    $page = $_POST['selected_page'];
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
                    $page = $this->designerCommon->createNewPage($_POST['selected_value'], $_POST['db']);
                    $this->response->addJSON('id', $page);
                }

                $success = $this->designerCommon->saveTablePositions($page);
                $this->response->setRequestStatus($success);
            } elseif ($_POST['operation'] === 'setDisplayField') {
                [
                    $success,
                    $GLOBALS['message'],
                ] = $this->designerCommon->saveDisplayField($_POST['db'], $_POST['table'], $_POST['field']);
                $this->response->setRequestStatus($success);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($_POST['operation'] === 'addNewRelation') {
                [$success, $GLOBALS['message']] = $this->designerCommon->addNewRelation(
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
                $this->response->setRequestStatus($success);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($_POST['operation'] === 'removeRelation') {
                [$success, $GLOBALS['message']] = $this->designerCommon->removeRelation(
                    $_POST['T1'],
                    $_POST['F1'],
                    $_POST['T2'],
                    $_POST['F2']
                );
                $this->response->setRequestStatus($success);
                $this->response->addJSON('message', $GLOBALS['message']);
            } elseif ($_POST['operation'] === 'save_setting_value') {
                $success = $this->designerCommon->saveSetting($_POST['index'], $_POST['value']);
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

        $visualBuilderMode = isset($_GET['query']);

        if ($visualBuilderMode) {
            $displayPage = $this->designerCommon->getDefaultPage($_GET['db']);
        } elseif (! empty($_GET['page'])) {
            $displayPage = (int) $_GET['page'];
        } else {
            $displayPage = $this->designerCommon->getLoadingPage($_GET['db']);
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

        $this->addScriptFiles([
            'designer/database.js',
            'designer/objects.js',
            'designer/page.js',
            'designer/history.js',
            'designer/move.js',
            'designer/init.js',
        ]);

        // Embed some data into HTML, later it will be read
        // by designer/init.js and converted to JS variables.
        $this->response->addHTML(
            $this->databaseDesigner->getHtmlForMain(
                $GLOBALS['db'],
                $_GET['db'],
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
                $tablesPkOrUniqueKeys
            )
        );

        $this->response->addHTML('<div id="PMA_disable_floating_menubar"></div>');
    }
}
