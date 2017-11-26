<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\CharsetsExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class CharsetsExtension
 *
 * @package PhpMyAdmin\Twig
 */
class CharsetsExtension extends Twig_Extension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'Charsets_getCollationDescr',
                'PhpMyAdmin\Charsets::getCollationDescr'
            ),
            new Twig_SimpleFunction(
                'Charsets_getCharsetDropdownBox',
                'PhpMyAdmin\Charsets::getCharsetDropdownBox',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Charsets_getCollationDropdownBox',
                'PhpMyAdmin\Charsets::getCollationDropdownBox',
                array('is_safe' => array('html'))
            ),
        );
    }
}
