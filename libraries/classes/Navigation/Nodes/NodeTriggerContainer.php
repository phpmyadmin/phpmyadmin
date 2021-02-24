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
 * Represents a container for trigger nodes in the navigation tree
 */
class NodeTriggerContainer extends Node
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Triggers'), Node::CONTAINER);
        $this->icon = Generator::getImage('b_triggers');
        $this->links = [
            'text' => Url::getFromRoute('/database/triggers', [
                'server' => $GLOBALS['server'],
            ]) . '&amp;db=%2$s&amp;table=%1$s',
            'icon' => Url::getFromRoute('/database/triggers', [
                'server' => $GLOBALS['server'],
            ]) . '&amp;db=%2$s&amp;table=%1$s',
        ];
        $this->realName = 'triggers';

        $newLabel = _pgettext('Create new trigger', 'New');
        $new = NodeFactory::getInstanceForNewNode(
            $newLabel,
            'new_trigger italics'
        );
        $new->icon = Generator::getImage('b_trigger_add', '');
        $new->links = [
            'text' => Url::getFromRoute('/database/triggers', [
                'server' => $GLOBALS['server'],
                'add_item' => 1,
            ]) . '&amp;db=%3$s',
            'icon' => Url::getFromRoute('/database/triggers', [
                'server' => $GLOBALS['server'],
                'add_item' => 1,
            ]) . '&amp;db=%3$s',
        ];
        $this->addChild($new);
    }
}
