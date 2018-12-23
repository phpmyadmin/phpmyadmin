<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays index edit/creation form and handles it
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\TableIndexesController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Index;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory('PhpMyAdmin\Controllers\Table\TableIndexesController');
$container->alias(
    'TableIndexesController',
    'PhpMyAdmin\Controllers\Table\TableIndexesController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/* Define dependencies for the concerned controller */
$db = $container->get('db');
$table = $container->get('table');
$dbi = $container->get('dbi');

if (! isset($_POST['create_edit_table'])) {
    include_once ROOT_PATH . 'libraries/tbl_common.inc.php';
}
if (isset($_POST['index'])) {
    if (is_array($_POST['index'])) {
        // coming already from form
        $index = new Index($_POST['index']);
    } else {
        $index = $dbi->getTable($db, $table)->getIndex($_POST['index']);
    }
} else {
    $index = new Index();
}

$dependency_definitions = [
    "index" => $index,
];

/** @var TableIndexesController $controller */
$controller = $container->get('TableIndexesController', $dependency_definitions);
$controller->indexAction();
