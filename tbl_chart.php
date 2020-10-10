<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles creation of the chart
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\ChartController;
use Symfony\Component\DependencyInjection\Definition;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'sql_query' => &$GLOBALS['sql_query'],
    'url_query' => &$GLOBALS['url_query'],
    'cfg' => &$GLOBALS['cfg'],
];

/** @var Definition $definition */
$definition = $containerBuilder->getDefinition(ChartController::class);
$parameterBag = $containerBuilder->getParameterBag();
array_map(
    static function (string $parameterName, $value) use ($definition, $parameterBag) {
        $definition->replaceArgument($parameterName, $parameterBag->escapeValue($value));
    },
    array_keys($dependency_definitions),
    $dependency_definitions
);

/** @var ChartController $controller */
$controller = $containerBuilder->get(ChartController::class);
$controller->indexAction();
