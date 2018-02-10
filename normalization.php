<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Normalization process (temporarily specific to 1NF)
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Core;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;

require_once 'libraries/common.inc.php';

$normalization = new Normalization($GLOBALS['dbi']);

if (isset($_REQUEST['getColumns'])) {
    $html = '<option selected disabled>' . __('Select one…') . '</option>'
        . '<option value="no_such_col">' . __('No such column') . '</option>';
    //get column whose datatype falls under string category
    $html .= $normalization->getHtmlForColumnsList(
        $db,
        $table,
        _pgettext('string types', 'String')
    );
    echo $html;
    exit;
}
if (isset($_REQUEST['splitColumn'])) {
    $num_fields = min(4096, intval($_REQUEST['numFields']));
    $html = $normalization->getHtmlForCreateNewColumn($num_fields, $db, $table);
    $html .= Url::getHiddenInputs($db, $table);
    echo $html;
    exit;
}
if (isset($_REQUEST['addNewPrimary'])) {
    $num_fields = 1;
    $columnMeta = array('Field'=>$table . "_id", 'Extra'=>'auto_increment');
    $html = $normalization->getHtmlForCreateNewColumn(
        $num_fields, $db, $table, $columnMeta
    );
    $html .= Url::getHiddenInputs($db, $table);
    echo $html;
    exit;
}
if (isset($_REQUEST['findPdl'])) {
    $html = $normalization->findPartialDependencies($table, $db);
    echo $html;
    exit;
}

if (isset($_REQUEST['getNewTables2NF'])) {
    $partialDependencies = json_decode($_REQUEST['pd']);
    $html = $normalization->getHtmlForNewTables2NF($partialDependencies, $table);
    echo $html;
    exit;
}

$response = Response::getInstance();

if (isset($_REQUEST['getNewTables3NF'])) {
    $dependencies = json_decode($_REQUEST['pd']);
    $tables = json_decode($_REQUEST['tables']);
    $newTables = $normalization->getHtmlForNewTables3NF($dependencies, $tables, $db);
    $response->disable();
    Core::headerJSON();
    echo json_encode($newTables);
    exit;
}

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('normalization.js');
$scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
$normalForm = '1nf';
if (Core::isValid($_REQUEST['normalizeTo'], array('1nf', '2nf', '3nf'))) {
    $normalForm = $_REQUEST['normalizeTo'];
}
if (isset($_REQUEST['createNewTables2NF'])) {
    $partialDependencies = json_decode($_REQUEST['pd']);
    $tablesName = json_decode($_REQUEST['newTablesName']);
    $res = $normalization->createNewTablesFor2NF($partialDependencies, $tablesName, $table, $db);
    $response->addJSON($res);
    exit;
}
if (isset($_REQUEST['createNewTables3NF'])) {
    $newtables = json_decode($_REQUEST['newTables']);
    $res = $normalization->createNewTablesFor3NF($newtables, $db);
    $response->addJSON($res);
    exit;
}
if (isset($_POST['repeatingColumns'])) {
    $repeatingColumns = $_POST['repeatingColumns'];
    $newTable = $_POST['newTable'];
    $newColumn = $_POST['newColumn'];
    $primary_columns = $_POST['primary_columns'];
    $res = $normalization->moveRepeatingGroup(
        $repeatingColumns, $primary_columns, $newTable, $newColumn, $table, $db
    );
    $response->addJSON($res);
    exit;
}
if (isset($_REQUEST['step1'])) {
    $html = $normalization->getHtmlFor1NFStep1($db, $table, $normalForm);
    $response->addHTML($html);
} elseif (isset($_REQUEST['step2'])) {
    $res = $normalization->getHtmlContentsFor1NFStep2($db, $table);
    $response->addJSON($res);
} elseif (isset($_REQUEST['step3'])) {
    $res = $normalization->getHtmlContentsFor1NFStep3($db, $table);
    $response->addJSON($res);
} elseif (isset($_REQUEST['step4'])) {
    $res = $normalization->getHtmlContentsFor1NFStep4($db, $table);
    $response->addJSON($res);
} elseif (isset($_REQUEST['step']) && $_REQUEST['step'] == '2.1') {
    $res = $normalization->getHtmlFor2NFstep1($db, $table);
    $response->addJSON($res);
} elseif (isset($_REQUEST['step']) && $_REQUEST['step'] == '3.1') {
    $tables = $_REQUEST['tables'];
    $res = $normalization->getHtmlFor3NFstep1($db, $tables);
    $response->addJSON($res);
} else {
    $response->addHTML($normalization->getHtmlForNormalizeTable());
}
