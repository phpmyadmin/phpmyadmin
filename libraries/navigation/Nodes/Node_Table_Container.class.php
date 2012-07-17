<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
/**
 * Represents a container for table nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Table_Container extends Node
{
    /**
     * Initialises the class
     *
     * @return Node_Table_Container
     */
    public function __construct()
    {
        parent::__construct(__('Tables'), Node::CONTAINER);
        $this->icon  = $this->_commonFunctions->getImage('b_browse.png', '');
        $this->links = array(
            'text' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
        );
        if ($GLOBALS['cfg']['NavigationTreeEnableGrouping']) {
            $this->separator       = $GLOBALS['cfg']['NavigationTreeTableSeparator'];
            $this->separator_depth = (int)($GLOBALS['cfg']['NavigationTreeTableLevel']);
        }
        $this->real_name       = 'tables';

        $new        = new Node(__('New'));
        $new->isNew = true;
        $new->icon  = $this->_commonFunctions->getImage('b_table_add.png', '');
        $new->links = array(
            'text' => 'tbl_create.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_create.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token'],
        );
        $new->classes = 'new_table italics';
        $this->addChild($new);
    }
}

?>
