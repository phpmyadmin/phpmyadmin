<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;

use function __;

/**
 * Represents a procedure node in the navigation tree
 */
class NodeProcedure extends NodeDatabaseChild
{
    /** @param string $name An identifier for the new node */
    public function __construct(Config $config, string $name)
    {
        parent::__construct($config, $name);

        $this->icon = ['image' => 'b_routines', 'title' => __('Procedure')];
        $this->links = [
            'text' => [
                'route' => '/database/routines',
                'params' => ['item_type' => 'PROCEDURE', 'edit_item' => 1, 'db' => null, 'item_name' => null],
            ],
            'icon' => [
                'route' => '/database/routines',
                'params' => ['item_type' => 'PROCEDURE', 'execute_dialog' => 1, 'db' => null, 'item_name' => null],
            ],
        ];
        $this->classes = 'procedure';
        $this->urlParamName = 'item_name';
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType(): string
    {
        return 'procedure';
    }
}
