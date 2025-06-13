<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\NodeType;

use function __;
use function _pgettext;

/**
 * Represents a container for index nodes in the navigation tree
 */
class NodeIndexContainer extends Node
{
    public function __construct(Config $config)
    {
        parent::__construct($config, __('Indexes'), NodeType::Container);

        $this->icon = new Icon('b_index', __('Indexes'), '/table/structure', ['db' => null, 'table' => null]);
        $this->link = new Link(
            '',
            '/table/structure',
            ['db' => null, 'table' => null],
        );
        $this->realName = 'indexes';

        $newLabel = _pgettext('Create new index', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_index italics');
        $new->icon = new Icon(
            'b_index_add',
            $newLabel,
            '/table/indexes',
            ['create_index' => 1, 'added_fields' => 2, 'db' => null, 'table' => null],
        );
        $new->link = new Link(
            $newLabel,
            '/table/indexes',
            ['create_index' => 1, 'added_fields' => 2, 'db' => null, 'table' => null],
        );
        $this->addChild($new);
    }
}
