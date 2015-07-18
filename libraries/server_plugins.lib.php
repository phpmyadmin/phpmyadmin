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
 * Get the HTML for the sub tabs
 *
 * @param string $activeUrl url of the active sub tab
 *
 * @return string HTML for sub tabs
 */
function PMA_getHtmlForPluginsSubTabs($activeUrl)
{
    $url_params = PMA_URL_getCommon();
    $items = array(
        array(
            'name' => __('Plugins'),
            'url' => 'server_plugins.php'
        ),
        array(
            'name' => __('Modules'),
            'url' => 'server_modules.php'
        )
    );

    $retval  = '<ul id="topmenu2">';
    foreach ($items as $item) {
        $class = '';
        if ($item['url'] === $activeUrl) {
            $class = ' class="tabactive"';
        }
        $retval .= '<li>';
        $retval .= '<a' . $class;
        $retval .= ' href="' . $item['url'] . $url_params . '">';
        $retval .= $item['name'];
        $retval .= '</a>';
        $retval .= '</li>';
    }
    $retval .= '</ul>';
    $retval .= '<div class="clearfloat"></div>';

    return $retval;
}

/**
 * Returns the common SQL used to retrieve plugin and modules data
 *
 * @return string SQL
 */
function PMA_getServerPluginModuleSQL()
{
    return "SELECT p.plugin_name, p.plugin_type, p.is_active, m.module_name,
            m.module_library, m.module_version, m.module_author,
            m.module_description, m.module_license
        FROM data_dictionary.plugins p
            JOIN data_dictionary.modules m USING (module_name)
        ORDER BY m.module_name, p.plugin_type, p.plugin_name";
}

/**
 * Returns details about server plugins
 *
 * @return array server plugins data
 */
function PMA_getServerPlugins()
{
    $sql = PMA_getServerPluginModuleSQL();
    $res = $GLOBALS['dbi']->query($sql);
    $plugins = array();
    while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
        $plugins[$row['plugin_type']][] = $row;
    }
    $GLOBALS['dbi']->freeResult($res);
    ksort($plugins);
    return $plugins;
}

/**
 * Returns details about server modules
 *
 * @return array server modules data
 */
function PMA_getServerModules()
{
    $sql = PMA_getServerPluginModuleSQL();
    $res = $GLOBALS['dbi']->query($sql);
    $modules = array();
    while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
        $modules[$row['module_name']]['info'] = $row;
        $modules[$row['module_name']]['plugins'][$row['plugin_type']][] = $row;
    }
    $GLOBALS['dbi']->freeResult($res);
    return $modules;
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

