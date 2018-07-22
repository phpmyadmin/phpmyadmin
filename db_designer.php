<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin designer general code
 *
 * @package PhpMyAdmin-Designer
 */
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\Database\Designer\Common;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$response = Response::getInstance();

$databaseDesigner = new Designer();
$designerCommon = new Common();

if (isset($_REQUEST['dialog'])) {

    if ($_GET['dialog'] == 'edit') {
        $html = $databaseDesigner->getHtmlForEditOrDeletePages($GLOBALS['db'], 'editPage');
    } elseif ($_GET['dialog'] == 'delete') {
        $html = $databaseDesigner->getHtmlForEditOrDeletePages($GLOBALS['db'], 'deletePage');
    } elseif ($_GET['dialog'] == 'save_as') {
        $html = $databaseDesigner->getHtmlForPageSaveAs($GLOBALS['db']);
    } elseif ($_GET['dialog'] == 'export') {
        $html = $databaseDesigner->getHtmlForSchemaExport(
            $GLOBALS['db'], $_GET['selected_page']
        );
    } elseif ($_POST['dialog'] == 'add_table') {
        $script_display_field = $designerCommon->getTablesInfo();
        $required = $GLOBALS['db'] . '.' . $GLOBALS['table'];
        $tab_column = $designerCommon->getColumnsInfo();
        $tables_all_keys = $designerCommon->getAllKeys();
        $tables_pk_or_unique_keys = $designerCommon->getPkOrUniqueKeys();

        $req_key = array_search($required, $GLOBALS['designer']['TABLE_NAME']);

        $GLOBALS['designer']['TABLE_NAME'] = array($GLOBALS['designer']['TABLE_NAME'][$req_key]);
        $GLOBALS['designer_url']['TABLE_NAME_SMALL'] = array($GLOBALS['designer_url']['TABLE_NAME_SMALL'][$req_key]);
        $GLOBALS['designer']['TABLE_NAME_SMALL'] = array($GLOBALS['designer']['TABLE_NAME_SMALL'][$req_key]);
        $GLOBALS['designer_out']['TABLE_NAME_SMALL'] = array($GLOBALS['designer_out']['TABLE_NAME_SMALL'][$req_key]);
        $GLOBALS['designer']['TABLE_TYPE'] = array($GLOBALS['designer_url']['TABLE_TYPE'][$req_key]);
        $GLOBALS['designer_out']['OWNER'] = array($GLOBALS['designer_out']['OWNER'][$req_key]);

        $html = $databaseDesigner->getDatabaseTables(
            array(), -1, $tab_column,
            $tables_all_keys, $tables_pk_or_unique_keys
        );
    }

    if (! empty($html)) {
        $response->addHTML($html);
    }
    return;
}

if (isset($_POST['operation'])) {

    if ($_POST['operation'] == 'deletePage') {
        $success = $designerCommon->deletePage($_POST['selected_page']);
        $response->setRequestStatus($success);
    } elseif ($_POST['operation'] == 'savePage') {
        if ($_POST['save_page'] == 'same') {
            $page = $_POST['selected_page'];
        } else { // new
            $page = $designerCommon->createNewPage($_POST['selected_value'], $GLOBALS['db']);
            $response->addJSON('id', $page);
        }
        $success = $designerCommon->saveTablePositions($page);
        $response->setRequestStatus($success);
    } elseif ($_POST['operation'] == 'setDisplayField') {
        $designerCommon->saveDisplayField(
            $_POST['db'], $_POST['table'], $_POST['field']
        );
        $response->setRequestStatus(true);
    } elseif ($_POST['operation'] == 'addNewRelation') {
        list($success, $message) = $designerCommon->addNewRelation(
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
        $response->setRequestStatus($success);
        $response->addJSON('message', $message);
    } elseif ($_POST['operation'] == 'removeRelation') {
        list($success, $message) = $designerCommon->removeRelation(
            $_POST['T1'],
            $_POST['F1'],
            $_POST['T2'],
            $_POST['F2']
        );
        $response->setRequestStatus($success);
        $response->addJSON('message', $message);
    } elseif ($_POST['operation'] == 'save_setting_value') {
        $success = $designerCommon->saveSetting($_POST['index'], $_POST['value']);
        $response->setRequestStatus($success);
    }

    return;
}

require 'libraries/db_common.inc.php';

$script_display_field = $designerCommon->getTablesInfo();
$tab_column = $designerCommon->getColumnsInfo();
$script_tables = $designerCommon->getScriptTabs();
$tables_pk_or_unique_keys = $designerCommon->getPkOrUniqueKeys();
$tables_all_keys = $designerCommon->getAllKeys();
$classes_side_menu = $databaseDesigner->returnClassNamesFromMenuButtons();

$display_page = -1;
$selected_page = null;

if (isset($_GET['query'])) {
    $display_page = $designerCommon->getDefaultPage($_GET['db']);
} else {
    if (! empty($_GET['page'])) {
        $display_page = $_GET['page'];
    } else {
        $display_page = $designerCommon->getLoadingPage($_GET['db']);
    }
}
if ($display_page != -1) {
    $selected_page = $designerCommon->getPageName($display_page);
}
$tab_pos = $designerCommon->getTablePositions($display_page);
$script_contr = $designerCommon->getScriptContr();

$params = array('lang' => $GLOBALS['lang']);
if (isset($_GET['db'])) {
    $params['db'] = $_GET['db'];
}

$response = Response::getInstance();
$response->getFooter()->setMinimal();
$header   = $response->getHeader();
$header->setBodyId('designer_body');

$scripts  = $header->getScripts();
$scripts->addFile('vendor/jquery/jquery.fullscreen.js');
$scripts->addFile('designer/database.js');
$scripts->addFile('designer/objects.js');
$scripts->addFile('designer/page.js');
$scripts->addFile('designer/history.js');
$scripts->addFile('designer/move.js');
$scripts->addFile('designer/init.js');

list(
    $tables,
    $num_tables,
    $total_num_tables,
    $sub_part,
    $is_show_stats,
    $db_is_system_schema,
    $tooltip_truename,
    $tooltip_aliasname,
    $pos
) = PhpMyAdmin\Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');

// Embed some data into HTML, later it will be read
// by designer/init.js and converted to JS variables.
$response->addHTML(
    $databaseDesigner->getHtmlForJsFields(
        $script_tables, $script_contr, $script_display_field, $display_page
    )
);
$response->addHTML(
    $databaseDesigner->getPageMenu(
        isset($_GET['query']),
        $selected_page,
        $classes_side_menu
    )
);



$response->addHTML('<div id="canvas_outer">');
$response->addHTML(
    '<form action="" id="container-form" method="post" name="form1">'
);

$response->addHTML($databaseDesigner->getHtmlCanvas());
$response->addHTML($databaseDesigner->getHtmlTableList($tab_pos, $display_page));

$response->addHTML(
    $databaseDesigner->getDatabaseTables(
        $tab_pos, $display_page, $tab_column,
        $tables_all_keys, $tables_pk_or_unique_keys
    )
);
$response->addHTML('</form>');
$response->addHTML('</div>'); // end canvas_outer

$response->addHTML('<div id="designer_hint"></div>');

$response->addHTML($databaseDesigner->getNewRelationPanel());
$response->addHTML($databaseDesigner->getDeleteRelationPanel());

if (isset($_GET['query'])) {
    $response->addHTML($databaseDesigner->getOptionsPanel());
    $response->addHTML($databaseDesigner->getRenameToPanel());
    $response->addHTML($databaseDesigner->getHavingQueryPanel());
    $response->addHTML($databaseDesigner->getAggregateQueryPanel());
    $response->addHTML($databaseDesigner->getWhereQueryPanel());
    $response->addHTML($databaseDesigner->getQueryDetails($_GET['db']));
}

$response->addHTML('<div id="PMA_disable_floating_menubar"></div>');
