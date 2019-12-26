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
use Twig\TwigFilter;
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
                'backquote',
                'PhpMyAdmin\Util::backquote'
            ),
            new TwigFunction(
                'get_browse_upload_file_block',
                'PhpMyAdmin\Util::getBrowseUploadFileBlock',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'extract_column_spec',
                'PhpMyAdmin\Util::extractColumnSpec'
            ),
            new TwigFunction(
                'format_byte_down',
                'PhpMyAdmin\Util::formatByteDown'
            ),
            new TwigFunction(
                'format_number',
                'PhpMyAdmin\Util::formatNumber'
            ),
            new TwigFunction(
                'format_sql',
                'PhpMyAdmin\Util::formatSql',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_button_or_image',
                'PhpMyAdmin\Util::getButtonOrImage',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_docu_link',
                'PhpMyAdmin\Util::getDocuLink',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_list_navigator',
                'PhpMyAdmin\Util::getListNavigator',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'show_docu',
                'PhpMyAdmin\Util::showDocu',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_dropdown',
                'PhpMyAdmin\Util::getDropdown',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_fk_checkbox',
                'PhpMyAdmin\Util::getFKCheckbox',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_gis_datatypes',
                'PhpMyAdmin\Util::getGISDatatypes'
            ),
            new TwigFunction(
                'get_gis_functions',
                'PhpMyAdmin\Util::getGISFunctions'
            ),
            new TwigFunction(
                'get_html_tab',
                'PhpMyAdmin\Util::getHtmlTab',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_icon',
                'PhpMyAdmin\Util::getIcon',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_image',
                'PhpMyAdmin\Util::getImage',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_radio_fields',
                'PhpMyAdmin\Util::getRadioFields',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_select_upload_file_block',
                'PhpMyAdmin\Util::getSelectUploadFileBlock',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_script_name_for_option',
                'PhpMyAdmin\Util::getScriptNameForOption',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_start_and_number_of_rows_panel',
                'PhpMyAdmin\Util::getStartAndNumberOfRowsPanel',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_supported_datatypes',
                'PhpMyAdmin\Util::getSupportedDatatypes',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'is_foreign_key_supported',
                'PhpMyAdmin\Util::isForeignKeySupported'
            ),
            new TwigFunction(
                'link_or_button',
                'PhpMyAdmin\Util::linkOrButton',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'link_to_var_documentation',
                'PhpMyAdmin\Util::linkToVarDocumentation',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'localised_date',
                'PhpMyAdmin\Util::localisedDate'
            ),
            new TwigFunction(
                'show_hint',
                'PhpMyAdmin\Util::showHint',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'show_icons',
                'PhpMyAdmin\Util::showIcons'
            ),
            new TwigFunction(
                'show_mysql_docu',
                'PhpMyAdmin\Util::showMySQLDocu',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_mysql_docu_url',
                'PhpMyAdmin\Util::getMySQLDocuURL',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'show_php_docu',
                'PhpMyAdmin\Util::showPHPDocu',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'sortable_table_header',
                'PhpMyAdmin\Util::sortableTableHeader',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'timespan_format',
                'PhpMyAdmin\Util::timespanFormat'
            ),
            new TwigFunction(
                'generate_hidden_max_file_size',
                'PhpMyAdmin\Util::generateHiddenMaxFileSize',
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'convert_bit_default_value',
                'PhpMyAdmin\Util::convertBitDefaultValue'
            ),
            new TwigFilter(
                'escape_mysql_wildcards',
                'PhpMyAdmin\Util::convertBitDefaultValue'
            ),
        ];
    }
}
