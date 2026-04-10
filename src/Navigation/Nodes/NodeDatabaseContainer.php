<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Navigation\NodeType;
use PhpMyAdmin\UserPrivilegesFactory;

use function _pgettext;

/**
 * Represents a container for database nodes in the navigation tree
 */
class NodeDatabaseContainer extends Node
{
    /** @param string $name An identifier for the new node */
    public function __construct(DatabaseInterface $dbi, Config $config, string $name)
    {
        parent::__construct($dbi, $config, $name, NodeType::Container);

        if (
            $config->config->NavigationTreeEnableGrouping
            && $config->config->ShowDatabasesNavigationAsTree
        ) {
            $separator = $config->config->NavigationTreeDbSeparator;
            if ($separator !== '') {
                $this->separators = [$separator];
            }

            $this->separatorDepth = 10000;
        }

        $userPrivilegesFactory = new UserPrivilegesFactory($this->dbi);
        $userPrivileges = $userPrivilegesFactory->getPrivileges();

        if (! $userPrivileges->isCreateDatabase || ! $config->config->ShowCreateDb) {
            return;
        }

        $newLabel = _pgettext('Create new database', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_database italics');
        $new->icon = new Icon('b_newdb', $newLabel, '/server/databases');
        $new->link = new Link($newLabel, '/server/databases');
        $this->addChild($new);
    }
}
