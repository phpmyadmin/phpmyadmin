<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\TableExtension class
 *
 * @package PhpMyAdmin\Twig
 */
declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class TableExtension
 *
 * @package PhpMyAdmin\Twig
 */
class TableExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'table_get',
                'PhpMyAdmin\Table::get'
            ),
        ];
    }
}
