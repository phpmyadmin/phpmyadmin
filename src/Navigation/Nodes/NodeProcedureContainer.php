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
 * Represents a container for procedure nodes in the navigation tree
 */
class NodeProcedureContainer extends NodeDatabaseChildContainer
{
    public function __construct(Config $config)
    {
        parent::__construct($config, __('Procedures'));

        $this->icon = new Icon(
            'b_routines',
            __('Procedures'),
            '/database/routines',
            ['type' => 'PROCEDURE', 'db' => null],
        );
        $this->link = new Link(
            '',
            '/database/routines',
            ['type' => 'PROCEDURE', 'db' => null],
        );
        $this->realName = 'procedures';

        $newLabel = _pgettext('Create new procedure', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_procedure italics');
        $new->icon = new Icon(
            'b_routine_add',
            $newLabel,
            '/database/routines',
            ['add_item' => 1, 'db' => null],
        );
        $new->link = new Link(
            $newLabel,
            '/database/routines',
            ['add_item' => 1, 'db' => null],
        );
        $this->addChild($new);
    }
}
