<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
/**
 * Represents a container for index nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Index_Container extends Node
{
    /**
     * Initialises the class
     *
     * @return Node_Index_Container
     */
    public function __construct()
    {
        parent::__construct(__('Indexes'), Node::CONTAINER);
        $this->icon  = $this->_commonFunctions->getImage('b_index.png', '');
        $this->links = array(
            'text' => 'tbl_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s'
                    . '&amp;token=' . $GLOBALS['token'],
        );
        $this->real_name = 'indexes';

        $new = new Node(__('New'));
        $new->icon  = $this->_commonFunctions->getImage('b_index_add.png', '');
        $new->links = array(
            'text' => 'tbl_indexes.php?server=' . $GLOBALS['server']
                    . '&amp;create_index=1&amp;added_fields=2'
                    . '&amp;db=%3$s&amp;table=%2$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_indexes.php?server=' . $GLOBALS['server']
                    . '&amp;create_index=1&amp;added_fields=2'
                    . '&amp;db=%3$s&amp;table=%2$s&amp;token=' . $GLOBALS['token'],
        );
        $new->classes = 'new_index italics';
        $this->addChild($new);
    }
}

?>
