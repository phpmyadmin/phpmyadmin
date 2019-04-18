<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The navigation panel - displays server, db and table selection tree
 *
 * @package PhpMyAdmin-Navigation
 */
declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Di\Container;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$container = Container::getDefaultContainer();
$container->set(Response::class, Response::getInstance());

/** @var Response $response */
$response = $container->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $container->get(DatabaseInterface::class);

$navigation = new Navigation();
if (! $response->isAjax()) {
    $response->addHTML(
        PhpMyAdmin\Message::error(
            __('Fatal error: The navigation can only be accessed via AJAX')
        )
    );
    exit;
}

if (isset($_POST['getNaviSettings']) && $_POST['getNaviSettings']) {
    $response->addJSON('message', PageSettings::getNaviSettings());
    exit();
}

$relation = new Relation($dbi);
$cfgRelation = $relation->getRelationsParam();
if ($cfgRelation['navwork']) {
    if (isset($_POST['hideNavItem'])) {
        if (! empty($_POST['itemName'])
            && ! empty($_POST['itemType'])
            && ! empty($_POST['dbName'])
        ) {
            $navigation->hideNavigationItem(
                $_POST['itemName'],
                $_POST['itemType'],
                $_POST['dbName'],
                (! empty($_POST['tableName']) ? $_POST['tableName'] : null)
            );
        }
        exit;
    }

    if (isset($_POST['unhideNavItem'])) {
        if (! empty($_POST['itemName'])
            && ! empty($_POST['itemType'])
            && ! empty($_POST['dbName'])
        ) {
            $navigation->unhideNavigationItem(
                $_POST['itemName'],
                $_POST['itemType'],
                $_POST['dbName'],
                (! empty($_POST['tableName']) ? $_POST['tableName'] : null)
            );
        }
        exit;
    }

    if (isset($_POST['showUnhideDialog'])) {
        if (! empty($_POST['dbName'])) {
            $response->addJSON(
                'message',
                $navigation->getItemUnhideDialog($_POST['dbName'])
            );
        }
        exit;
    }
}

// Do the magic
$response->addJSON('message', $navigation->getDisplay());
