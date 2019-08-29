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
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Represents a container for functions nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeFunctionContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Functions'), Node::CONTAINER);
        $this->icon = Util::getImage('b_routines', __('Functions'));
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
        $new = NodeFactory::getInstance(
            'Node',
            $newLabel
        );
        $new->isNew = true;
        $new->icon = Util::getImage('b_routine_add', $newLabel);
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
        $new->classes = 'new_function italics';
        $this->addChild($new);
    }
}
