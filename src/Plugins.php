<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Plugins\Plugin;
use PhpMyAdmin\Plugins\PluginType;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\OptionsPropertyGroup;
use PhpMyAdmin\Properties\Options\OptionsPropertyItem;
use PhpMyAdmin\Properties\Options\OptionsPropertyOneItem;

use function __;
use function array_map;
use function count;
use function in_array;
use function is_string;

class Plugins
{
    /**
     * Returns html input tag option 'checked' if plugin $opt
     * should be set by config or request
     *
     * @param string $opt name of option
     *
     * @return string  html input tag option 'checked'
     */
    public static function checkboxCheck(PluginType $pluginType, string $opt): string
    {
        // If the form is being repopulated using $_GET data, that is priority
        if (
            isset($_GET[$opt])
            || ! isset($_GET['repopulate'])
            && ((ImportSettings::$timeoutPassed && isset($_REQUEST[$opt]))
                || ! empty(Config::getInstance()->settings[$pluginType->value][$opt]))
        ) {
            return ' checked';
        }

        return '';
    }

    /**
     * Validates the plugin name and returns it, or falls back to 'sql' if invalid.
     *
     * @param ExportPlugin[]|ImportPlugin[] $plugins
     */
    public static function validatePluginNameOrUseDefault(array $plugins, string $pluginName): string
    {
        // If the format is invalid, fall back to 'sql' (issue: #19891)
        $validNames = array_map(static function ($plugin) {
            return $plugin->getName();
        }, $plugins);

        if (! in_array($pluginName, $validNames, true)) {
            return 'sql';
        }

        return $pluginName;
    }

    /**
     * Returns default value for option $opt
     *
     * @param PluginType $pluginType type of the plugin
     * @param string     $opt        name of option
     *
     * @return string  default value for option $opt
     */
    public static function getDefault(PluginType $pluginType, string $opt): string
    {
        if (isset($_GET[$opt]) && is_string($_GET[$opt])) {
            // If the form is being repopulated using $_GET data, that is priority
            return $_GET[$opt];
        }

        if (isset($_REQUEST[$opt]) && is_string($_REQUEST[$opt]) && ImportSettings::$timeoutPassed) {
            return $_REQUEST[$opt];
        }

        $config = Config::getInstance();
        if (isset($config->settings[$pluginType->value][$opt])) {
            return (string) $config->settings[$pluginType->value][$opt];
        }

        return '';
    }

    /**
     * @param ExportPlugin[]|ImportPlugin[]|SchemaPlugin[] $list
     *
     * @return array<int, array<string, bool|string>>
     * @psalm-return list<array{name: non-empty-lowercase-string, text: string, is_selected: bool, is_binary: bool}>
     */
    public static function getChoice(array $list, string $default): array
    {
        $return = [];
        foreach ($list as $plugin) {
            $pluginName = $plugin->getName();
            $properties = $plugin->getProperties();
            $return[] = [
                'name' => $pluginName,
                'text' => $plugin->getTranslatedText($properties->getText()),
                'is_selected' => $pluginName === $default,
                'is_binary' => $properties->getForceFile(),
            ];
        }

        return $return;
    }

    /**
     * Returns single option in a list element
     *
     * @param OptionsPropertyItem $propertyGroup options property main group instance
     *
     * @return string  table row with option
     */
    private static function getOneOption(
        Plugin $plugin,
        PluginType $pluginType,
        OptionsPropertyItem $propertyGroup,
    ): string {
        $ret = '';

        $properties = [];
        if (! $propertyGroup instanceof OptionsPropertySubgroup) {
            // for subgroup headers
            if ($propertyGroup instanceof OptionsPropertyOneItem) {
                $properties = [$propertyGroup];
            } else {
                // for main groups
                $ret .= "\n" . '<div id="' . $propertyGroup->getName() . '">';

                $text = $propertyGroup->getText();

                if ($text !== '') {
                    $ret .= '<h5 class="card-title mt-4 mb-2">' . $plugin->getTranslatedText($text) . '</h5>';
                }

                $ret .= '<ul class="list-group">' . "\n";
            }
        }

        if ($propertyGroup instanceof OptionsPropertyGroup) {
            $properties = $propertyGroup->getProperties();
        }

        foreach ($properties as $propertyItem) {
            if ($propertyItem instanceof OptionsPropertySubgroup) {
                // each subgroup can have a header, which may also be a form element
                $subgroupHeader = $propertyItem->getSubgroupHeader();
                if ($subgroupHeader !== null) {
                    $ret .= self::getOneOption($plugin, $pluginType, $subgroupHeader);
                }

                $ret .= '<li class="list-group-item"><ul class="list-group"';
                if ($subgroupHeader !== null && $subgroupHeader->getName() !== '') {
                    $ret .= ' id="ul_' . $subgroupHeader->getName() . '">';
                } else {
                    $ret .= '>';
                }

                $ret .= "\n";

                $ret .= self::getOneOption($plugin, $pluginType, $propertyItem);
                continue;
            }

            // single property item
            $ret .= self::getHtmlForProperty($plugin, $pluginType, $propertyItem);
        }

        if ($propertyGroup instanceof OptionsPropertySubgroup) {
            // end subgroup
            $ret .= '</ul>' . "\n";
        } elseif ($propertyGroup instanceof OptionsPropertyGroup) {
            // end main group
            $ret .= '</ul></div>' . "\n";
        }

        return $ret;
    }

    private static function getHtmlForProperty(
        Plugin $plugin,
        PluginType $pluginType,
        OptionsPropertyItem $propertyItem,
    ): string {
        if ($propertyItem instanceof OptionsPropertyOneItem) {
            return $propertyItem->getHtml($plugin, $pluginType) . "\n";
        }

        return '';
    }

    /**
     * Returns html div with editable options for plugin
     *
     * @param ExportPlugin[]|ImportPlugin[]|SchemaPlugin[] $list array with plugin instances
     *
     * @return string  html fieldset with plugin options
     */
    public static function getOptions(PluginType $pluginType, array $list): string
    {
        $ret = '';
        // Options for plugins that support them
        foreach ($list as $plugin) {
            $properties = $plugin->getProperties();
            $text = $properties->getText();
            $options = $properties->getOptions();

            $ret .= '<div id="' . $plugin->getName() . '_options" class="format_specific_options">';
            $ret .= '<h3>' . $plugin->getTranslatedText($text) . '</h3>';

            $noOptions = true;
            if ($options !== null) {
                /** @var OptionsPropertyMainGroup $propertyMainGroup */
                foreach ($options->getProperties() as $propertyMainGroup) {
                    // check for hidden properties
                    $noOptions = true;
                    foreach ($propertyMainGroup->getProperties() as $propertyItem) {
                        if (! $propertyItem instanceof HiddenPropertyItem) {
                            $noOptions = false;
                            break;
                        }
                    }

                    $ret .= self::getOneOption($plugin, $pluginType, $propertyMainGroup);
                }
            }

            if ($noOptions) {
                $ret .= '<p class="card-text">' . __('This format has no options') . '</p>';
            }

            $ret .= '</div>' . "\n\n";
        }

        return $ret;
    }

    public static function getDocumentationLinkHtml(OptionsPropertyOneItem $propertyGroup): string
    {
        $doc = $propertyGroup->getDoc();
        if ($doc === '') {
            return '';
        }

        if (is_string($doc)) {
            return MySQLDocumentation::showDocumentation('faq', $doc);
        }

        if (count($doc) === 2) {
            return MySQLDocumentation::show($doc[0], anchor: $doc[1]);
        }

        return MySQLDocumentation::show($doc[0]);
    }
}
