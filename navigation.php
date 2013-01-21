<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * the navigation frame - displays server, db and table selection tree
 *
 * @package PhpMyAdmin-Navigation
 */

// Include common functionalities
require_once './libraries/common.inc.php';

// Also initialises the collapsible tree class
require_once './libraries/navigation/Navigation.class.php';

// Do the magic
$response = PMA_Response::getInstance();
if ($response->isAjax()) {
    $navigation = new PMA_Navigation();
    $response->addJSON('message', $navigation->getDisplay());
} else {
    $response->addHTML(
        PMA_Message::error(
            __('Fatal error: The navigation can only be accessed via AJAX')
        )
    );
}
?>
