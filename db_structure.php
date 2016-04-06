<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database structure manipulation
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA\libraries\controllers\database\DatabaseStructureController;
use PMA\libraries\Response;
use PMA\libraries\Util;

require_once 'libraries/common.inc.php';
require_once 'libraries/db_common.inc.php';

$container = libraries\di\Container::getDefaultContainer();
$container->factory('PMA\libraries\controllers\database\DatabaseStructureController');
$container->alias(
    'DatabaseStructureController',
    'PMA\libraries\controllers\database\DatabaseStructureController'
);
$container->set('PMA\libraries\Response', Response::getInstance());
$container->alias('response', 'PMA\libraries\Response');

/* Define dependencies for the concerned controller */
$dependency_definitions = array(
    'db' => $db,
    'url_query' => &$GLOBALS['url_query'],
);

/** @var DatabaseStructureController $controller */
$controller = $container->get(
    'DatabaseStructureController',
    $dependency_definitions
);
$controller->indexAction();
