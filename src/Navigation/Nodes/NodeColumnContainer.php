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
 * Represents a container for column nodes in the navigation tree
 */
class NodeColumnContainer extends Node
{
    public function __construct(Config $config)
    {
        parent::__construct($config, __('Columns'), NodeType::Container);

        $this->icon = new Icon('pause', __('Columns'), '/table/structure', ['db' => null, 'table' => null]);
        $this->link = new Link(
            '',
            '/table/structure',
            ['db' => null, 'table' => null],
        );
        $this->realName = 'columns';

        $newLabel = _pgettext('Create new column', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_column italics');
        $new->icon = new Icon(
            'b_column_add',
            $newLabel,
            '/table/add-field',
            ['field_where' => 'last', 'after_field' => '', 'db' => null, 'table' => null],
        );
        $new->link = new Link(
            $newLabel,
            '/table/add-field',
            ['field_where' => 'last', 'after_field' => '', 'db' => null, 'table' => null],
        );
        $this->addChild($new);
    }
}
