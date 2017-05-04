<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\libraries\twig\UtilExtension class
 *
 * @package PMA\libraries\twig
 */
namespace PMA\libraries\twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class UtilExtension
 *
 * @package PMA\libraries\twig
 */
class UtilExtension extends Twig_Extension
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
                'Util_formatSql',
                'PMA\libraries\Util::formatSql',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getHtmlTab',
                'PMA\libraries\Util::getHtmlTab',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getIcon',
                'PMA\libraries\Util::getIcon',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getImage',
                'PMA\libraries\Util::getImage',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getSupportedDatatypes',
                'PMA\libraries\Util::getSupportedDatatypes',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_showIcons',
                'PMA\libraries\Util::showIcons'
            ),
        );
    }
}
