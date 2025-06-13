<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;

use function __;

/**
 * Represents a function node in the navigation tree
 */
class NodeFunction extends NodeDatabaseChild
{
    /** @param string $name An identifier for the new node */
    public function __construct(Config $config, string $name)
    {
        parent::__construct($config, $name);

        $this->icon = new Icon(
            'b_routines',
            __('Function'),
            '/database/routines',
            ['item_type' => 'FUNCTION', 'execute_dialog' => 1, 'db' => null, 'item_name' => null],
        );
        $this->link = new Link(
            '',
            '/database/routines',
            ['item_type' => 'FUNCTION', 'edit_item' => 1, 'db' => null, 'item_name' => null],
        );
        $this->classes = 'function';
        $this->urlParamName = 'item_name';
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType(): string
    {
        return 'function';
    }
}
