<?php
/**
 * Represents container node that carries children of a database
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeType;

/**
 * Represents container node that carries children of a database
 */
abstract class NodeDatabaseChildContainer extends NodeDatabaseChild
{
    /**
     * Initialises the class by setting the common variables
     *
     * @param string $name An identifier for the new node
     */
    public function __construct(string $name)
    {
        parent::__construct($name, NodeType::Container);

        if (! $GLOBALS['cfg']['NavigationTreeEnableGrouping']) {
            return;
        }

        $this->separator = $GLOBALS['cfg']['NavigationTreeTableSeparator'];
        $this->separatorDepth = (int) $GLOBALS['cfg']['NavigationTreeTableLevel'];
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType(): string
    {
        return 'group';
    }
}
