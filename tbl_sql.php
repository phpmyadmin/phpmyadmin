<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table SQL executor
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\SqlController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Response;
use Symfony\Component\DependencyInjection\Definition;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $db, $table;

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->set(Response::class, Response::getInstance());

/** @var Response $response */
$response = $container->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $container->get(DatabaseInterface::class);

/* Define dependencies for the concerned controller */
$dependency_definitions = [
    'db' => $container->get('db'),
    'table' => $container->get('table'),
];

/** @var Definition $definition */
$definition = $containerBuilder->getDefinition(SqlController::class);
array_map(
    static function (string $parameterName, $value) use ($definition) {
        $definition->replaceArgument($parameterName, $value);
    },
    array_keys($dependency_definitions),
    $dependency_definitions
);

/** @var SqlController $controller */
$controller = $containerBuilder->get(SqlController::class);

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
$scripts->addFile('sql.js');

$response->addHTML($controller->index([
    'delimiter' => $_POST['delimiter'] ?? null,
    'sql_query' => $_GET['sql_query'] ?? true,
]));
