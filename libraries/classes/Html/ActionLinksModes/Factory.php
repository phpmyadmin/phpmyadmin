<?php
/**
 * ActionLinksModesInterface builder
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Html\ActionLinksModes;

/**
 * ActionLinksModesInterface builder
 *
 * @package PhpMyAdmin
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
        if ('text' === $mode) {
            return new Text();
        }
        return new Image();
    }
}
