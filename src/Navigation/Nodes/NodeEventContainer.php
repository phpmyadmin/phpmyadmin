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

        $this->icon = new Icon('b_events', __('Events'), '/database/events', ['db' => null]);
        $this->link = new Link(
            '',
            '/database/events',
            ['db' => null],
        );
        $this->realName = 'events';

        $newLabel = _pgettext('Create new event', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_event italics');
        $new->icon = new Icon('b_event_add', $newLabel, '/database/events', ['add_item' => 1, 'db' => null]);
        $new->link = new Link(
            $newLabel,
            '/database/events',
            ['add_item' => 1, 'db' => null],
        );
        $this->addChild($new);
    }
}
