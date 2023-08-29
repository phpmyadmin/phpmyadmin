<?php

declare(strict_types=1);

namespace PhpMyAdmin\Properties\Options\Groups;

use PhpMyAdmin\Properties\Options\OptionsPropertyGroup;
use PhpMyAdmin\Properties\PropertyItem;

/**
 * Group property item class of type subgroup
 */
class OptionsPropertySubgroup extends OptionsPropertyGroup
{
    private PropertyItem|null $subgroupHeader = null;
    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Returns the property item type of either an instance of
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyOneItem ( f.e. "bool",
     *  "text", "radio", etc ) or
     *  - PhpMyAdmin\Properties\Options\OptionsPropertyGroup   ( "root", "main"
     *  or "subgroup" )
     *  - PhpMyAdmin\Properties\Plugins\PluginPropertyItem     ( "export", "import", "transformations" )
     */
    public function getItemType(): string
    {
        return 'subgroup';
    }

    public function getSubgroupHeader(): PropertyItem|null
    {
        return $this->subgroupHeader;
    }

    public function setSubgroupHeader(PropertyItem $subgroupHeader): void
    {
        $this->subgroupHeader = $subgroupHeader;
    }
}
