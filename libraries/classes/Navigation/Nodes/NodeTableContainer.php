<?php
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Url;

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
        $this->icon = Generator::getImage('b_browse', __('Tables'));
        $this->links = [
            'text' => Url::getFromRoute('/database/structure', [
                'server' => $GLOBALS['server'],
                'db' => '%1\$s',
                'tbl_type' => 'table',
            ]),
            'icon' => Url::getFromRoute('/database/structure', [
                'server' => $GLOBALS['server'],
                'db' => '%1\$s',
                'tbl_type' => 'table',
            ]),
        ];
        $this->realName = 'tables';
        $this->classes = 'tableContainer subContainer';

        $newLabel = _pgettext('Create new table', 'New');
        $new = NodeFactory::getInstance(
            'Node',
            $newLabel
        );
        $new->isNew = true;
        $new->icon = Generator::getImage('b_table_add', $newLabel);
        $new->links = [
            'text' => Url::getFromRoute('/table/create', [
                'server' => $GLOBALS['server'],
            ]) . '&amp;db=%2$s',
            'icon' => Url::getFromRoute('/table/create', [
                'server' => $GLOBALS['server'],
            ]) . '&amp;db=%2$s',
        ];
        $new->classes = 'new_table italics';
        $this->addChild($new);
    }
}
