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
 * Represents a container for column nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeColumnContainer extends Node
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Columns'), Node::CONTAINER);
        $this->icon = Util::getImage('pause', __('Columns'));
        $this->links = [
            'text' => 'tbl_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s',
            'icon' => 'tbl_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;table=%1$s',
        ];
        $this->realName = 'columns';

        $newLabel = _pgettext('Create new column', 'New');
        $new = NodeFactory::getInstance(
            'Node',
            $newLabel
        );
        $new->isNew = true;
        $new->icon = Util::getImage('b_column_add', $newLabel);
        $new->links = [
            'text' => 'tbl_addfield.php?server=' . $GLOBALS['server']
                . '&amp;db=%3$s&amp;table=%2$s'
                . '&amp;field_where=last&after_field=',
            'icon' => 'tbl_addfield.php?server=' . $GLOBALS['server']
                . '&amp;db=%3$s&amp;table=%2$s'
                . '&amp;field_where=last&after_field=',
        ];
        $new->classes = 'new_column italics';
        $this->addChild($new);
    }
}
