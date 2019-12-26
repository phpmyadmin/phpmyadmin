<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Util;

/**
 * Represents a container for trigger nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeTriggerContainer extends Node
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Triggers'), Node::CONTAINER);
        $this->icon = Util::getImage('b_triggers');
        $this->links = [
            'text' => 'db_triggers.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s',
            'icon' => 'db_triggers.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s',
        ];
        $this->realName = 'triggers';

        $new = NodeFactory::getInstance(
            'Node',
            _pgettext('Create new trigger', 'New')
        );
        $new->isNew = true;
        $new->icon = Util::getImage('b_trigger_add', '');
        $new->links = [
            'text' => 'db_triggers.php?server=' . $GLOBALS['server']
                . '&amp;db=%3$s&amp;add_item=1',
            'icon' => 'db_triggers.php?server=' . $GLOBALS['server']
                . '&amp;db=%3$s&amp;add_item=1',
        ];
        $new->classes = 'new_trigger italics';
        $this->addChild($new);
    }
}
