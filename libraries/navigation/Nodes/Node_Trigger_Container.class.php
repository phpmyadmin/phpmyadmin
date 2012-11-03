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
 * Represents a container for trigger nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Trigger_Container extends Node
{
    /**
     * Initialises the class
     *
     * @return Node_Trigger_Container
     */
    public function __construct()
    {
        parent::__construct(__('Triggers'), Node::CONTAINER);
        $this->icon  = PMA_Util::getImage('b_triggers.png');
        $this->links = array(
            'text' => 'db_triggers.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_triggers.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s&amp;token=' . $GLOBALS['token']
        );
        $this->real_name = 'triggers';

        $new        = PMA_NodeFactory::getInstance('Node', _pgettext('Create new trigger', 'New'));
        $new->isNew = true;
        $new->icon  = PMA_Util::getImage('b_trigger_add.png', '');
        $new->links = array(
            'text' => 'db_triggers.php?server=' . $GLOBALS['server']
                    . '&amp;db=%3$s&amp;token=' . $GLOBALS['token']
                    . '&amp;add_item=1',
            'icon' => 'db_triggers.php?server=' . $GLOBALS['server']
                    . '&amp;db=%3$s&amp;token=' . $GLOBALS['token']
                    . '&amp;add_item=1',
        );
        $new->classes = 'new_trigger italics';
        $this->addChild($new);
    }

}

?>
