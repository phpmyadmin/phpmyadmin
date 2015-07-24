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
 * Represents a container for view nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_View_Container extends Node_DatabaseChild_Container
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Views'), Node::CONTAINER);
        $this->icon  = PMA_Util::getImage('b_views.png', __('Views'));
        $this->links = array(
            'text' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;tbl_type=view'
                    . '&amp;token=' . $_SESSION[' PMA_token '],
            'icon' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;tbl_type=view'
                    . '&amp;token=' . $_SESSION[' PMA_token '],
        );
        $this->classes   = 'viewContainer subContainer';
        $this->real_name = 'views';

        $new_label = _pgettext('Create new view', 'New');
        $new        = PMA_NodeFactory::getInstance('Node', $new_label);
        $new->isNew = true;
        $new->icon  = PMA_Util::getImage('b_view_add.png', $new_label);
        $new->links = array(
            'text' => 'view_create.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $_SESSION[' PMA_token '],
            'icon' => 'view_create.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $_SESSION[' PMA_token '],
        );
        $new->classes = 'new_view italics';
        $this->addChild($new);
    }
}

