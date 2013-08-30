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

/**
 * Represents a container for view nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_View_Container extends Node
{
    /**
     * Initialises the class
     *
     * @return Node_View_Container
     */
    public function __construct()
    {
        parent::__construct(__('Views'), Node::CONTAINER);
        $this->icon  = PMA_Util::getImage('b_views.png', '');
        $this->links = array(
            'text' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
        );
        $this->classes   = 'viewContainer';
        $this->real_name = 'views';

        $new        = PMA_NodeFactory::getInstance('Node', _pgettext('Create new view', 'New'));
        $new->isNew = true;
        $new->icon  = PMA_Util::getImage('b_view_add.png', '');
        $new->links = array(
            'text' => 'view_create.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'view_create.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token'],
        );
        $new->classes = 'new_view italics';
        $this->addChild($new);
    }
}

?>
