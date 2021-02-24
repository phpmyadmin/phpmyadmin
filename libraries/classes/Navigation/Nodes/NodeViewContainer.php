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
 * Represents a container for view nodes in the navigation tree
 */
class NodeViewContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Views'), Node::CONTAINER);
        $this->icon = Generator::getImage('b_views', __('Views'));
        $this->links = [
            'text' => Url::getFromRoute('/database/structure', [
                'server' => $GLOBALS['server'],
                'tbl_type' => 'view',
            ]) . '&amp;db=%1$s',
            'icon' => Url::getFromRoute('/database/structure', [
                'server' => $GLOBALS['server'],
                'tbl_type' => 'view',
            ]) . '&amp;db=%1$s',
        ];
        $this->classes = 'viewContainer subContainer';
        $this->realName = 'views';

        $newLabel = _pgettext('Create new view', 'New');
        $new = NodeFactory::getInstanceForNewNode(
            $newLabel,
            'new_view italics'
        );
        $new->icon = Generator::getImage('b_view_add', $newLabel);
        $new->links = [
            'text' => Url::getFromRoute('/view/create', [
                'server' => $GLOBALS['server'],
            ]) . '&amp;db=%2$s',
            'icon' => Url::getFromRoute('/view/create', [
                'server' => $GLOBALS['server'],
            ]) . '&amp;db=%2$s',
        ];
        $this->addChild($new);
    }
}
