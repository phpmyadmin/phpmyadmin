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
 * Represents a container for events nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Event_Container extends Node
{
    /**
     * Initialises the class
     *
     * @return Node_Event_Container
     */
    public function __construct()
    {
        parent::__construct(__('Events'), Node::CONTAINER);
        $this->icon  = PMA_Util::getImage('b_events.png', '');
        $this->links = array(
            'text' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
        );
        $this->real_name = 'events';

        $new        = PMA_NodeFactory::getInstance('Node', _pgettext('Create new event', 'New'));
        $new->isNew = true;
        $new->icon  = PMA_Util::getImage('b_event_add.png', '');
        $new->links = array(
            'text' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token']
                    . '&add_item=1',
            'icon' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token']
                    . '&add_item=1',
        );
        $new->classes = 'new_event italics';
        $this->addChild($new);
    }
}

?>
