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
        $this->icon  = PMA_Util::getImage('b_index.png', __('Indexes'));
        $this->links = array(
            'text' => 'tbl_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s'
                    . '&amp;token=' . $GLOBALS['token'],
        );
        $this->real_name = 'indexes';

        $new_label = _pgettext('Create new index', 'New');
        $new        = PMA_NodeFactory::getInstance('Node', $new_label);
        $new->isNew = true;
        $new->icon  = PMA_Util::getImage('b_index_add.png', $new_label);
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
