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

if (isset($_POST['getColumns'])) {
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
if (isset($_POST['splitColumn'])) {
    $num_fields = min(4096, intval($_POST['numFields']));
    $html = $normalization->getHtmlForCreateNewColumn($num_fields, $db, $table);
    $html .= Url::getHiddenInputs($db, $table);
    echo $html;
    exit;
}
if (isset($_POST['addNewPrimary'])) {
    $num_fields = 1;
    $columnMeta = array('Field'=>$table . "_id", 'Extra'=>'auto_increment');
    $html = $normalization->getHtmlForCreateNewColumn(
        $num_fields, $db, $table, $columnMeta
    );
    $html .= Url::getHiddenInputs($db, $table);
    echo $html;
    exit;
}
if (isset($_POST['findPdl'])) {
    $html = $normalization->findPartialDependencies($table, $db);
    echo $html;
    exit;
}

if (isset($_POST['getNewTables2NF'])) {
    $partialDependencies = json_decode($_POST['pd']);
    $html = $normalization->getHtmlForNewTables2NF($partialDependencies, $table);
    echo $html;
    exit;
}

$response = Response::getInstance();

if (isset($_POST['getNewTables3NF'])) {
    $dependencies = json_decode($_POST['pd']);
    $tables = json_decode($_POST['tables']);
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
if (Core::isValid($_POST['normalizeTo'], array('1nf', '2nf', '3nf'))) {
    $normalForm = $_POST['normalizeTo'];
}
if (isset($_POST['createNewTables2NF'])) {
    $partialDependencies = json_decode($_POST['pd']);
    $tablesName = json_decode($_POST['newTablesName']);
    $res = $normalization->createNewTablesFor2NF($partialDependencies, $tablesName, $table, $db);
    $response->addJSON($res);
    exit;
}
if (isset($_POST['createNewTables3NF'])) {
    $newtables = json_decode($_POST['newTables']);
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
if (isset($_POST['step1'])) {
    $html = $normalization->getHtmlFor1NFStep1($db, $table, $normalForm);
    $response->addHTML($html);
} elseif (isset($_POST['step2'])) {
    $res = $normalization->getHtmlContentsFor1NFStep2($db, $table);
    $response->addJSON($res);
} elseif (isset($_POST['step3'])) {
    $res = $normalization->getHtmlContentsFor1NFStep3($db, $table);
    $response->addJSON($res);
} elseif (isset($_POST['step4'])) {
    $res = $normalization->getHtmlContentsFor1NFStep4($db, $table);
    $response->addJSON($res);
} elseif (isset($_POST['step']) && $_POST['step'] == '2.1') {
    $res = $normalization->getHtmlFor2NFstep1($db, $table);
    $response->addJSON($res);
} elseif (isset($_POST['step']) && $_POST['step'] == '3.1') {
    $tables = $_POST['tables'];
    $res = $normalization->getHtmlFor3NFstep1($db, $tables);
    $response->addJSON($res);
} else {
    $response->addHTML($normalization->getHtmlForNormalizeTable());
}
