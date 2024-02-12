<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;

use function __;

/**
 * Represents a trigger node in the navigation tree
 */
class NodeTrigger extends Node
{
    /** @param string $name An identifier for the new node */
    public function __construct(Config $config, string $name)
    {
        parent::__construct($config, $name);

        $this->icon = ['image' => 'b_triggers', 'title' => __('Trigger')];
        $this->links = [
            'text' => [
                'route' => '/triggers',
                'params' => ['edit_item' => 1, 'db' => null, 'item_name' => null],
            ],
            'icon' => [
                'route' => '/triggers',
                'params' => ['export_item' => 1, 'db' => null, 'item_name' => null],
            ],
        ];
        $this->classes = 'trigger';
        $this->urlParamName = 'item_name';
    }
}
