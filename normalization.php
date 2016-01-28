<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Normalization process (temporarily specific to 1NF)
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/transformations.lib.php';
require_once 'libraries/normalization.lib.php';
require_once 'libraries/Index.class.php';

if (isset($_REQUEST['getColumns'])) {
    $html = '<option selected disabled>' . __('Select one…') . '</option>'
        . '<option value="no_such_col">' . __('No such column') . '</option>';
    //get column whose datatype falls under string category
    $html .= PMA_getHtmlForColumnsList(
        $db,
        $table,
        _pgettext('string types', 'String')
    );
    echo $html;
    exit;
}
if (isset($_REQUEST['splitColumn'])) {
    $num_fields = $_REQUEST['numFields'];
    $html = PMA_getHtmlForCreateNewColumn($num_fields, $db, $table);
    $html .= PMA_URL_getHiddenInputs($db, $table);
    echo $html;
    exit;
}
if (isset($_REQUEST['addNewPrimary'])) {
    $num_fields = 1;
    $columnMeta = array('Field'=>$table . "_id", 'Extra'=>'auto_increment');
    $html = PMA_getHtmlForCreateNewColumn(
        $num_fields, $db, $table, $columnMeta
    );
    $html .= PMA_URL_getHiddenInputs($db, $table);
    echo $html;
    exit;
}
if (isset($_REQUEST['findPdl'])) {
    $html = PMA_findPartialDependencies($table, $db);
    echo $html;
    exit;
}

if (isset($_REQUEST['getNewTables2NF'])) {
    $partialDependencies = json_decode($_REQUEST['pd']);
    $html = PMA_getHtmlForNewTables2NF($partialDependencies, $table);
    echo $html;
    exit;
}

if (isset($_REQUEST['getNewTables3NF'])) {
    $dependencies = json_decode($_REQUEST['pd']);
    $tables = json_decode($_REQUEST['tables']);
    $newTables = PMA_getHtmlForNewTables3NF($dependencies, $tables, $db);
    PMA_Response::getInstance()->disable();
    PMA_headerJSON();
    echo json_encode($newTables);
    exit;
}

$response = PMA_Response::getInstance();
$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('normalization.js');
$scripts->addFile('jquery/jquery.uitablefilter.js');
$normalForm = '1nf';
if (isset($_REQUEST['normalizeTo'])) {
    $normalForm = $_REQUEST['normalizeTo'];
}
if (isset($_REQUEST['createNewTables2NF'])) {
    $partialDependencies = json_decode($_REQUEST['pd']);
    $tablesName = json_decode($_REQUEST['newTablesName']);
    $res = PMA_createNewTablesFor2NF($partialDependencies, $tablesName, $table, $db);
    $response->addJSON($res);
    exit;
}
if (isset($_REQUEST['createNewTables3NF'])) {
    $newtables = json_decode($_REQUEST['newTables']);
    $res = PMA_createNewTablesFor3NF($newtables, $db);
    $response->addJSON($res);
    exit;
}
if (isset($_POST['repeatingColumns'])) {
    $repeatingColumns = $_POST['repeatingColumns'];
    $newTable = $_POST['newTable'];
    $newColumn = $_POST['newColumn'];
    $primary_columns = $_POST['primary_columns'];
    $res = PMA_moveRepeatingGroup(
        $repeatingColumns, $primary_columns, $newTable, $newColumn, $table, $db
    );
    $response->addJSON($res);
    exit;
}
if (isset($_REQUEST['step1'])) {
    $html = PMA_getHtmlFor1NFStep1($db, $table, $normalForm);
    $response->addHTML($html);
} else if (isset($_REQUEST['step2'])) {
    $res = PMA_getHtmlContentsFor1NFStep2($db, $table);
    $response->addJSON($res);
} else if (isset($_REQUEST['step3'])) {
    $res = PMA_getHtmlContentsFor1NFStep3($db, $table);
    $response->addJSON($res);
} else if (isset ($_REQUEST['step4'])) {
    $res = PMA_getHtmlContentsFor1NFStep4($db, $table);
    $response->addJSON($res);
} else if (isset($_REQUEST['step']) && $_REQUEST['step'] == 2.1) {
    $res = PMA_getHtmlFor2NFstep1($db, $table);
    $response->addJSON($res);
} else if (isset($_REQUEST['step']) && $_REQUEST['step'] == 3.1) {
    $tables = $_REQUEST['tables'];
    $res = PMA_getHtmlFor3NFstep1($db, $tables);
    $response->addJSON($res);
} else {
    $response->addHTML(PMA_getHtmlForNormalizetable());
}
