<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the GIS visualizations.
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA\libraries\Response;
use PMA\libraries\Util;

require_once 'libraries/di/Container.class.php';
require_once 'libraries/Response.class.php';
require_once 'libraries/controllers/TableGisVisualizationController.class.php';
require_once 'libraries/Util.php';

$container = DI\Container::getDefaultContainer();
$container->factory('PMA\Controllers\Table\TableGisVisualizationController');
$container->alias(
    'TableGisVisualizationController',
    'PMA\Controllers\Table\TableGisVisualizationController'
);
$container->set('PMA\libraries\Response', Response::getInstance());
$container->alias('response', 'PMA\libraries\Response');

/* Define dependencies for the concerned controller */
$dependency_definitions = array(
    "sql_query" => &$GLOBALS['sql_query'],
    "url_params" => &$GLOBALS['url_params'],
    "goto" => Util::getScriptNameForOption(
        $GLOBALS['cfg']['DefaultTabDatabase'], 'database'
    ),
    "back" => 'sql.php',
    "visualizationSettings" => array()
);

/** @var Controllers\Table\TableGisVisualizationController $controller */
$controller = $container->get(
    'TableGisVisualizationController', $dependency_definitions
);
$controller->indexAction();
