<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\UtilExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class UtilExtension
 *
 * @package PhpMyAdmin\Twig
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
                'PhpMyAdmin\Util::backquote'
            ),
            new Twig_SimpleFunction(
                'Util_convertBitDefaultValue',
                'PhpMyAdmin\Util::convertBitDefaultValue'
            ),
            new Twig_SimpleFunction(
                'Util_escapeMysqlWildcards',
                'PhpMyAdmin\Util::escapeMysqlWildcards'
            ),
            new Twig_SimpleFunction(
                'Util_formatByteDown',
                'PhpMyAdmin\Util::formatByteDown'
            ),
            new Twig_SimpleFunction(
                'Util_formatNumber',
                'PhpMyAdmin\Util::formatNumber'
            ),
            new Twig_SimpleFunction(
                'Util_formatSql',
                'PhpMyAdmin\Util::formatSql',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getButtonOrImage',
                'PhpMyAdmin\Util::getButtonOrImage',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getDivForSliderEffect',
                'PhpMyAdmin\Util::getDivForSliderEffect',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getDocuLink',
                'PhpMyAdmin\Util::getDocuLink',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getDropdown',
                'PhpMyAdmin\Util::getDropdown',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getGISDatatypes',
                'PhpMyAdmin\Util::getGISDatatypes'
            ),
            new Twig_SimpleFunction(
                'Util_getGISFunctions',
                'PhpMyAdmin\Util::getGISFunctions'
            ),
            new Twig_SimpleFunction(
                'Util_getHtmlTab',
                'PhpMyAdmin\Util::getHtmlTab',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getIcon',
                'PhpMyAdmin\Util::getIcon',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getImage',
                'PhpMyAdmin\Util::getImage',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getRadioFields',
                'PhpMyAdmin\Util::getRadioFields',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getStartAndNumberOfRowsPanel',
                'PhpMyAdmin\Util::getStartAndNumberOfRowsPanel',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_getSupportedDatatypes',
                'PhpMyAdmin\Util::getSupportedDatatypes',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_linkOrButton',
                'PhpMyAdmin\Util::linkOrButton',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_localisedDate',
                'PhpMyAdmin\Util::localisedDate'
            ),
            new Twig_SimpleFunction(
                'Util_showHint',
                'PhpMyAdmin\Util::showHint',
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Util_showIcons',
                'PhpMyAdmin\Util::showIcons'
            ),
            new Twig_SimpleFunction(
                'Util_showMySQLDocu',
                'PhpMyAdmin\Util::showMySQLDocu',
                array('is_safe' => array('html'))
            ),
        );
    }
}
