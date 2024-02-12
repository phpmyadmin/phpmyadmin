<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\NodeType;

use function __;
use function _pgettext;

/**
 * Represents a container for trigger nodes in the navigation tree
 */
class NodeTriggerContainer extends Node
{
    public function __construct(Config $config)
    {
        parent::__construct($config, __('Triggers'), NodeType::Container);

        $this->icon = ['image' => 'b_triggers', 'title' => __('Triggers')];
        $this->links = [
            'text' => ['route' => '/triggers', 'params' => ['db' => null, 'table' => null]],
            'icon' => ['route' => '/triggers', 'params' => ['db' => null, 'table' => null]],
        ];
        $this->realName = 'triggers';

        $newLabel = _pgettext('Create new trigger', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_trigger italics');
        $new->icon = ['image' => 'b_trigger_add', 'title' => $newLabel];
        $new->links = [
            'text' => ['route' => '/triggers', 'params' => ['add_item' => 1, 'db' => null]],
            'icon' => ['route' => '/triggers', 'params' => ['add_item' => 1, 'db' => null]],
        ];
        $this->addChild($new);
    }
}
