<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\IndexExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class IndexExtension
 *
 * @package PhpMyAdmin\Twig
 */
class IndexExtension extends Twig_Extension
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
                'Index_getHtmlForDisplayIndexes',
                'PhpMyAdmin\Index::getHtmlForDisplayIndexes',
                array('is_safe' => array('html'))
            ),
        );
    }
}
