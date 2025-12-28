<?php
/**
 * Represents container node that carries children of a database
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\NodeType;

use function is_string;

/**
 * Represents container node that carries children of a database
 */
abstract class NodeDatabaseChildContainer extends NodeDatabaseChild
{
    /** @param string $name An identifier for the new node */
    public function __construct(Config $config, string $name)
    {
        parent::__construct($config, $name, NodeType::Container);

        if (! $this->config->settings['NavigationTreeEnableGrouping']) {
            return;
        }

        $this->setSeparators();

        $this->separatorDepth = $this->config->settings['NavigationTreeTableLevel'];
    }

    private function setSeparators(): void
    {
        $separators = $this->config->config->NavigationTreeTableSeparator;
        if (is_string($separators)) {
            if ($separators !== '') {
                $this->separators = [$separators];
            }
        } elseif ($separators !== false) {
            $this->separators = $separators;
        }
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
