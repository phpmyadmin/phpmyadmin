<?php

class Node_Procedure_Container extends Node {
    
    public function __construct()
    {
        parent::__construct(__('Procedures'), Node::CONTAINER);
        $this->icon = $this->_commonFunctions->getImage('b_routines.png');
        $this->links = array(
            'text' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
        );
        $this->real_name = 'procedures';

        $new = new Node(__('New'));
        $new->icon = $this->_commonFunctions->getImage('b_routine_add.png', '');
        $new->links = array(
            'text' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token']
                    . '&add_item=1',
            'icon' => 'db_routines.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token']
                    . '&add_item=1',
        );
        $new->classes = 'new_procedure italics';
        $this->addChild($new);
    }
}

?>
