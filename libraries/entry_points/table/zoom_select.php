<?php
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

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder, $url_query;

require_once ROOT_PATH . 'libraries/tbl_common.inc.php';

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'searchType' => 'zoom',
    'url_query' => &$url_query,
];

/** @var Definition $definition */
$definition = $containerBuilder->getDefinition(SearchController::class);
array_map(
    static function (string $parameterName, $value) use ($definition) {
        $definition->replaceArgument($parameterName, $value);
    },
    array_keys($dependency_definitions),
    $dependency_definitions
);

/** @var SearchController $controller */
$controller = $containerBuilder->get(SearchController::class);
$controller->indexAction();
