<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display selection for relational field values
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/transformations.lib.php';
require_once 'libraries/browse_foreigners.lib.php';

/**
 * Sets globals from $_REQUEST
 */
$request_params = array(
    'data',
    'field'
);

foreach ($request_params as $one_request_param) {
    if (isset($_REQUEST[$one_request_param])) {
        $GLOBALS[$one_request_param] = $_REQUEST[$one_request_param];
    }
}

PMA_Util::checkParameters(array('db', 'table', 'field'));

$response = PMA_Response::getInstance();
$response->getFooter()->setMinimal();
$header = $response->getHeader();
$header->disableMenu();
$header->setBodyId('body_browse_foreigners');

/**
 * Displays the frame
 */

$cfgRelation = PMA_getRelationsParam();
$foreigners  = ($cfgRelation['relwork'] ? PMA_getForeigners($db, $table) : false);
$foreign_limit = PMA_getForeignLimit(
    isset($foreign_navig) ? $foreign_navig : null
);

$foreignData = PMA_getForeignData(
    $foreigners, $_REQUEST['field'], true,
    isset($_REQUEST['foreign_filter'])
    ? $_REQUEST['foreign_filter']
    : '',
    isset($foreign_limit) ? $foreign_limit : null
);


$code = PMA_getJsScriptToHandleSelectRelationalFields();

$header->getScripts()->addCode($code);

// HTML output
$html = PMA_getHtmlForRelationalFieldSelection(
    $db, $table, $_REQUEST['field'], $foreignData,
    isset($fieldkey) ? $fieldkey : null,
    isset($data) ? $data : null
);

$response->addHtml($html);
?>
