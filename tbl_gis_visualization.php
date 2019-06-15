<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the GIS visualizations.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\GisVisualizationController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;
use Symfony\Component\DependencyInjection\Definition;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'sql_query' => &$GLOBALS['sql_query'],
    'url_params' => &$GLOBALS['url_params'],
    'goto' => Util::getScriptNameForOption(
        $GLOBALS['cfg']['DefaultTabDatabase'],
        'database'
    ),
    'back' => 'sql.php',
    'visualizationSettings' => [],
];

/** @var Definition $definition */
$definition = $containerBuilder->getDefinition(GisVisualizationController::class);
array_map(
    static function (string $parameterName, $value) use ($definition) {
        $definition->replaceArgument($parameterName, $value);
    },
    array_keys($dependency_definitions),
    $dependency_definitions
);

/** @var GisVisualizationController $controller */
$controller = $containerBuilder->get(GisVisualizationController::class);
$controller->indexAction();
