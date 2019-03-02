<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Renders data dictionary
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Database\DataDictionaryController;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

Util::checkParameters(['db']);

$response = Response::getInstance();

$controller = new DataDictionaryController(
    $response,
    $GLOBALS['dbi'],
    $db,
    new Relation($GLOBALS['dbi']),
    new Transformations()
);

$header = $response->getHeader();
$header->enablePrintView();

$response->addHTML($controller->index());
