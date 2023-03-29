<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use FilesystemIterator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Plugins\Plugin;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertySubgroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\DocPropertyItem;
use PhpMyAdmin\Properties\Options\Items\HiddenPropertyItem;
use PhpMyAdmin\Properties\Options\Items\MessageOnlyPropertyItem;
use PhpMyAdmin\Properties\Options\Items\NumberPropertyItem;
use PhpMyAdmin\Properties\Options\Items\RadioPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Properties\Options\Items\TextPropertyItem;
use PhpMyAdmin\Properties\Options\OptionsPropertyGroup;
use PhpMyAdmin\Properties\Options\OptionsPropertyItem;
use SplFileInfo;
use Throwable;

use function __;
use function class_exists;
use function count;
use function htmlspecialchars;
use function is_array;
use function is_subclass_of;
use function mb_strpos;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function method_exists;
use function preg_match_all;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strcasecmp;
use function usort;

class Plugins
{
    /**
     * Instantiates the specified plugin type for a certain format
     *
     * @param string            $type   the type of the plugin (import, export, etc)
     * @param string            $format the format of the plugin (sql, xml, et )
     * @param array|string|null $param  parameter to plugin by which they can decide whether they can work
     * @psalm-param array{export_type: string, single_table: bool}|string|null $param
     *
     * @return object|null new plugin instance
     */
    public static function getPlugin(string $type, string $format, array|string|null $param = null): object|null
    {
        $GLOBALS['plugin_param'] = $param;
        $pluginType = mb_strtoupper($type[0]) . mb_strtolower(mb_substr($type, 1));
        $pluginFormat = mb_strtoupper($format[0]) . mb_strtolower(mb_substr($format, 1));
        $class = sprintf('PhpMyAdmin\\Plugins\\%s\\%s%s', $pluginType, $pluginType, $pluginFormat);
        if (! class_exists($class)) {
            return null;
        }

        if ($type === 'export') {
            $container = Core::getContainerBuilder();

            /** @psalm-suppress MixedMethodCall */
            return new $class(
                $container->get('relation'),
                $container->get('export'),
                $container->get('transformations'),
            );
        }

        return new $class();
    }

    /**
     * @param string $type server|database|table|raw
     * @psalm-param 'server'|'database'|'table'|'raw' $type
     *
     * @return ExportPlugin[]
     * @psalm-return list<ExportPlugin>
     */
    public static function getExport(string $type, bool $singleTable): array
    {
        $GLOBALS['plugin_param'] = ['export_type' => $type, 'single_table' => $singleTable];

        return self::getPlugins('Export');
    }

    /**
     * @param string $type server|database|table
     * @psalm-param 'server'|'database'|'table' $type
     *
     * @return ImportPlugin[]
     * @psalm-return list<ImportPlugin>
     */
    public static function getImport(string $type): array
    {
        $GLOBALS['plugin_param'] = $type;

        return self::getPlugins('Import');
    }

    /**
     * @return SchemaPlugin[]
     * @psalm-return list<SchemaPlugin>
     */
    public static function getSchema(): array
    {
        return self::getPlugins('Schema');
    }

    /**
     * Reads all plugin information
     *
     * @param string $type the type of the plugin (import, export, etc)
     * @psalm-param 'Export'|'Import'|'Schema' $type
     *
     * @return Plugin[] list of plugin instances
     * @psalm-return (
     *   $type is 'Export'
     *   ? list<ExportPlugin>
     *   : ($type is 'Import' ? list<ImportPlugin> : list<SchemaPlugin>)
     * )
     */
    private static function getPlugins(string $type): array
    {
        try {
            $files = new FilesystemIterator(ROOT_PATH . 'libraries/classes/Plugins/' . $type);
        } catch (Throwable) {
            return [];
        }

        $plugins = [];

        /** @var SplFileInfo $fileInfo */
        foreach ($files as $fileInfo) {
            if (! $fileInfo->isReadable() || ! $fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                continue;
            }

            if (! str_starts_with($fileInfo->getFilename(), $type)) {
                continue;
            }

            $class = sprintf('PhpMyAdmin\\Plugins\\%s\\%s', $type, $fileInfo->getBasename('.php'));
            if (! class_exists($class) || ! is_subclass_of($class, Plugin::class) || ! $class::isAvailable()) {
                continue;
            }

            if ($type === 'Export' && is_subclass_of($class, ExportPlugin::class)) {
                $container = Core::getContainerBuilder();
                $plugins[] = new $class(
                    $container->get('relation'),
                    $container->get('export'),
                    $container->get('transformations'),
                );
            } elseif ($type === 'Import' && is_subclass_of($class, ImportPlugin::class)) {
                $plugins[] = new $class();
            } elseif ($type === 'Schema' && is_subclass_of($class, SchemaPlugin::class)) {
                $plugins[] = new $class();
            }
        }

        usort($plugins, static fn (Plugin $plugin1, Plugin $plugin2): int => strcasecmp(
            $plugin1->getProperties()->getText(),
            $plugin2->getProperties()->getText(),
        ));

        return $plugins;
    }

    /**
     * Returns locale string for $name or $name if no locale is found
     *
     * @param string|null $name for local string
     *
     * @return string  locale string for $name
     */
    public static function getString(string|null $name): string
    {
        return $GLOBALS[$name] ?? $name ?? '';
    }

    /**
     * Returns html input tag option 'checked' if plugin $opt
     * should be set by config or request
     *
     * @param string $section name of config section in
     *                        $GLOBALS['cfg'][$section] for plugin
     * @param string $opt     name of option
     * @psalm-param 'Export'|'Import'|'Schema' $section
     *
     * @return string  html input tag option 'checked'
     */
    public static function checkboxCheck(string $section, string $opt): string
    {
        // If the form is being repopulated using $_GET data, that is priority
        if (
            isset($_GET[$opt])
            || ! isset($_GET['repopulate'])
            && ((! empty($GLOBALS['timeout_passed']) && isset($_REQUEST[$opt]))
                || ! empty($GLOBALS['cfg'][$section][$opt]))
        ) {
            return ' checked="checked"';
        }

        return '';
    }

    /**
     * Returns default value for option $opt
     *
     * @param string $section name of config section in
     *                        $GLOBALS['cfg'][$section] for plugin
     * @param string $opt     name of option
     * @psalm-param 'Export'|'Import'|'Schema' $section
     *
     * @return string  default value for option $opt
     */
    public static function getDefault(string $section, string $opt): string
    {
        if (isset($_GET[$opt])) {
            // If the form is being repopulated using $_GET data, that is priority
            return htmlspecialchars($_GET[$opt]);
        }

        if (isset($GLOBALS['timeout_passed'], $_REQUEST[$opt]) && $GLOBALS['timeout_passed']) {
            return htmlspecialchars($_REQUEST[$opt]);
        }

        if (! isset($GLOBALS['cfg'][$section][$opt])) {
            return '';
        }

        $matches = [];
        /* Possibly replace localised texts */
        if (! preg_match_all('/(str[A-Z][A-Za-z0-9]*)/', (string) $GLOBALS['cfg'][$section][$opt], $matches)) {
            return htmlspecialchars((string) $GLOBALS['cfg'][$section][$opt]);
        }

        $val = $GLOBALS['cfg'][$section][$opt];
        foreach ($matches[0] as $match) {
            if (! isset($GLOBALS[$match])) {
                continue;
            }

            $val = str_replace($match, $GLOBALS[$match], $val);
        }

        return htmlspecialchars($val);
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
                'text' => self::getString($properties->getText()),
                'is_selected' => $pluginName === $default,
                'is_binary' => $properties->getForceFile(),
            ];
        }

        return $return;
    }

    /**
     * Returns single option in a list element
     *
     * @param string              $section       name of config section in $GLOBALS['cfg'][$section] for plugin
     * @param string              $pluginName    unique plugin name
     * @param OptionsPropertyItem $propertyGroup options property main group instance
     * @param bool                $isSubgroup    if this group is a subgroup
     * @psalm-param 'Export'|'Import'|'Schema' $section
     *
     * @return string  table row with option
     */
    private static function getOneOption(
        string $section,
        string $pluginName,
        OptionsPropertyItem $propertyGroup,
        bool $isSubgroup = false,
    ): string {
        $ret = "\n";

        $properties = null;
        if (! $isSubgroup) {
            // for subgroup headers
            if (mb_strpos($propertyGroup::class, 'PropertyItem')) {
                $properties = [$propertyGroup];
            } else {
                // for main groups
                $ret .= '<div id="' . $pluginName . '_' . $propertyGroup->getName() . '">';

                $text = null;
                if (method_exists($propertyGroup, 'getText')) {
                    $text = $propertyGroup->getText();
                }

                if ($text != null) {
                    $ret .= '<h5 class="card-title mt-4 mb-2">' . self::getString($text) . '</h5>';
                }

                $ret .= '<ul class="list-group">';
            }
        }

        $notSubgroupHeader = false;
        if ($properties === null) {
            $notSubgroupHeader = true;
            if ($propertyGroup instanceof OptionsPropertyGroup) {
                $properties = $propertyGroup->getProperties();
            }
        }

        $propertyClass = null;
        if ($properties !== null) {
            /** @var OptionsPropertySubgroup $propertyItem */
            foreach ($properties as $propertyItem) {
                $propertyClass = $propertyItem::class;
                // if the property is a subgroup, we deal with it recursively
                if (mb_strpos($propertyClass, 'Subgroup')) {
                    // for subgroups
                    // each subgroup can have a header, which may also be a form element
                    /** @var OptionsPropertyItem|null $subgroupHeader */
                    $subgroupHeader = $propertyItem->getSubgroupHeader();
                    if ($subgroupHeader !== null) {
                        $ret .= self::getOneOption($section, $pluginName, $subgroupHeader);
                    }

                    $ret .= '<li class="list-group-item"><ul class="list-group"';
                    if ($subgroupHeader !== null) {
                        $ret .= ' id="ul_' . $subgroupHeader->getName() . '">';
                    } else {
                        $ret .= '>';
                    }

                    $ret .= self::getOneOption($section, $pluginName, $propertyItem, true);
                    continue;
                }

                // single property item
                $ret .= self::getHtmlForProperty($section, $pluginName, $propertyItem);
            }
        }

        if ($isSubgroup) {
            // end subgroup
            $ret .= '</ul></li>';
        } elseif ($notSubgroupHeader) {
            // end main group
            $ret .= '</ul></div>';
        }

        if (method_exists($propertyGroup, 'getDoc')) {
            $doc = $propertyGroup->getDoc();
            if (is_array($doc)) {
                if (count($doc) === 3) {
                    $ret .= MySQLDocumentation::show($doc[1], false, null, null, $doc[2]);
                } elseif (count($doc) === 1) {
                    $ret .= MySQLDocumentation::showDocumentation('faq', $doc[0]);
                } else {
                    $ret .= MySQLDocumentation::show($doc[1]);
                }
            }
        }

        // Close the list element after $doc link is displayed
        if (
            $propertyClass === BoolPropertyItem::class
            || $propertyClass === MessageOnlyPropertyItem::class
            || $propertyClass === SelectPropertyItem::class
            || $propertyClass === TextPropertyItem::class
        ) {
            $ret .= '</li>';
        }

        return $ret . "\n";
    }

    /**
     * Get HTML for properties items
     *
     * @param string              $section      name of config section in
     *                                                            $GLOBALS['cfg'][$section] for plugin
     * @param string              $pluginName   unique plugin name
     * @param OptionsPropertyItem $propertyItem Property item
     * @psalm-param 'Export'|'Import'|'Schema' $section
     */
    public static function getHtmlForProperty(
        string $section,
        string $pluginName,
        OptionsPropertyItem $propertyItem,
    ): string {
        $ret = '';
        $propertyClass = $propertyItem::class;
        switch ($propertyClass) {
            case BoolPropertyItem::class:
                $ret .= '<li class="list-group-item">' . "\n";
                $ret .= '<div class="form-check form-switch">' . "\n";
                $ret .= '<input class="form-check-input" type="checkbox" role="switch" name="' . $pluginName . '_'
                    . $propertyItem->getName() . '"'
                    . ' value="something" id="checkbox_' . $pluginName . '_'
                    . $propertyItem->getName() . '"'
                    . ' '
                    . self::checkboxCheck(
                        $section,
                        $pluginName . '_' . $propertyItem->getName(),
                    );

                if ($propertyItem->getForce() != null) {
                    // Same code is also few lines lower, update both if needed
                    $ret .= ' onclick="if (!this.checked &amp;&amp; '
                        . '(!document.getElementById(\'checkbox_' . $pluginName
                        . '_' . $propertyItem->getForce() . '\') '
                        . '|| !document.getElementById(\'checkbox_'
                        . $pluginName . '_' . $propertyItem->getForce()
                        . '\').checked)) '
                        . 'return false; else return true;"';
                }

                $ret .= '>';
                $ret .= '<label class="form-check-label" for="checkbox_' . $pluginName . '_'
                    . $propertyItem->getName() . '">'
                    . self::getString($propertyItem->getText()) . '</label></div>';
                break;
            case DocPropertyItem::class:
                echo DocPropertyItem::class;
                break;
            case HiddenPropertyItem::class:
                $ret .= '<li class="list-group-item"><input type="hidden" name="' . $pluginName . '_'
                    . $propertyItem->getName() . '"'
                    . ' value="' . self::getDefault(
                        $section,
                        $pluginName . '_' . $propertyItem->getName(),
                    )
                    . '"></li>';
                break;
            case MessageOnlyPropertyItem::class:
                $ret .= '<li class="list-group-item">' . "\n";
                $ret .= self::getString($propertyItem->getText());
                break;
            case RadioPropertyItem::class:
                /** @var RadioPropertyItem $pitem */
                $pitem = $propertyItem;

                $default = self::getDefault(
                    $section,
                    $pluginName . '_' . $pitem->getName(),
                );

                $ret .= '<li class="list-group-item">';

                foreach ($pitem->getValues() as $key => $val) {
                    $ret .= '<div class="form-check"><input type="radio" name="' . $pluginName
                        . '_' . $pitem->getName() . '" class="form-check-input" value="' . $key
                        . '" id="radio_' . $pluginName . '_'
                        . $pitem->getName() . '_' . $key . '"';
                    if ($key == $default) {
                        $ret .= ' checked';
                    }

                    $ret .= '><label class="form-check-label" for="radio_' . $pluginName . '_'
                        . $pitem->getName() . '_' . $key . '">'
                        . self::getString($val) . '</label></div>';
                }

                $ret .= '</li>';

                break;
            case SelectPropertyItem::class:
                /** @var SelectPropertyItem $pitem */
                $pitem = $propertyItem;
                $ret .= '<li class="list-group-item">' . "\n";
                $ret .= '<label for="select_' . $pluginName . '_'
                    . $pitem->getName() . '" class="form-label">'
                    . self::getString($pitem->getText()) . '</label>';
                $ret .= '<select class="form-select" name="' . $pluginName . '_'
                    . $pitem->getName() . '"'
                    . ' id="select_' . $pluginName . '_'
                    . $pitem->getName() . '">';
                $default = self::getDefault(
                    $section,
                    $pluginName . '_' . $pitem->getName(),
                );
                foreach ($pitem->getValues() as $key => $val) {
                    $ret .= '<option value="' . $key . '"';
                    if ($key == $default) {
                        $ret .= ' selected';
                    }

                    $ret .= '>' . self::getString($val) . '</option>';
                }

                $ret .= '</select>';
                break;
            case TextPropertyItem::class:
                /** @var TextPropertyItem $pitem */
                $pitem = $propertyItem;
                $ret .= '<li class="list-group-item">' . "\n";
                $ret .= '<label for="text_' . $pluginName . '_'
                    . $pitem->getName() . '" class="form-label">'
                    . self::getString($pitem->getText()) . '</label>';
                $ret .= '<input class="form-control" type="text" name="' . $pluginName . '_'
                    . $pitem->getName() . '"'
                    . ' value="' . self::getDefault(
                        $section,
                        $pluginName . '_' . $pitem->getName(),
                    ) . '"'
                    . ' id="text_' . $pluginName . '_'
                    . $pitem->getName() . '"'
                    . ($pitem->getSize() !== 0
                        ? ' size="' . $pitem->getSize() . '"'
                        : '')
                    . ($pitem->getLen() !== 0
                        ? ' maxlength="' . $pitem->getLen() . '"'
                        : '')
                    . '>';
                break;
            case NumberPropertyItem::class:
                $ret .= '<li class="list-group-item">' . "\n";
                $ret .= '<label for="number_' . $pluginName . '_'
                    . $propertyItem->getName() . '" class="form-label">'
                    . self::getString($propertyItem->getText()) . '</label>';
                $ret .= '<input class="form-control" type="number" name="' . $pluginName . '_'
                    . $propertyItem->getName() . '"'
                    . ' value="' . self::getDefault(
                        $section,
                        $pluginName . '_' . $propertyItem->getName(),
                    ) . '"'
                    . ' id="number_' . $pluginName . '_'
                    . $propertyItem->getName() . '"'
                    . ' min="0"'
                    . '>';
                break;
            default:
                break;
        }

        return $ret;
    }

    /**
     * Returns html div with editable options for plugin
     *
     * @param string                                       $section name of config section in $GLOBALS['cfg'][$section]
     * @param ExportPlugin[]|ImportPlugin[]|SchemaPlugin[] $list    array with plugin instances
     * @psalm-param 'Export'|'Import'|'Schema' $section
     *
     * @return string  html fieldset with plugin options
     */
    public static function getOptions(string $section, array $list): string
    {
        $ret = '';
        // Options for plugins that support them
        foreach ($list as $plugin) {
            $properties = $plugin->getProperties();
            $text = $properties->getText();
            $options = $properties->getOptions();

            $pluginName = $plugin->getName();

            $ret .= '<div id="' . $pluginName
                . '_options" class="format_specific_options">';
            $ret .= '<h3>' . self::getString($text) . '</h3>';

            $noOptions = true;
            if ($options !== null && count($options) > 0) {
                foreach ($options->getProperties() as $propertyMainGroup) {
                    // check for hidden properties
                    $noOptions = true;
                    foreach ($propertyMainGroup->getProperties() as $propertyItem) {
                        if (! ($propertyItem instanceof HiddenPropertyItem)) {
                            $noOptions = false;
                            break;
                        }
                    }

                    $ret .= self::getOneOption($section, $pluginName, $propertyMainGroup);
                }
            }

            if ($noOptions) {
                $ret .= '<p class="card-text">' . __('This format has no options') . '</p>';
            }

            $ret .= '</div>';
        }

        return $ret;
    }
}
