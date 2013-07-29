<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The navigation panel - displays server, db and table selection tree
 *
 * @package PhpMyAdmin-Navigation
 */

// Include common functionalities
require_once './libraries/common.inc.php';

// Also initialises the collapsible tree class
require_once './libraries/navigation/Navigation.class.php';

$response = PMA_Response::getInstance();
$navigation = new PMA_Navigation();
if (! $response->isAjax()) {
    $response->addHTML(
        PMA_Message::error(
            __('Fatal error: The navigation can only be accessed via AJAX')
        )
    );
    exit;
}

$cfgRelation = PMA_getRelationsParam();
if ($cfgRelation['navwork']) {
    if (isset($_REQUEST['hideNavItem'])) {
        if (! empty($_REQUEST['itemName'])
            && ! empty($_REQUEST['itemType'])
            && ! empty($_REQUEST['dbName'])
        ) {
            $navigation->hideNavigationItem(
                $_REQUEST['itemName'],
                $_REQUEST['itemType'],
                $_REQUEST['dbName'],
                (! empty($_REQUEST['tableName']) ? $_REQUEST['tableName'] : null)
            );
        }
        exit;
    }

    if (isset($_REQUEST['unhideNavItem'])) {
        if (! empty($_REQUEST['itemName'])
            && ! empty($_REQUEST['itemType'])
            && ! empty($_REQUEST['dbName'])
        ) {
            $navigation->unhideNavigationItem(
                $_REQUEST['itemName'],
                $_REQUEST['itemType'],
                $_REQUEST['dbName'],
                (! empty($_REQUEST['tableName']) ? $_REQUEST['tableName'] : null)
            );
        }
        exit;
    }

    if (isset($_REQUEST['showUnhideDialog'])) {
        if (! empty($_REQUEST['dbName'])) {
            $response->addJSON(
                'message',
                $navigation->getItemUnhideDialog($_REQUEST['dbName'])
            );
        }
        exit;
    }
}

// Do the magic
$response->addJSON('message', $navigation->getDisplay());
?>
