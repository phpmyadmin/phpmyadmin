<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin designer general code
 *
 * @package PhpMyAdmin-Designer
 */
use PhpMyAdmin\Database\Designer;
use PhpMyAdmin\PmdCommon;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$response = Response::getInstance();

if (isset($_REQUEST['dialog'])) {

    if ($_REQUEST['dialog'] == 'edit') {
        $html = Designer::getHtmlForEditOrDeletePages($GLOBALS['db'], 'editPage');
    } elseif ($_REQUEST['dialog'] == 'delete') {
        $html = Designer::getHtmlForEditOrDeletePages($GLOBALS['db'], 'deletePage');
    } elseif ($_REQUEST['dialog'] == 'save_as') {
        $html = Designer::getHtmlForPageSaveAs($GLOBALS['db']);
    } elseif ($_REQUEST['dialog'] == 'export') {
        $html = Designer::getHtmlForSchemaExport(
            $GLOBALS['db'], $_REQUEST['selected_page']
        );
    } elseif ($_REQUEST['dialog'] == 'add_table') {
        $script_display_field = PmdCommon::getTablesInfo();
        $required = $GLOBALS['db'] . '.' . $GLOBALS['table'];
        $tab_column = PmdCommon::getColumnsInfo();
        $tables_all_keys = PmdCommon::getAllKeys();
        $tables_pk_or_unique_keys = PmdCommon::getPkOrUniqueKeys();

        $req_key = array_search($required, $GLOBALS['PMD']['TABLE_NAME']);

        $GLOBALS['PMD']['TABLE_NAME'] = array($GLOBALS['PMD']['TABLE_NAME'][$req_key]);
        $GLOBALS['PMD_URL']['TABLE_NAME_SMALL'] = array($GLOBALS['PMD_URL']['TABLE_NAME_SMALL'][$req_key]);
        $GLOBALS['PMD']['TABLE_NAME_SMALL'] = array($GLOBALS['PMD']['TABLE_NAME_SMALL'][$req_key]);
        $GLOBALS['PMD_OUT']['TABLE_NAME_SMALL'] = array($GLOBALS['PMD_OUT']['TABLE_NAME_SMALL'][$req_key]);
        $GLOBALS['PMD']['TABLE_TYPE'] = array($GLOBALS['PMD_URL']['TABLE_TYPE'][$req_key]);
        $GLOBALS['PMD_OUT']['OWNER'] = array($GLOBALS['PMD_OUT']['OWNER'][$req_key]);

        $html = Designer::getDatabaseTables(
            array(), -1, $tab_column,
            $tables_all_keys, $tables_pk_or_unique_keys
        );
    }

    if (! empty($html)) {
        $response->addHTML($html);
    }
    return;
}

if (isset($_REQUEST['operation'])) {

    if ($_REQUEST['operation'] == 'deletePage') {
        $success = PmdCommon::deletePage($_REQUEST['selected_page']);
        $response->setRequestStatus($success);
    } elseif ($_REQUEST['operation'] == 'savePage') {
        if ($_REQUEST['save_page'] == 'same') {
            $page = $_REQUEST['selected_page'];
        } else { // new
            $page = PmdCommon::createNewPage($_REQUEST['selected_value'], $GLOBALS['db']);
            $response->addJSON('id', $page);
        }
        $success = PmdCommon::saveTablePositions($page);
        $response->setRequestStatus($success);
    } elseif ($_REQUEST['operation'] == 'setDisplayField') {
        PmdCommon::saveDisplayField(
            $_REQUEST['db'], $_REQUEST['table'], $_REQUEST['field']
        );
        $response->setRequestStatus(true);
    } elseif ($_REQUEST['operation'] == 'addNewRelation') {
        list($success, $message) = PmdCommon::addNewRelation(
            $_REQUEST['db'],
            $_REQUEST['T1'],
            $_REQUEST['F1'],
            $_REQUEST['T2'],
            $_REQUEST['F2'],
            $_REQUEST['on_delete'],
            $_REQUEST['on_update'],
            $_REQUEST['DB1'],
            $_REQUEST['DB2']
        );
        $response->setRequestStatus($success);
        $response->addJSON('message', $message);
    } elseif ($_REQUEST['operation'] == 'removeRelation') {
        list($success, $message) = PmdCommon::removeRelation(
            $_REQUEST['T1'],
            $_REQUEST['F1'],
            $_REQUEST['T2'],
            $_REQUEST['F2']
        );
        $response->setRequestStatus($success);
        $response->addJSON('message', $message);
    } elseif ($_REQUEST['operation'] == 'save_setting_value') {
        $success = PmdCommon::saveDesignerSetting($_REQUEST['index'], $_REQUEST['value']);
        $response->setRequestStatus($success);
    }

    return;
}

require 'libraries/db_common.inc.php';

$script_display_field = PmdCommon::getTablesInfo();
$tab_column = PmdCommon::getColumnsInfo();
$script_tables = PmdCommon::getScriptTabs();
$tables_pk_or_unique_keys = PmdCommon::getPkOrUniqueKeys();
$tables_all_keys = PmdCommon::getAllKeys();
$classes_side_menu = Designer::returnClassNamesFromMenuButtons();

$display_page = -1;
$selected_page = null;

if (isset($_REQUEST['query'])) {
    $display_page = PmdCommon::getDefaultPage($_REQUEST['db']);
} else {
    if (! empty($_REQUEST['page'])) {
        $display_page = $_REQUEST['page'];
    } else {
        $display_page = PmdCommon::getLoadingPage($_REQUEST['db']);
    }
}
if ($display_page != -1) {
    $selected_page = PmdCommon::getPageName($display_page);
}
$tab_pos = PmdCommon::getTablePositions($display_page);
$script_contr = PmdCommon::getScriptContr();

$params = array('lang' => $GLOBALS['lang']);
if (isset($_GET['db'])) {
    $params['db'] = $_GET['db'];
}

$response = Response::getInstance();
$response->getFooter()->setMinimal();
$header   = $response->getHeader();
$header->setBodyId('pmd_body');

$scripts  = $header->getScripts();
$scripts->addFile('vendor/jquery/jquery.fullscreen.js');
$scripts->addFile('pmd/designer_db.js');
$scripts->addFile('pmd/designer_objects.js');
$scripts->addFile('pmd/designer_page.js');
$scripts->addFile('pmd/history.js');
$scripts->addFile('pmd/move.js');
$scripts->addFile('pmd/init.js');

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
// by pmd/init.js and converted to JS variables.
$response->addHTML(
    Designer::getHtmlForJsFields(
        $script_tables, $script_contr, $script_display_field, $display_page
    )
);
$response->addHTML(
    Designer::getPageMenu(
        isset($_REQUEST['query']),
        $selected_page,
        $classes_side_menu
    )
);



$response->addHTML('<div id="canvas_outer">');
$response->addHTML(
    '<form action="" id="container-form" method="post" name="form1">'
);

$response->addHTML(Designer::getHtmlCanvas());
$response->addHTML(Designer::getHtmlTableList($tab_pos, $display_page));

$response->addHTML(
    Designer::getDatabaseTables(
        $tab_pos, $display_page, $tab_column,
        $tables_all_keys, $tables_pk_or_unique_keys
    )
);
$response->addHTML('</form>');
$response->addHTML('</div>'); // end canvas_outer

$response->addHTML('<div id="pmd_hint"></div>');

$response->addHTML(Designer::getNewRelationPanel());
$response->addHTML(Designer::getDeleteRelationPanel());

if (isset($_REQUEST['query'])) {
    $response->addHTML(Designer::getOptionsPanel());
    $response->addHTML(Designer::getRenameToPanel());
    $response->addHTML(Designer::getHavingQueryPanel());
    $response->addHTML(Designer::getAggregateQueryPanel());
    $response->addHTML(Designer::getWhereQueryPanel());
    $response->addHTML(Designer::getQueryDetails($_GET['db']));
}

$response->addHTML('<div id="PMA_disable_floating_menubar"></div>');
