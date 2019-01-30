<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Lists available transformation plugins
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\TransformationOverviewController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Transformations;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$response = Response::getInstance();
$header = $response->getHeader();
$header->disableMenuAndConsole();

$controller = new TransformationOverviewController(
    $response,
    $GLOBALS['dbi'],
    new Transformations()
);

$response->addHTML($controller->indexAction());
