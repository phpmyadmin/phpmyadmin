<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles table zoom search tab
 *
 * display table zoom search form, create SQL queries from form data
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\SearchController;
use Symfony\Component\DependencyInjection\Definition;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $url_query;

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/tbl_common.inc.php';

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'searchType' => 'zoom',
    'url_query' => &$url_query,
];

/** @var Definition $definition */
$definition = $containerBuilder->getDefinition(SearchController::class);
$parameterBag = $containerBuilder->getParameterBag();
array_map(
    static function (string $parameterName, $value) use ($definition, $parameterBag) {
        $definition->replaceArgument($parameterName, $parameterBag->escapeValue($value));
    },
    array_keys($dependency_definitions),
    $dependency_definitions
);

/** @var SearchController $controller */
$controller = $containerBuilder->get(SearchController::class);
$controller->indexAction();
