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
require_once 'libraries/tbl_columns_definition_form.lib.php';
require_once 'libraries/Index.class.php';

if (isset($_REQUEST['getColumns'])) {
    $html = '<option selected disabled>' . __('Select one…') . '</option>'
        . '<option value="no_such_col">' . __('No such column') . '</option>';
    //get column whose datatype falls under string category
    $html .= PMA_getHtmlForColumnsList($db, $table, 'string');
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
$response = PMA_Response::getInstance();
$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('normalization.js');
$scripts->addFile('jquery/jquery.uitablefilter.js');
if (isset($_REQUEST['normalizeTo'])) {
    $normalForm = $_REQUEST['normalizeTo'];
    if ($normalForm != '1nf') {
        $response->addHTML(
            '<h3 style="text-align:center">'
            . __('Second/Third step of normalization') . '</h3>'
            . '<fieldset>'
            . '<legend>Coming soon...</legend>'
            . 'Wait is worth it :-)</fieldset>'
        );
        exit;
    }
}

if (isset($_REQUEST['step1'])) {
    $html = PMA_getHtmlFor1NFStep1($db, $table);
    $response->addHTML($html);
} else if (isset($_REQUEST['step2'])) {
    $res = PMA_getHtmlContentsFor1NFStep2($db, $table);
    $response->addJSON($res);
} else if (isset($_REQUEST['step3'])) {
    $res = PMA_getHtmlContentsFor1NFStep3($db, $table);
    $response->addJSON($res);
} else {
    $response->addHTML(PMA_getHtmlForNormalizetable());
}
