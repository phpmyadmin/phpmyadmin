<?php
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Url;

/**
 * Represents a container for database nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeDatabaseContainer extends Node
{
    /**
     * Initialises the class
     *
     * @param string $name An identifier for the new node
     */
    public function __construct($name)
    {
        $checkUserPrivileges = new CheckUserPrivileges($GLOBALS['dbi']);
        $checkUserPrivileges->getPrivileges();

        parent::__construct($name, Node::CONTAINER);

        if ($GLOBALS['is_create_db_priv']
            && $GLOBALS['cfg']['ShowCreateDb'] !== false
        ) {
            $new = NodeFactory::getInstanceForNewNode(
                _pgettext('Create new database', 'New')
            );
            $new->icon = Generator::getImage('b_newdb', '');
            $new->links = [
                'text' => Url::getFromRoute('/server/databases', ['server' => $GLOBALS['server']]),
                'icon' => Url::getFromRoute('/server/databases', ['server' => $GLOBALS['server']]),
            ];
            $new->classes = 'new_database italics';
            $this->addChild($new);
        }
    }
}
