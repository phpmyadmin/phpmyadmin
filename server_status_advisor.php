<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * displays the advisor feature
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Server\Status\AdvisorController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/replication.inc.php';

$response = Response::getInstance();
$scripts = $response->getHeader()->getScripts();
$scripts->addFile('server_status_advisor.js');

$controller = new AdvisorController(
    $response,
    $GLOBALS['dbi'],
    new Data()
);

$response->addHTML($controller->index());
