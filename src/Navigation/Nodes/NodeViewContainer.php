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
 * Represents a container for view nodes in the navigation tree
 */
class NodeViewContainer extends NodeDatabaseChildContainer
{
    public function __construct(Config $config)
    {
        parent::__construct($config, __('Views'));

        $this->icon = ['image' => 'b_views', 'title' => __('Views')];
        $this->links = [
            'text' => ['route' => '/database/structure', 'params' => ['tbl_type' => 'view', 'db' => null]],
            'icon' => ['route' => '/database/structure', 'params' => ['tbl_type' => 'view', 'db' => null]],
        ];
        $this->classes = 'viewContainer subContainer';
        $this->realName = 'views';

        $newLabel = _pgettext('Create new view', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_view italics');
        $new->icon = ['image' => 'b_view_add', 'title' => $newLabel];
        $new->links = [
            'text' => ['route' => '/view/create', 'params' => ['db' => null]],
            'icon' => ['route' => '/view/create', 'params' => ['db' => null]],
        ];
        $this->addChild($new);
    }
}
