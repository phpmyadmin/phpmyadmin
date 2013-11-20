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
        $this->icon  = PMA_Util::getImage('b_views.png', __('Views'));
        $this->links = array(
            'text' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;tbl_type=view'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;tbl_type=view'
                    . '&amp;token=' . $GLOBALS['token'],
        );
        if ($GLOBALS['cfg']['NavigationTreeEnableGrouping']) {
            $this->separator       = $GLOBALS['cfg']['NavigationTreeTableSeparator'];
            $this->separator_depth = (int)(
                $GLOBALS['cfg']['NavigationTreeTableLevel']
            );
        }
        $this->classes   = 'viewContainer';
        $this->real_name = 'views';

        $new_label = _pgettext('Create new view', 'New');
        $new        = PMA_NodeFactory::getInstance('Node', $new_label);
        $new->isNew = true;
        $new->icon  = PMA_Util::getImage('b_view_add.png', $new_label);
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
