<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;

use function __;

/**
 * Represents a index node in the navigation tree
 */
class NodeIndex extends Node
{
    /** @param string $name An identifier for the new node */
    public function __construct(Config $config, string $name)
    {
        parent::__construct($config, $name);

        $this->icon = new Icon(
            'b_index',
            __('Index'),
            '/table/indexes',
            ['db' => null, 'table' => null, 'index' => null],
        );
        $this->link = new Link(
            '',
            '/table/indexes',
            ['db' => null, 'table' => null, 'index' => null],
        );
        $this->classes = 'index';
        $this->urlParamName = 'index';
    }
}
