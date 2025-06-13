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

        $this->icon = new Icon('b_views', __('Views'), '/database/structure', ['tbl_type' => 'view', 'db' => null]);
        $this->link = new Link(
            '',
            '/database/structure',
            ['tbl_type' => 'view', 'db' => null],
        );
        $this->classes = 'viewContainer subContainer';
        $this->realName = 'views';

        $newLabel = _pgettext('Create new view', 'New');
        $new = $this->getInstanceForNewNode($newLabel, 'new_view italics');
        $new->icon = new Icon('b_view_add', $newLabel, '/view/create', ['db' => null]);
        $new->link = new Link(
            $newLabel,
            '/view/create',
            ['db' => null],
        );
        $this->addChild($new);
    }
}
