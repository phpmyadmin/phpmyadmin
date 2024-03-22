<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Navigation\NodeType;
use PhpMyAdmin\UserPrivilegesFactory;

use function _pgettext;

/**
 * Represents a container for database nodes in the navigation tree
 */
class NodeDatabaseContainer extends Node
{
    /** @param string $name An identifier for the new node */
    public function __construct(Config $config, string $name)
    {
        parent::__construct($config, $name, NodeType::Container);

        $userPrivilegesFactory = new UserPrivilegesFactory(DatabaseInterface::getInstance());
        $userPrivileges = $userPrivilegesFactory->getPrivileges();

        if (! $userPrivileges->isCreateDatabase || $this->config->settings['ShowCreateDb'] === false) {
            return;
        }

        $newLabel = _pgettext('Create new database', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_database italics');
        $new->icon = ['image' => 'b_newdb', 'title' => $newLabel];
        $new->links = [
            'text' => ['route' => '/server/databases', 'params' => []],
            'icon' => ['route' => '/server/databases', 'params' => []],
        ];
        $this->addChild($new);
    }
}
