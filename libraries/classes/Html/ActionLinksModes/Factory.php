<?php
/**
 * ActionLinksModesInterface builder
 */
declare(strict_types=1);

namespace PhpMyAdmin\Html\ActionLinksModes;

/**
 * ActionLinksModesInterface builder
 */
class Factory
{
    /**
     * Build an ActionLinksModesInterface to generate the HTML element
     *
     * @param string $mode Mode (text, image)
     *
     * @return ActionLinksModesInterface
     */
    public static function build(string $mode): ActionLinksModesInterface
    {
        if ($mode === 'text') {
            return new Text();
        }
        return new Image();
    }
}
