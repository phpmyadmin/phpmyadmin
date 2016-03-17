<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the GIS visualizations.
 *
 * @package PhpMyAdmin
 */

namespace PMA;

use PMA\libraries\controllers\table\TableGisVisualizationController;
use PMA\libraries\Response;
use PMA\libraries\Util;

require_once 'libraries/common.inc.php';

$container = libraries\di\Container::getDefaultContainer();
$container->factory(
    'PMA\libraries\controllers\table\TableGisVisualizationController'
);
$container->alias(
    'TableGisVisualizationController',
    'PMA\libraries\controllers\table\TableGisVisualizationController'
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

/** @var TableGisVisualizationController $controller */
$controller = $container->get(
    'TableGisVisualizationController', $dependency_definitions
);
$controller->indexAction();
