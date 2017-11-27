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
        $this->links = array(
            'text' => 'db_routines.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;type=PROCEDURE',
            'icon' => 'db_routines.php?server=' . $GLOBALS['server']
                . '&amp;db=%1$s&amp;type=PROCEDURE',
        );
        $this->real_name = 'procedures';

        $new_label = _pgettext('Create new procedure', 'New');
        $new = NodeFactory::getInstance(
            'Node',
            $new_label
        );
        $new->isNew = true;
        $new->icon = Util::getImage('b_routine_add', $new_label);
        $new->links = array(
            'text' => 'db_routines.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&add_item=1',
            'icon' => 'db_routines.php?server=' . $GLOBALS['server']
                . '&amp;db=%2$s&add_item=1',
        );
        $new->classes = 'new_procedure italics';
        $this->addChild($new);
    }
}
