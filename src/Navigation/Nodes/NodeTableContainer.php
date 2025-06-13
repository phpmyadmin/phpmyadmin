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
 * Represents a container for table nodes in the navigation tree
 */
class NodeTableContainer extends NodeDatabaseChildContainer
{
    public function __construct(Config $config)
    {
        parent::__construct($config, __('Tables'));

        $this->icon = new Icon('b_browse', __('Tables'), '/database/structure', ['tbl_type' => 'table', 'db' => null]);
        $this->link = new Link(
            '',
            '/database/structure',
            ['tbl_type' => 'table', 'db' => null],
        );
        $this->realName = 'tables';
        $this->classes = 'tableContainer subContainer';

        $newLabel = _pgettext('Create new table', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_table italics');
        $new->icon = new Icon('b_table_add', $newLabel, '/table/create', ['db' => null]);
        $new->link = new Link(
            $newLabel,
            '/table/create',
            ['db' => null],
        );
        $this->addChild($new);
    }
}
