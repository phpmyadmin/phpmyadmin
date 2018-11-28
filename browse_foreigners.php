<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display selection for relational field values
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;

require_once 'libraries/common.inc.php';

/**
 * Sets globals from $_REQUEST
 */
$request_params = [
    'data',
    'field',
];

foreach ($request_params as $one_request_param) {
    if (isset($_REQUEST[$one_request_param])) {
        $GLOBALS[$one_request_param] = $_REQUEST[$one_request_param];
    }
}

Util::checkParameters(['db', 'table', 'field']);

$response = Response::getInstance();
$response->getFooter()->setMinimal();
$header = $response->getHeader();
$header->disableMenuAndConsole();
$header->setBodyId('body_browse_foreigners');

$relation = new Relation($GLOBALS['dbi']);

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
    isset($_REQUEST['foreign_showAll']) ? $_REQUEST['foreign_showAll'] : null
);

$foreignData = $relation->getForeignData(
    $foreigners,
    $_REQUEST['field'],
    true,
    isset($_REQUEST['foreign_filter'])
    ? $_REQUEST['foreign_filter']
    : '',
    isset($foreign_limit) ? $foreign_limit : null,
    true // for getting value in $foreignData['the_total']
);

// HTML output
$html = $browseForeigners->getHtmlForRelationalFieldSelection(
    $db,
    $table,
    $_REQUEST['field'],
    $foreignData,
    isset($fieldkey) ? $fieldkey : '',
    isset($data) ? $data : ''
);

$response->addHtml($html);
