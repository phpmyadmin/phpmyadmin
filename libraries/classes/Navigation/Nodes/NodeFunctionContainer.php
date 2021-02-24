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
 * Represents a container for functions nodes in the navigation tree
 */
class NodeFunctionContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Functions'), Node::CONTAINER);
        $this->icon = Generator::getImage('b_routines', __('Functions'));
        $this->links = [
            'text' => Url::getFromRoute('/database/routines', [
                'server' => $GLOBALS['server'],
                'type' => 'FUNCTION',
            ]) . '&amp;db=%1$s',
            'icon' => Url::getFromRoute('/database/routines', [
                'server' => $GLOBALS['server'],
                'type' => 'FUNCTION',
            ]) . '&amp;db=%1$s',
        ];
        $this->realName = 'functions';

        $newLabel = _pgettext('Create new function', 'New');
        $new = NodeFactory::getInstanceForNewNode(
            $newLabel,
            'new_function italics'
        );
        $new->icon = Generator::getImage('b_routine_add', $newLabel);
        $new->links = [
            'text' => Url::getFromRoute('/database/routines', [
                'server' => $GLOBALS['server'],
                'item_type' => 'FUNCTION',
                'add_item' => 1,
            ]) . '&amp;db=%2$s',
            'icon' => Url::getFromRoute('/database/routines', [
                'server' => $GLOBALS['server'],
                'item_type' => 'FUNCTION',
                'add_item' => 1,
            ]) . '&amp;db=%2$s',
        ];
        $this->addChild($new);
    }
}
