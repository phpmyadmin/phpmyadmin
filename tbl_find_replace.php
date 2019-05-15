<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles find and replace tab
 *
 * Displays find and replace form, allows previewing and do the replacing
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\SearchController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $url_query;

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/tbl_common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(SearchController::class);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

$dependency_definitions = [
    'searchType' => 'replace',
    'url_query' => &$url_query
];

/** @var SearchController $controller */
$controller = $container->get(SearchController::class, $dependency_definitions);
$controller->indexAction();
