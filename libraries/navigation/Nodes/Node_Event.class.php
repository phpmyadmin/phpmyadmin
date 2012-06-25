<?php

class Node_Event extends Node {
    
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon = $this->_commonFunctions->getImage('b_events.png');
        $this->links = array(
            'text' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;item_name=%1$s&amp;edit_item=1'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;item_name=%1$s&amp;export_item=1'
                    . '&amp;token=' . $GLOBALS['token']
        );
    }
}

?>
