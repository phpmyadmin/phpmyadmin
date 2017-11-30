<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\UtilExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class UtilExtension
 *
 * @package PhpMyAdmin\Twig
 */
class UtilExtension extends AbstractExtension
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
                'Util_backquote',
                'PhpMyAdmin\Util::backquote'
            ),
            new TwigFunction(
                'Util_getBrowseUploadFileBlock',
                'PhpMyAdmin\Util::getBrowseUploadFileBlock',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_convertBitDefaultValue',
                'PhpMyAdmin\Util::convertBitDefaultValue'
            ),
            new TwigFunction(
                'Util_escapeMysqlWildcards',
                'PhpMyAdmin\Util::escapeMysqlWildcards'
            ),
            new TwigFunction(
                'Util_extractColumnSpec',
                'PhpMyAdmin\Util::extractColumnSpec'
            ),
            new TwigFunction(
                'Util_formatByteDown',
                'PhpMyAdmin\Util::formatByteDown'
            ),
            new TwigFunction(
                'Util_formatNumber',
                'PhpMyAdmin\Util::formatNumber'
            ),
            new TwigFunction(
                'Util_formatSql',
                'PhpMyAdmin\Util::formatSql',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getButtonOrImage',
                'PhpMyAdmin\Util::getButtonOrImage',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getClassForType',
                'PhpMyAdmin\Util::getClassForType',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getDivForSliderEffect',
                'PhpMyAdmin\Util::getDivForSliderEffect',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getDocuLink',
                'PhpMyAdmin\Util::getDocuLink',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getListNavigator',
                'PhpMyAdmin\Util::getListNavigator',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_showDocu',
                'PhpMyAdmin\Util::showDocu',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getDropdown',
                'PhpMyAdmin\Util::getDropdown',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getFKCheckbox',
                'PhpMyAdmin\Util::getFKCheckbox',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getGISDatatypes',
                'PhpMyAdmin\Util::getGISDatatypes'
            ),
            new TwigFunction(
                'Util_getGISFunctions',
                'PhpMyAdmin\Util::getGISFunctions'
            ),
            new TwigFunction(
                'Util_getHtmlTab',
                'PhpMyAdmin\Util::getHtmlTab',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getIcon',
                'PhpMyAdmin\Util::getIcon',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getImage',
                'PhpMyAdmin\Util::getImage',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getRadioFields',
                'PhpMyAdmin\Util::getRadioFields',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getSelectUploadFileBlock',
                'PhpMyAdmin\Util::getSelectUploadFileBlock',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getScriptNameForOption',
                'PhpMyAdmin\Util::getScriptNameForOption',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getStartAndNumberOfRowsPanel',
                'PhpMyAdmin\Util::getStartAndNumberOfRowsPanel',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_getSupportedDatatypes',
                'PhpMyAdmin\Util::getSupportedDatatypes',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_isForeignKeySupported',
                'PhpMyAdmin\Util::isForeignKeySupported'
            ),
            new TwigFunction(
                'Util_linkOrButton',
                'PhpMyAdmin\Util::linkOrButton',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_localisedDate',
                'PhpMyAdmin\Util::localisedDate'
            ),
            new TwigFunction(
                'Util_showHint',
                'PhpMyAdmin\Util::showHint',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_showDocu',
                'PhpMyAdmin\Util::showDocu',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_showIcons',
                'PhpMyAdmin\Util::showIcons'
            ),
            new TwigFunction(
                'Util_showMySQLDocu',
                'PhpMyAdmin\Util::showMySQLDocu',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Util_sortableTableHeader',
                'PhpMyAdmin\Util::sortableTableHeader',
                array('is_safe' => array('html'))
            ),
        );
    }
}
