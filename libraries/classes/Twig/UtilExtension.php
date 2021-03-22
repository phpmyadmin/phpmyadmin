<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

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
                'extract_column_spec',
                'PhpMyAdmin\Util::extractColumnSpec'
            ),
            new TwigFunction(
                'format_byte_down',
                'PhpMyAdmin\Util::formatByteDown'
            ),
            new TwigFunction(
                'get_formatted_maximum_upload_size',
                'PhpMyAdmin\Util::getFormattedMaximumUploadSize'
            ),
            new TwigFunction(
                'format_number',
                'PhpMyAdmin\Util::formatNumber'
            ),
            new TwigFunction(
                'format_sql',
                '\PhpMyAdmin\Html\Generator::formatSql',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_docu_link',
                '\PhpMyAdmin\Html\MySQLDocumentation::getDocumentationLink',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_list_navigator',
                '\PhpMyAdmin\Html\Generator::getListNavigator',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'show_docu',
                '\PhpMyAdmin\Html\MySQLDocumentation::showDocumentation',
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
                'get_icon',
                '\PhpMyAdmin\Html\Generator::getIcon',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_image',
                '\PhpMyAdmin\Html\Generator::getImage',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_start_and_number_of_rows_panel',
                'PhpMyAdmin\Html\Generator::getStartAndNumberOfRowsPanel',
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
                'PhpMyAdmin\Html\Generator::linkOrButton',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'link_to_var_documentation',
                'PhpMyAdmin\Html\Generator::linkToVarDocumentation',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'localised_date',
                'PhpMyAdmin\Util::localisedDate'
            ),
            new TwigFunction(
                'show_hint',
                '\PhpMyAdmin\Html\Generator::showHint',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'show_icons',
                'PhpMyAdmin\Util::showIcons'
            ),
            new TwigFunction(
                'show_text',
                'PhpMyAdmin\Util::showText'
            ),
            new TwigFunction(
                'show_mysql_docu',
                '\PhpMyAdmin\Html\MySQLDocumentation::show',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_mysql_docu_url',
                'PhpMyAdmin\Util::getMySQLDocuURL',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'get_docu_url',
                'PhpMyAdmin\Util::getdocuURL',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'show_php_docu',
                '\PhpMyAdmin\Html\Generator::showPHPDocumentation',
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
                'PhpMyAdmin\Util::escapeMysqlWildcards'
            ),
        ];
    }
}
