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
        $this->icon = Generator::getImage('pause', __('Columns'));
        $this->links = [
            'text' => Url::getFromRoute('/table/structure', [
                'server' => $GLOBALS['server'],
                'db' => '%2\$s',
                'table' => '%1\$s',
            ]),
            'icon' => Url::getFromRoute('/table/structure', [
                'server' => $GLOBALS['server'],
                'db' => '%2\$s',
                'table' => '%1\$s',
            ]),
        ];
        $this->realName = 'columns';

        $newLabel = _pgettext('Create new column', 'New');
        $new = NodeFactory::getInstanceForNewNode(
            $newLabel,
            'new_column italics'
        );
        $new->icon = Generator::getImage('b_column_add', $newLabel);
        $new->links = [
            'text' => Url::getFromRoute('/table/add-field', [
                'server' => $GLOBALS['server'],
                'field_where' => 'last',
                'after_field' => '',
            ]) . '&amp;db=%3$s&amp;table=%2$s',
            'icon' => Url::getFromRoute('/table/add-field', [
                'server' => $GLOBALS['server'],
                'field_where' => 'last',
                'after_field' => '',
            ]) . '&amp;db=%3$s&amp;table=%2$s',
        ];
        $this->addChild($new);
    }
}
