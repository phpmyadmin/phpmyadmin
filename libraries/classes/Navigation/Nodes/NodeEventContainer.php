<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Url;

/**
 * Represents a container for events nodes in the navigation tree
 */
class NodeEventContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Events'), Node::CONTAINER);
        $this->icon = Generator::getImage('b_events', '');
        $this->links = [
            'text' => Url::getFromRoute('/database/events', [
                'server' => $GLOBALS['server'],
            ]) . '&amp;db=%1$s',
            'icon' => Url::getFromRoute('/database/events', [
                'server' => $GLOBALS['server'],
            ]) . '&amp;db=%1$s',
        ];
        $this->realName = 'events';

        $newLabel = _pgettext('Create new event', 'New');
        $new = NodeFactory::getInstanceForNewNode(
            $newLabel,
            'new_event italics'
        );
        $new->icon = Generator::getImage('b_event_add', '');
        $new->links = [
            'text' => Url::getFromRoute('/database/events', [
                'server' => $GLOBALS['server'],
                'add_item' => 1,
            ]) . '&amp;db=%2$s',
            'icon' => Url::getFromRoute('/database/events', [
                'server' => $GLOBALS['server'],
                'add_item' => 1,
            ]) . '&amp;db=%2$s',
        ];
        $this->addChild($new);
    }
}
