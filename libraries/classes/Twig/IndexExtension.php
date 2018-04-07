<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\IndexExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class IndexExtension
 *
 * @package PhpMyAdmin\Twig
 */
class IndexExtension extends AbstractExtension
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
                'Index_getHtmlForDisplayIndexes',
                'PhpMyAdmin\Index::getHtmlForDisplayIndexes',
                array('is_safe' => array('html'))
            ),
        );
    }
}
