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
use PhpMyAdmin\Util;

/**
 * Represents a container for view nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeViewContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Views'), Node::CONTAINER);
        $this->icon = Util::getImage('b_views', __('Views'));
        $this->links = [
            'text' => 'db_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;tbl_type=view',
            'icon' => 'db_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;tbl_type=view',
        ];
        $this->classes = 'viewContainer subContainer';
        $this->realName = 'views';

        $newLabel = _pgettext('Create new view', 'New');
        $new = NodeFactory::getInstance(
            'Node',
            $newLabel
        );
        $new->isNew = true;
        $new->icon = Util::getImage('b_view_add', $newLabel);
        $new->title = $newLabel;
        $new->links = [
            'text' => 'view_create.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s',
            'icon' => 'view_create.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s',
        ];
        $new->classes = 'new_view italics';
        $this->addChild($new);
    }
}
