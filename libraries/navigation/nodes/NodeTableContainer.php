<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
namespace PMA\libraries\navigation\nodes;

use PMA;
use PMA\libraries\navigation\NodeFactory;

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
        $this->icon = PMA\libraries\Util::getImage('b_browse.png', __('Tables'));
        $this->links = array(
            'text' => 'db_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;tbl_type=table'
                . '&amp;token=' . $_SESSION[' PMA_token '],
            'icon' => 'db_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;tbl_type=table'
                . '&amp;token=' . $_SESSION[' PMA_token '],
        );
        $this->real_name = 'tables';
        $this->classes = 'tableContainer subContainer';

        $new_label = _pgettext('Create new table', 'New');
        $new = NodeFactory::getInstance(
            'Node',
            $new_label
        );
        $new->isNew = true;
        $new->icon = PMA\libraries\Util::getImage('b_table_add.png', $new_label);
        $new->links = array(
            'text' => 'tbl_create.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;token=' . $_SESSION[' PMA_token '],
            'icon' => 'tbl_create.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&amp;token=' . $_SESSION[' PMA_token '],
        );
        $new->classes = 'new_table italics';
        $this->addChild($new);
    }
}

