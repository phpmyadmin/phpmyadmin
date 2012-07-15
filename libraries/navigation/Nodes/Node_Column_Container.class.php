<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
/**
 * Represents a container for column nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Column_Container extends Node
{
    /**
     * Initialises the class
     *
     * @return Node_Column_Container
     */
    public function __construct()
    {
        parent::__construct(__('Columns'), Node::CONTAINER);
        $this->icon  = $this->_commonFunctions->getImage('pause.png', '');
        $this->links = array(
            'text' => 'tbl_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s'
                    . '&amp;token=' . $GLOBALS['token'],
        );
        $this->real_name = 'columns';

        $new        = new Node(__('New'));
        $new->isNew = true;
        $new->icon  = $this->_commonFunctions->getImage('b_column_add.png', '');
        $new->links = array(
            'text' => 'tbl_addfield.php?server=' . $GLOBALS['server']
                    . '&amp;db=%3$s&amp;table=%2$s'
                    . '&amp;field_where=last&after_field='
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_addfield.php?server=' . $GLOBALS['server']
                    . '&amp;db=%3$s&amp;table=%2$s'
                    . '&amp;field_where=last&after_field='
                    . '&amp;token=' . $GLOBALS['token'],
        );
        $new->classes = 'new_column italics';
        $this->addChild($new);
    }
}

?>
