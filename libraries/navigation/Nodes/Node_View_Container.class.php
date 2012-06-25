<?php

class Node_View_Container extends Node {
    
    public function __construct()
    {
        parent::__construct(__('Views'), Node::CONTAINER);
        $this->icon = $this->_commonFunctions->getImage('b_views.png', '');
        $this->links = array(
            'text' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_structure.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
        );
        $this->real_name = 'views';

        $new = new Node(__('New'));
        $new->icon = $this->_commonFunctions->getImage('b_view_add.png', '');
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
