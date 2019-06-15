<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles find and replace tab
 *
 * Displays find and replace form, allows previewing and do the replacing
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\SearchController;
use Symfony\Component\DependencyInjection\Definition;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/** @var string $url_query Overwritten in tbl_common.inc.php */
$url_query = null;

require_once ROOT_PATH . 'libraries/common.inc.php';
require_once ROOT_PATH . 'libraries/tbl_common.inc.php';

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'searchType' => 'replace',
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
