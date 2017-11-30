<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\CharsetsExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class CharsetsExtension
 *
 * @package PhpMyAdmin\Twig
 */
class CharsetsExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction(
                'Charsets_getCollationDescr',
                'PhpMyAdmin\Charsets::getCollationDescr'
            ),
            new TwigFunction(
                'Charsets_getCharsetDropdownBox',
                'PhpMyAdmin\Charsets::getCharsetDropdownBox',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Charsets_getCollationDropdownBox',
                'PhpMyAdmin\Charsets::getCollationDropdownBox',
                array('is_safe' => array('html'))
            ),
        );
    }
}
