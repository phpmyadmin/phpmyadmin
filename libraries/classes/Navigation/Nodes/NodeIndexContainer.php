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
 * Represents a container for index nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeIndexContainer extends Node
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Indexes'), Node::CONTAINER);
        $this->icon = Util::getImage('b_index', __('Indexes'));
        $this->links = [
            'text' => 'tbl_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s',
            'icon' => 'tbl_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s',
        ];
        $this->realName = 'indexes';

        $newLabel = _pgettext('Create new index', 'New');
        $new = NodeFactory::getInstance(
            'Node',
            $newLabel
        );
        $new->isNew = true;
        $new->icon = Util::getImage('b_index_add', $newLabel);
        $new->title = $newLabel;
        $new->links = [
            'text' => 'tbl_indexes.php?server=' . $GLOBALS['server']
                . '&amp;create_index=1&amp;added_fields=2'
                . '&amp;db=%3$s&amp;table=%2$s',
            'icon' => 'tbl_indexes.php?server=' . $GLOBALS['server']
                . '&amp;create_index=1&amp;added_fields=2'
                . '&amp;db=%3$s&amp;table=%2$s',
        ];
        $new->classes = 'new_index italics';
        $this->addChild($new);
    }
}
