<?php
/**
 * Represents container node that carries children of a database
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
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

        $config = Config::getInstance();
        if (! $config->settings['NavigationTreeEnableGrouping']) {
            return;
        }

        $this->separator = $config->settings['NavigationTreeTableSeparator'];
        $this->separatorDepth = $config->settings['NavigationTreeTableLevel'];
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
