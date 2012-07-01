<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * the navigation frame - displays server, db and table selection tree
 *
 * @package PhpMyAdmin-Navigation
 */

// Include common functionalities
require_once './libraries/common.inc.php';

// The Nodes are the building blocks for the navigation tree
require_once './libraries/navigation/Nodes/Node.class.php';
// All of the below Nodes inherit from the base Node
require_once './libraries/navigation/Nodes/Node_Column.class.php';
require_once './libraries/navigation/Nodes/Node_Database.class.php';
require_once './libraries/navigation/Nodes/Node_Event.class.php';
require_once './libraries/navigation/Nodes/Node_Function.class.php';
require_once './libraries/navigation/Nodes/Node_Index.class.php';
require_once './libraries/navigation/Nodes/Node_Procedure.class.php';
require_once './libraries/navigation/Nodes/Node_Table.class.php';
require_once './libraries/navigation/Nodes/Node_Trigger.class.php';
require_once './libraries/navigation/Nodes/Node_View.class.php';
// Containers. Also inherit from the base Node
require_once './libraries/navigation/Nodes/Node_Column_Container.class.php';
require_once './libraries/navigation/Nodes/Node_Event_Container.class.php';
require_once './libraries/navigation/Nodes/Node_Function_Container.class.php';
require_once './libraries/navigation/Nodes/Node_Index_Container.class.php';
require_once './libraries/navigation/Nodes/Node_Procedure_Container.class.php';
require_once './libraries/navigation/Nodes/Node_Table_Container.class.php';
require_once './libraries/navigation/Nodes/Node_Trigger_Container.class.php';
require_once './libraries/navigation/Nodes/Node_View_Container.class.php';

// Generates a collapsible tree of database objects
require_once './libraries/navigation/NavigationTree.class.php';

// Also initialises the collapsible tree class
require_once './libraries/navigation/Navigation.class.php';

$GLOBALS['token'] = $_REQUEST['token'];

// Do the magic
$response = PMA_Response::getInstance();
if ($response->isAjax()) {
    $navigation = new PMA_Navigation();
    $response->addJSON('message', $navigation->getDisplay());
} else {
    $response->addHTML(
        PMA_Message::error(
            __('Fatal error: The navigation can only be accessed via ajax')
        )
    );
}
?>
