<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/check_user_privileges.lib.php';

/**
 * Represents a container for database nodes in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Node_Database_Container extends Node
{
    /**
     * Initialises the class
     *
     * @param string $name An identifier for the new node
     */
    public function __construct($name)
    {
        parent::__construct($name, Node::CONTAINER);

        if ($GLOBALS['is_create_db_priv']
            && $GLOBALS['cfg']['ShowCreateDb'] !== false
        ) {
            $new        = PMA_NodeFactory::getInstance(
                'Node', _pgettext('Create new database', 'New')
            );
            $new->isNew = true;
            $new->icon  = PMA_Util::getImage('b_newdb.png', '');
            $new->links = array(
                'text' => 'server_databases.php?server=' . $GLOBALS['server']
                        . '&amp;token=' . $_SESSION[' PMA_token '],
                'icon' => 'server_databases.php?server=' . $GLOBALS['server']
                        . '&amp;token=' . $_SESSION[' PMA_token '],
            );
            $new->classes = 'new_database italics';
            $this->addChild($new);
        }
    }
}
