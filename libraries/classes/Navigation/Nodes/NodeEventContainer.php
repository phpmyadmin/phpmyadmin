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
 * Represents a container for events nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeEventContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Events'), Node::CONTAINER);
        $this->icon = Util::getImage('b_events', '');
        $this->links = [
            'text' => 'db_events.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s',
            'icon' => 'db_events.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s',
        ];
        $this->realName = 'events';

        $newLabel = _pgettext('Create new event', 'New');
        $new = NodeFactory::getInstance(
            'Node',
            $newLabel
        );
        $new->isNew = true;
        $new->icon = Util::getImage('b_event_add', '');
        $new->title = $newLabel;
        $new->links = [
            'text' => 'db_events.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&add_item=1',
            'icon' => 'db_events.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&add_item=1',
        ];
        $new->classes = 'new_event italics';
        $this->addChild($new);
    }
}
