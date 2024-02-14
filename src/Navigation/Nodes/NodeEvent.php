<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;

use function __;

/**
 * Represents a event node in the navigation tree
 */
class NodeEvent extends NodeDatabaseChild
{
    /** @param string $name An identifier for the new node */
    public function __construct(Config $config, string $name)
    {
        parent::__construct($config, $name);

        $this->icon = ['image' => 'b_events', 'title' => __('Event')];
        $this->links = [
            'text' => [
                'route' => '/database/events',
                'params' => ['edit_item' => 1, 'db' => null, 'item_name' => null],
            ],
            'icon' => [
                'route' => '/database/events',
                'params' => ['export_item' => 1, 'db' => null, 'item_name' => null],
            ],
        ];
        $this->classes = 'event';
        $this->urlParamName = 'item_name';
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType(): string
    {
        return 'event';
    }
}
