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
 * Represents a view node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_View extends Node
{
    /**
     * Initialises the class
     *
     * @param string $name     An identifier for the new node
     * @param int    $type     Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $is_group Whether this object has been created
     *                         while grouping nodes
     *
     * @return Node_View
     */
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon  = PMA_Util::getImage('b_views.png');
        $this->links = array(
            'text' => 'sql.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s&amp;pos=0'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s'
                    . '&amp;token=' . $GLOBALS['token']
        );
    }

    /**
     * Returns HTML for hide button displayed infront of the view node
     *
     * @return HTML for hide button
     */
    public function getHtmlForControlButtons()
    {
        $ret = '';
        $cfgRelation = PMA_getRelationsParam();
        if ($cfgRelation['navwork']) {
            $db    = $this->realParent()->real_name;
            $view  = $this->real_name;
            $ret   = '<span class="navItemControls">'
                . '<a href="navigation.php?'
                . PMA_generate_common_url()
                . '&hideNavItem=true&itemType=view'
                . '&itemName=' . urldecode($view)
                . '&dbName=' . urldecode($db) . '"'
                . ' class="hideNavItem ajax">'
                . PMA_Util::getImage('b_close', 'Hide')
                . '</a></span>';
        }
        return $ret;
    }
}

?>
