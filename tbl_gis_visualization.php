<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the GIS visualizations.
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Controllers\Table\TableGisVisualizationController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;
use PhpMyAdmin\Core;

require_once 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory(
    'PhpMyAdmin\Controllers\Table\TableGisVisualizationController'
);
$container->alias(
    'TableGisVisualizationController',
    'PhpMyAdmin\Controllers\Table\TableGisVisualizationController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

$sqlQuery = null;

if (isset($_GET['sql_query']) && isset($_GET['sql_signature'])) {
    if (Core::checkSqlQuerySignature($_GET['sql_query'], $_GET['sql_signature'])) {
        $sqlQuery = $_GET['sql_query'];
    }
} elseif (isset($_POST['sql_query'])) {
    $sqlQuery = &$GLOBALS['sql_query'];
}

/* Define dependencies for the concerned controller */
$dependency_definitions = array(
    "sql_query" => $sqlQuery,
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
