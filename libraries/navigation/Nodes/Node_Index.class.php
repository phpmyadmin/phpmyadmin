<?php

class Node_Index extends Node {
    
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon = $this->_commonFunctions->getImage('b_index.png');
        $this->links = array(
            'text' => 'tbl_indexes.php?server=' . $GLOBALS['server']
                    . '&amp;db=%3$s&amp;table=%2$s&amp;index=%1$s'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_indexes.php?server=' . $GLOBALS['server']
                    . '&amp;db=%3$s&amp;table=%2$s&amp;index=%1$s'
                    . '&amp;token=' . $GLOBALS['token']
        );
    }
}

?>
