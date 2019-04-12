<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays constraint edit/creation form and handles it
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\TableConstraintsController;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\CheckConstraint;
use PhpMyAdmin\Response;

require_once 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->factory('PhpMyAdmin\Controllers\Table\TableConstraintsController');
$container->alias(
    'TableConstraintsController',
    'PhpMyAdmin\Controllers\Table\TableConstraintsController'
);
$container->set('PhpMyAdmin\Response', Response::getInstance());
$container->alias('response', 'PhpMyAdmin\Response');

/* Define dependencies for the concerned controller */
$db = $container->get('db');
$table = $container->get('table');
$dbi = $container->get('dbi');

if (isset($_REQUEST['edit_constraint'])) {
    $constraint = CheckConstraint::getFromDb($table, $db, $_REQUEST['constraint']);
    if (count($constraint) === 0) {
        $error_msg = __('Could not fetch Constraint!');
        $response = Response::getInstance();
        $response->setRequestStatus(false);
        $response->addJSON('message', $error_msg);
    }
} else {
    $constraint = [];
}

$dependency_definitions = [
    "constraint" => $constraint,
];

/** @var TableConstraintsController $controller */
$controller = $container->get('TableConstraintsController', $dependency_definitions);
$controller->indexAction();
