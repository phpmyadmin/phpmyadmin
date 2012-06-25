<?php

class Node_Column extends Node {
    
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon = $this->_commonFunctions->getImage('pause.png', '');
        $this->links = array(
            'text' => 'tbl_alter.php?server=' . $GLOBALS['server']
                    . '&amp;db=%3$s&amp;table=%2$s&amp;field=%1$s'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_alter.php?server=' . $GLOBALS['server']
                    . '&amp;db=%3$s&amp;table=%2$s&amp;field=%1$s'
                    . '&amp;token=' . $GLOBALS['token']
        );
    }
}

?>
