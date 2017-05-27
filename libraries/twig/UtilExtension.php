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
                'Util_backquote',
                'PMA\libraries\Util::backquote'
            ),
            new Twig_SimpleFunction(
                'Util_convertBitDefaultValue',
                'PMA\libraries\Util::convertBitDefaultValue'
            ),
            new Twig_SimpleFunction(
                'Util_escapeMysqlWildcards',
                'PMA\libraries\Util::escapeMysqlWildcards'
            ),
            new Twig_SimpleFunction(
                'Util_formatByteDown',
                'PMA\libraries\Util::formatByteDown'
            ),
            new Twig_SimpleFunction(
                'Util_formatNumber',
                'PMA\libraries\Util::formatNumber'
            ),
            new Twig_SimpleFunction(
                'Util_formatSql',
                'PMA\libraries\Util::formatSql',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getButtonOrImage',
                'PMA\libraries\Util::getButtonOrImage',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getDivForSliderEffect',
                'PMA\libraries\Util::getDivForSliderEffect',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getDropdown',
                'PMA\libraries\Util::getDropdown',
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
                'Util_linkOrButton',
                'PMA\libraries\Util::linkOrButton',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_localisedDate',
                'PMA\libraries\Util::localisedDate'
            ),
            new Twig_SimpleFunction(
                'Util_showHint',
                'PMA\libraries\Util::showHint',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_showIcons',
                'PMA\libraries\Util::showIcons'
            ),
            new Twig_SimpleFunction(
                'Util_showMySQLDocu',
                'PMA\libraries\Util::showMySQLDocu',
                array('is_safe' => array('html'))
            ),
        );
    }
}
