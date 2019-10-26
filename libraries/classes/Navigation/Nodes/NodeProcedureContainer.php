<?php
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
 * Represents a container for procedure nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeProcedureContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Procedures'), Node::CONTAINER);
        $this->icon = Util::getImage('b_routines', __('Procedures'));
        $this->links = [
            'text' => Url::getFromRoute('/database/routines', [
                'server' => $GLOBALS['server'],
                'type' => 'PROCEDURE',
            ]) . '&amp;db=%1$s',
            'icon' => Url::getFromRoute('/database/routines', [
                'server' => $GLOBALS['server'],
                'type' => 'PROCEDURE',
            ]) . '&amp;db=%1$s',
        ];
        $this->realName = 'procedures';

        $newLabel = _pgettext('Create new procedure', 'New');
        $new = NodeFactory::getInstance(
            'Node',
            $newLabel
        );
        $new->isNew = true;
        $new->icon = Util::getImage('b_routine_add', $newLabel);
        $new->links = [
            'text' => Url::getFromRoute('/database/routines', [
                'server' => $GLOBALS['server'],
                'add_item' => 1,
            ]) . '&amp;db=%2$s',
            'icon' => Url::getFromRoute('/database/routines', [
                'server' => $GLOBALS['server'],
                'add_item' => 1,
            ]) . '&amp;db=%2$s',
        ];
        $new->classes = 'new_procedure italics';
        $this->addChild($new);
    }
}
