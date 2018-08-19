<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The navigation panel - displays server, db and table selection tree
 *
 * @package PhpMyAdmin-Navigation
 */

// Include common functionalities
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Navigation\Navigation;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;

require_once './libraries/common.inc.php';

// Also initialises the collapsible tree class
$response = Response::getInstance();
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

$relation = new Relation();
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
