<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;

use function __;
use function _pgettext;

/**
 * Represents a container for events nodes in the navigation tree
 */
class NodeEventContainer extends NodeDatabaseChildContainer
{
    public function __construct(Config $config)
    {
        parent::__construct($config, __('Events'));

        $this->icon = ['image' => 'b_events', 'title' => __('Events')];
        $this->links = [
            'text' => ['route' => '/database/events', 'params' => ['db' => null]],
            'icon' => ['route' => '/database/events', 'params' => ['db' => null]],
        ];
        $this->realName = 'events';

        $newLabel = _pgettext('Create new event', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_event italics');
        $new->icon = ['image' => 'b_event_add', 'title' => $newLabel];
        $new->links = [
            'text' => ['route' => '/database/events', 'params' => ['add_item' => 1, 'db' => null]],
            'icon' => ['route' => '/database/events', 'params' => ['add_item' => 1, 'db' => null]],
        ];
        $this->addChild($new);
    }
}
