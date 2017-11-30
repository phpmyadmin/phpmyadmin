<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Util;

/**
 * Represents a container for table nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeTableContainer extends NodeDatabaseChildContainer
{
    /**
     * Initialises the class
     */
    public function __construct()
    {
        parent::__construct(__('Tables'), Node::CONTAINER);
        $this->icon = Util::getImage('b_browse', __('Tables'));
        $this->links = array(
            'text' => 'db_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;tbl_type=table',
            'icon' => 'db_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;tbl_type=table',
        );
        $this->real_name = 'tables';
        $this->classes = 'tableContainer subContainer';

        $new_label = _pgettext('Create new table', 'New');
        $new = NodeFactory::getInstance(
            'Node',
            $new_label
        );
        $new->isNew = true;
        $new->icon = Util::getImage('b_table_add', $new_label);
        $new->links = array(
            'text' => 'tbl_create.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s',
            'icon' => 'tbl_create.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s',
        );
        $new->classes = 'new_table italics';
        $this->addChild($new);
    }
}
