<?php

class Node_View extends Node {
    
    public function __construct($name, $type = Node::OBJECT, $is_group = false)
    {
        parent::__construct($name, $type, $is_group);
        $this->icon = $this->_commonFunctions->getImage('b_views.png');
        $this->links = array(
            'text' => 'sql.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s&amp;pos=0'
                    . '&amp;token=' . $GLOBALS['token'],
            'icon' => 'tbl_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;table=%1$s'
                    . '&amp;token=' . $GLOBALS['token']
        );
    }
}

?>
