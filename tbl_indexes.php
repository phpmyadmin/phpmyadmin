<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays index edit/creation form and handles it
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA_Index;
use PMA_Response;

require_once 'libraries/common.inc.php';
require_once 'libraries/di/Container.class.php';
require_once 'libraries/controllers/TableIndexesController.class.php';
require_once 'libraries/Response.class.php';
require_once 'libraries/Index.class.php';

$container = DI\Container::getDefaultContainer();
$container->factory('PMA\Controllers\Table\TableIndexesController');
$container->alias(
    'TableIndexesController', 'PMA\Controllers\Table\TableIndexesController'
);
$container->set('PMA_Response', PMA_Response::getInstance());
$container->alias('response', 'PMA_Response');

/* Define dependencies for the concerned controller */
$db = $container->get('db');
$table = $container->get('table');
$dbi = $container->get('dbi');

if (!isset($_REQUEST['create_edit_table'])) {
    include_once 'libraries/tbl_common.inc.php';
}
if (isset($_REQUEST['index'])) {
    if (is_array($_REQUEST['index'])) {
        // coming already from form
        $index = new PMA_Index($_REQUEST['index']);
    } else {
        $index = $dbi->getTable($db, $table)->getIndex($_REQUEST['index']);
    }
} else {
    $index = new PMA_Index;
}

$dependency_definitions = array(
    "index" => $index
);

/** @var Controllers\Table\TableIndexesController $controller */
$controller = $container->get('TableIndexesController', $dependency_definitions);
$controller->indexAction();
