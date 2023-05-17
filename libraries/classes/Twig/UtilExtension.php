<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\Gis;
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
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'backquote',
                Util::backquote(...),
            ),
            new TwigFunction(
                'extract_column_spec',
                Util::extractColumnSpec(...),
            ),
            new TwigFunction(
                'format_byte_down',
                Util::formatByteDown(...),
            ),
            new TwigFunction(
                'format_number',
                Util::formatNumber(...),
            ),
            new TwigFunction(
                'format_sql',
                Generator::formatSql(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'get_docu_link',
                MySQLDocumentation::getDocumentationLink(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'get_list_navigator',
                Generator::getListNavigator(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'show_docu',
                MySQLDocumentation::showDocumentation(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'get_gis_datatypes',
                Gis::getDataTypes(...),
            ),
            new TwigFunction(
                'get_gis_functions',
                Gis::getFunctions(...),
            ),
            new TwigFunction(
                'get_icon',
                Generator::getIcon(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'get_image',
                Generator::getImage(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'get_supported_datatypes',
                Generator::getSupportedDatatypes(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'is_uuid_supported',
                Util::isUUIDSupported(...),
            ),
            new TwigFunction(
                'link_or_button',
                Generator::linkOrButton(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'link_to_var_documentation',
                Generator::linkToVarDocumentation(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'show_hint',
                Generator::showHint(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'show_icons',
                Util::showIcons(...),
            ),
            new TwigFunction(
                'show_text',
                Util::showText(...),
            ),
            new TwigFunction(
                'show_mysql_docu',
                MySQLDocumentation::show(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'get_mysql_docu_url',
                Util::getMySQLDocuURL(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'get_docu_url',
                Util::getDocuURL(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'show_php_docu',
                Generator::showPHPDocumentation(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'sortable_table_header',
                Util::sortableTableHeader(...),
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'timespan_format',
                Util::timespanFormat(...),
            ),
            new TwigFunction('parse_enum_set_values', 'PhpMyAdmin\Util::parseEnumSetValues'),
        ];
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'convert_bit_default_value',
                Util::convertBitDefaultValue(...),
            ),
        ];
    }
}
