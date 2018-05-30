<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\UtilExtension class
 *
 * @package PhpMyAdmin\Twig
 */
declare(strict_types=1);

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
        return [
            new TwigFunction(
                'Util_backquote',
                'PhpMyAdmin\Util::backquote'
            ),
            new TwigFunction(
                'Util_getBrowseUploadFileBlock',
                'PhpMyAdmin\Util::getBrowseUploadFileBlock',
                ['is_safe' => ['html']]
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
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getButtonOrImage',
                'PhpMyAdmin\Util::getButtonOrImage',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getClassForType',
                'PhpMyAdmin\Util::getClassForType',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getDivForSliderEffect',
                'PhpMyAdmin\Util::getDivForSliderEffect',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getDocuLink',
                'PhpMyAdmin\Util::getDocuLink',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getListNavigator',
                'PhpMyAdmin\Util::getListNavigator',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_showDocu',
                'PhpMyAdmin\Util::showDocu',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getDropdown',
                'PhpMyAdmin\Util::getDropdown',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getFKCheckbox',
                'PhpMyAdmin\Util::getFKCheckbox',
                ['is_safe' => ['html']]
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
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getIcon',
                'PhpMyAdmin\Util::getIcon',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getImage',
                'PhpMyAdmin\Util::getImage',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getRadioFields',
                'PhpMyAdmin\Util::getRadioFields',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getSelectUploadFileBlock',
                'PhpMyAdmin\Util::getSelectUploadFileBlock',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getScriptNameForOption',
                'PhpMyAdmin\Util::getScriptNameForOption',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getStartAndNumberOfRowsPanel',
                'PhpMyAdmin\Util::getStartAndNumberOfRowsPanel',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_getSupportedDatatypes',
                'PhpMyAdmin\Util::getSupportedDatatypes',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_isForeignKeySupported',
                'PhpMyAdmin\Util::isForeignKeySupported'
            ),
            new TwigFunction(
                'Util_linkOrButton',
                'PhpMyAdmin\Util::linkOrButton',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_localisedDate',
                'PhpMyAdmin\Util::localisedDate'
            ),
            new TwigFunction(
                'Util_showHint',
                'PhpMyAdmin\Util::showHint',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_showDocu',
                'PhpMyAdmin\Util::showDocu',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_showIcons',
                'PhpMyAdmin\Util::showIcons'
            ),
            new TwigFunction(
                'Util_showMySQLDocu',
                'PhpMyAdmin\Util::showMySQLDocu',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'Util_sortableTableHeader',
                'PhpMyAdmin\Util::sortableTableHeader',
                ['is_safe' => ['html']]
            ),
        ];
    }
}
