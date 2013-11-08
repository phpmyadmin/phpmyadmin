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
 * Represents a container for functions nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Function_Container extends Node
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Functions'), Node::CONTAINER);
        $this->icon  = PMA_Util::getImage('b_routines.png', __('Functions'));
        $this->links = array(
            'text' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token']
                    . '&amp;type=FUNCTION',
            'icon' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token']
                    . '&amp;type=FUNCTION',
        );
        $this->real_name = 'functions';

        $new_label = _pgettext('Create new function', 'New');
        $new        = PMA_NodeFactory::getInstance('Node', $new_label);
        $new->isNew = true;
        $new->icon  = PMA_Util::getImage('b_routine_add.png', $new_label);
        $new->links = array(
            'text' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token']
                    . '&add_item=1&amp;item_type=FUNCTION',
            'icon' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token']
                    . '&add_item=1&amp;item_type=FUNCTION',
        );
        $new->classes = 'new_function italics';
        $this->addChild($new);
    }
}

?>
