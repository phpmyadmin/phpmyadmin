<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display selection for relational field values
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;

require_once 'libraries/common.inc.php';

/**
 * Sets globals from $_POST
 */
$request_params = array(
    'data',
    'field'
);

foreach ($request_params as $one_request_param) {
    if (isset($_POST[$one_request_param])) {
        $GLOBALS[$one_request_param] = $_POST[$one_request_param];
    }
}

Util::checkParameters(array('db', 'table', 'field'));

$response = Response::getInstance();
$response->getFooter()->setMinimal();
$header = $response->getHeader();
$header->disableMenuAndConsole();
$header->setBodyId('body_browse_foreigners');

$relation = new Relation();

/**
 * Displays the frame
 */
$foreigners = $relation->getForeigners($db, $table);
$browseForeigners = new BrowseForeigners(
    $GLOBALS['cfg']['LimitChars'],
    $GLOBALS['cfg']['MaxRows'],
    $GLOBALS['cfg']['RepeatCells'],
    $GLOBALS['cfg']['ShowAll'],
    $GLOBALS['pmaThemeImage']
);
$foreign_limit = $browseForeigners->getForeignLimit(
    isset($_POST['foreign_showAll']) ? $_POST['foreign_showAll'] : null
);

$foreignData = $relation->getForeignData(
    $foreigners, $_POST['field'], true,
    isset($_POST['foreign_filter'])
    ? $_POST['foreign_filter']
    : '',
    isset($foreign_limit) ? $foreign_limit : null,
    true // for getting value in $foreignData['the_total']
);

// HTML output
$html = $browseForeigners->getHtmlForRelationalFieldSelection(
    $db,
    $table,
    $_POST['field'],
    $foreignData,
    isset($fieldkey) ? $fieldkey : null,
    isset($data) ? $data : null
);

$response->addHtml($html);
