<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/navigation/Nodes/Node_DatabaseChild_Container.class.php';

/**
 * Represents a container for procedure nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Procedure_Container extends Node_DatabaseChild_Container
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Procedures'), Node::CONTAINER);
        $this->icon  = PMA_Util::getImage('b_routines.png', __('Procedures'));
        $this->links = array(
            'text' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $_SESSION[' PMA_token ']
                    . '&amp;type=PROCEDURE',
            'icon' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $_SESSION[' PMA_token ']
                    . '&amp;type=PROCEDURE',
        );
        $this->real_name = 'procedures';

        $new_label = _pgettext('Create new procedure', 'New');
        $new        = PMA_NodeFactory::getInstance('Node', $new_label);
        $new->isNew = true;
        $new->icon  = PMA_Util::getImage('b_routine_add.png', $new_label);
        $new->links = array(
            'text' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $_SESSION[' PMA_token ']
                    . '&add_item=1',
            'icon' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $_SESSION[' PMA_token ']
                    . '&add_item=1',
        );
        $new->classes = 'new_procedure italics';
        $this->addChild($new);
    }
}

