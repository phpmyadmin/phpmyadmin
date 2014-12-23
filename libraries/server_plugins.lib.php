<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying server plugins
 *
 * @usedby  server_plugins.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Returns the html for plugin and module Info.
 *
 * @param Array $plugins Plugin list
 *
 * @param Array $modules Module list
 *
 * @return string
 */
function PMA_getPluginAndModuleInfo($plugins, $modules)
{
    $html  = '<script type="text/javascript">';
    $html .= 'pma_theme_image = "' . $GLOBALS['pmaThemeImage'] . '"';
    $html .= '</script>';
    $html .= '<div id="pluginsTabs">';
    $html .= '<ul>';
    $html .= '<li><a href="#plugins_plugins">' . __('Plugins') . '</a></li>';
    $html .= '<li><a href="#plugins_modules">' . __('Modules') . '</a></li>';
    $html .= '</ul>';
    $html .= PMA_getPluginTab($plugins);
    $html .= PMA_getModuleTab($modules);
    $html .= '</div>';
    return $html;
}

/**
 * Returns the html for plugin Tab.
 *
 * @param Array $plugins list
 *
 * @return string
 */
function PMA_getPluginTab($plugins)
{
    $html  = '<div id="plugins_plugins">';
    $html .= '<div id="sectionlinks">';

    foreach ($plugins as $plugin_type => $plugin_list) {
        $key = 'plugins-'
            . preg_replace('/[^a-z]/', '', /*overload*/mb_strtolower($plugin_type));
        $html .= '<a href="#' . $key . '">'
            . htmlspecialchars($plugin_type) . '</a>' . "\n";
    }

    $html .= '</div>';
    $html .= '<br />';

    foreach ($plugins as $plugin_type => $plugin_list) {
        $key = 'plugins-'
            . preg_replace('/[^a-z]/', '', /*overload*/mb_strtolower($plugin_type));
        sort($plugin_list);

        $html .= '<table class="data_full_width" id="' . $key . '">';
        $html .= '<caption class="tblHeaders">';
        $html .= '<a class="top" href="#serverinfo">';
        $html .=  __('Begin');
        $html .=  PMA_Util::getImage('s_asc.png');
        $html .= '</a>';
        $html .=  htmlspecialchars($plugin_type);
        $html .= '</caption>';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>' . __('Plugin') . '</th>';
        $html .= '<th>' . __('Module') . '</th>';
        $html .= '<th>' . __('Library') . '</th>';
        $html .= '<th>' . __('Version') . '</th>';
        $html .= '<th>' .  __('Author') . '</th>';
        $html .= '<th>' . __('License') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $html .= PMA_getPluginList($plugin_list);

        $html .= '</tbody>';
        $html .= '</table>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Returns the html for plugin List.
 *
 * @param Array $plugin_list list
 *
 * @return string
 */
function PMA_getPluginList($plugin_list)
{
    $html = "";
    $odd_row = false;
    foreach ($plugin_list as $plugin) {
        $odd_row = !$odd_row;
        $html .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
        $html .= '<th>' . htmlspecialchars($plugin['plugin_name']) . '</th>';
        $html .= '<td>' . htmlspecialchars($plugin['module_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($plugin['module_library']) . '</td>';
        $html .= '<td>' . htmlspecialchars($plugin['module_version']) . '</td>';
        $html .= '<td>' . htmlspecialchars($plugin['module_author']) . '</td>';
        $html .= '<td>' . htmlspecialchars($plugin['module_license']) . '</td>';
        $html .= '</tr>';
    }
    return $html;
}

/**
 * Returns the html for Module Tab.
 *
 * @param Array $modules list
 *
 * @return string
 */
function PMA_getModuleTab($modules)
{
    $html  = '<div id="plugins_modules">';
    $html .= '<table class="data_full_width">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th>' . __('Module') . '</th>';
    $html .= '<th>' . __('Description') . '</th>';
    $html .= '<th>' . __('Library') . '</th>';
    $html .= '<th>' . __('Version') . '</th>';
    $html .= '<th>' . __('Author') . '</th>';
    $html .= '<th>' . __('License') . '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    $html .= PMA_getModuleList($modules);
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    return $html;
}

/**
 * Returns the html for module List.
 *
 * @param Array $modules list
 *
 * @return string
 */
function PMA_getModuleList($modules)
{
    $html = "";
    $odd_row = false;
    foreach ($modules as $module_name => $module) {
        $odd_row = !$odd_row;
        $html .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
        $html .= '<th rowspan="2">' . htmlspecialchars($module_name) . '</th>';
        $html .= '<td>' . htmlspecialchars($module['info']['module_description'])
            .    '</td>';
        $html .= '<td>' . htmlspecialchars($module['info']['module_library'])
            .    '</td>';
        $html .= '<td>' . htmlspecialchars($module['info']['module_version'])
            .    '</td>';
        $html .= '<td>' . htmlspecialchars($module['info']['module_author'])
            .    '</td>';
        $html .= '<td>' . htmlspecialchars($module['info']['module_license'])
            .    '</td>';
        $html .= '</tr>';
        $html .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
        $html .= '<td colspan="5">';
        $html .= '<table>';
        $html .= '<tbody>';

        foreach ($module['plugins'] as $plugin_type => $plugin_list) {
            $html .= '<tr class="noclick">';
            $html .= '<td><b class="plugin-type">'
                . htmlspecialchars($plugin_type) . '</b></td>';
            $html .= '<td>';
            for ($i = 0, $nb = count($plugin_list); $i < $nb; $i++) {
                $html .= ($i != 0 ? '<br />' : '')
                    . htmlspecialchars($plugin_list[$i]['plugin_name']);
                if (!$plugin_list[$i]['is_active']) {
                    $html .= ' <small class="attention">' . __('disabled')
                        .    '</small>';
                }
            }
            $html .= '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</td>';
        $html .= '</tr>';
    }
    return $html;
}

?>
