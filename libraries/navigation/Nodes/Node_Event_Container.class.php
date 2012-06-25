<?php

class Node_Event_Container extends Node {
    
    public function __construct()
    {
        parent::__construct(__('Events'), Node::CONTAINER);
        $this->icon = $this->_commonFunctions->getImage('b_events.png', '');
        $this->links = array(
            'text' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
            'icon' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%1$s&amp;token=' . $GLOBALS['token'],
        );
        $this->real_name = 'events';

        $new = new Node(__('New'));
        $new->icon = $this->_commonFunctions->getImage('b_event_add.png', '');
        $new->links = array(
            'text' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token']
                    . '&add_item=1',
            'icon' => 'db_events.php?server=' . $GLOBALS['server']
                    . '&amp;db=%2$s&amp;token=' . $GLOBALS['token']
                    . '&add_item=1',
        );
        $new->classes = 'new_event italics';
        $this->addChild($new);
    }
}

?>
