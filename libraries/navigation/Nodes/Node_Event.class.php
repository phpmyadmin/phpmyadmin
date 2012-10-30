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
 * Represents a event node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Event extends Node
{
    /**
     * Initialises the class
     *
     * @param string $name     An identifier for the new node
     * @param int    $type     Type of node, may be one of CONTAINER or OBJECT
     * @param bool   $is_group Whether this object has been created
     *                         while grouping nodes
     *
     * @return Node_Event
     */
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon  = PMA_Util::getImage('b_events.png');
        $this->links = array(
            'text' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;item_name=%1$s&amp;edit_item=1'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;item_name=%1$s&amp;export_item=1'
                    . '&amp;token=' . $GLOBALS['token']
        );
        $this->classes = 'event';
    }

    /**
     * Returns the comment associated with node
     * This method should be overridden by specific type of nodes
     *
     * @return string
     */
    public function getComment()
    {
        $db    = PMA_Util::sqlAddSlashes(
            $this->realParent()->real_name
        );
        $event = PMA_Util::sqlAddSlashes(
            $this->real_name
        );
        $query  = "SELECT `EVENT_COMMENT` ";
        $query .= "FROM `INFORMATION_SCHEMA`.`EVENTS` ";
        $query .= "WHERE `EVENT_SCHEMA`='$db' ";
        $query .= "AND `EVENT_NAME`='$event' ";
        return PMA_DBI_fetch_value($query);
    }
}

?>
