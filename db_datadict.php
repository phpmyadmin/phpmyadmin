<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Renders data dictionary
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Database\DataDictionaryController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;
use Symfony\Component\DependencyInjection\Definition;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $db;

require_once ROOT_PATH . 'libraries/common.inc.php';

Util::checkParameters(['db']);

$container = Container::getDefaultContainer();
$container->set(Response::class, Response::getInstance());

/** @var Response $response */
$response = $container->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $container->get(DatabaseInterface::class);

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'db' => $container->get('db'),
    'relation' => new Relation($dbi),
    'transformations' => new Transformations(),
];

/** @var Definition $definition */
$definition = $containerBuilder->getDefinition(DataDictionaryController::class);
array_map(
    static function (string $parameterName, $value) use ($definition) {
        $definition->replaceArgument($parameterName, $value);
    },
    array_keys($dependency_definitions),
    $dependency_definitions
);

/** @var DataDictionaryController $controller */
$controller = $containerBuilder->get(DataDictionaryController::class);

$header = $response->getHeader();
$header->enablePrintView();

$response->addHTML($controller->index());
