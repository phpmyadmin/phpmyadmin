<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;

use function __;
use function _pgettext;

/**
 * Represents a container for functions nodes in the navigation tree
 */
class NodeFunctionContainer extends NodeDatabaseChildContainer
{
    public function __construct(Config $config)
    {
        parent::__construct($config, __('Functions'));

        $this->icon = new Icon(
            'b_routines',
            __('Functions'),
            '/database/routines',
            ['type' => 'FUNCTION', 'db' => null],
        );
        $this->link = new Link(
            '',
            '/database/routines',
            ['type' => 'FUNCTION', 'db' => null],
        );
        $this->realName = 'functions';

        $newLabel = _pgettext('Create new function', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_function italics');
        $new->icon = new Icon(
            'b_routine_add',
            $newLabel,
            '/database/routines',
            ['item_type' => 'FUNCTION', 'add_item' => 1, 'db' => null],
        );
        $new->link = new Link(
            $newLabel,
            '/database/routines',
            ['item_type' => 'FUNCTION', 'add_item' => 1, 'db' => null],
        );
        $this->addChild($new);
    }
}
