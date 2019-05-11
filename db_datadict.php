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
$definition = $containerBuilder->getDefinition('database_data_dictionary_controller');
$definition->setArguments(array_merge($definition->getArguments(), $dependency_definitions));

/** @var DataDictionaryController $controller */
$controller = $containerBuilder->get('database_data_dictionary_controller');

$header = $response->getHeader();
$header->enablePrintView();

$response->addHTML($controller->index());
